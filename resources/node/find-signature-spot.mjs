import { getDocument } from 'pdfjs-dist/legacy/build/pdf.mjs'

/*
 * Finds where a signature belongs in a PDF. Run by
 * App\Support\SignatureSpotFinder; nothing touches the disk.
 *
 * stdin:  JSON { pdf (base64), name (signer's name, optional),
 *                width, height (the signature box, PDF points) }
 * stdout: JSON { page, x, y, width, height, reason, anchor }
 * exit 2: the file cannot be read; stderr holds a message for the user
 *
 * Signature blocks in office documents follow a handful of shapes: a label
 * ("Approved by:", "Noted by:"), the signer's printed name, or a ruled line
 * above a printed name. We look for those, newest page first, and fall back to
 * the foot of the last page.
 */

let chunks = []
for await (let chunk of process.stdin) chunks.push(chunk)
let args = JSON.parse(Buffer.concat(chunks).toString('utf8'))

function reject(message) {
  process.stderr.write(message)
  process.exit(2)
}

let boxWidth = Number(args.width) > 0 ? Number(args.width) : 180
let boxHeight = Number(args.height) > 0 ? Number(args.height) : 80
let signer = String(args.name ?? '').trim().toLowerCase()

// "Approved by:" and friends sit above the space meant for the signature.
let LABELS = /\b(approved|noted|recommended|endorsed|certified|signed|conforme|attested)\s*(by)?\s*[:\-]?\s*$/i
let NAME_HINT = /signature over printed name|printed name|authorized signature/i

let pdf

try {
  pdf = await getDocument({
    data: Uint8Array.from(Buffer.from(args.pdf, 'base64')),
    isEvalSupported: false,
    disableFontFace: true,
    useSystemFonts: false,
    // pdf.js writes warnings to stdout, which would land in front of our JSON.
    verbosity: 0,
  }).promise
} catch (error) {
  reject(/password|encrypt/i.test(String(error?.message))
    ? 'This PDF is password-protected and cannot be read.'
    : 'This file is not a readable PDF.')
}

function clamp(value, min, max) {
  return Math.min(Math.max(value, min), max)
}

let fallback = null
let found = null

// Later pages first: signature blocks live at the end of a document.
for (let number = pdf.numPages; number >= 1 && !found; number--) {
  let page = await pdf.getPage(number)
  let viewport = page.getViewport({ scale: 1 })
  let [pageWidth, pageHeight] = [viewport.width, viewport.height]
  let rotated = (page.rotate ?? 0) % 360 !== 0

  if (!fallback && !rotated) {
    // Foot of the last usable page, right-hand side, inside a 54pt margin.
    fallback = {
      page: number,
      x: clamp(pageWidth - boxWidth - 54, 36, Math.max(36, pageWidth - boxWidth - 36)),
      y: 72,
      width: boxWidth,
      height: boxHeight,
      reason: 'fallback',
      anchor: null,
    }
  }

  if (rotated) continue

  let content = await page.getTextContent()

  let items = content.items
    .filter((item) => String(item.str ?? '').trim() !== '')
    .map((item) => ({
      text: String(item.str).trim(),
      x: item.transform[4],
      y: item.transform[5],
      width: item.width ?? 0,
      height: item.height ?? 10,
    }))

  // The signer's own printed name: sign directly above it.
  let nameItem = signer
    ? items.find((item) => item.text.toLowerCase().replace(/\s+/g, ' ') === signer)
    : null

  if (nameItem) {
    found = {
      page: number,
      x: clamp(nameItem.x + nameItem.width / 2 - boxWidth / 2, 24, pageWidth - boxWidth - 24),
      y: clamp(nameItem.y + nameItem.height + 4, 24, pageHeight - boxHeight - 24),
      width: boxWidth,
      height: boxHeight,
      reason: 'printed name',
      anchor: nameItem.text,
    }
    break
  }

  // "Approved by:" style label: sign in the space beneath it.
  let label = items
    .filter((item) => LABELS.test(item.text) || NAME_HINT.test(item.text))
    .sort((a, b) => a.y - b.y)[0]

  if (label) {
    let below = label.y - 8 - boxHeight

    // The approving official signs at the right-hand side of the page, even
    // when the label marking the space sits over at the left margin. A label
    // already further right wins, so two-column blocks stay aligned.
    let rightSide = Math.max(label.x, pageWidth - boxWidth - 54)

    found = {
      page: number,
      x: clamp(rightSide, 24, Math.max(24, pageWidth - boxWidth - 24)),
      y: below >= 24 ? below : clamp(label.y + label.height + 4, 24, pageHeight - boxHeight - 24),
      width: boxWidth,
      height: boxHeight,
      reason: LABELS.test(label.text) ? 'signature label' : 'printed-name line',
      anchor: label.text,
    }
  }
}

let spot = found ?? fallback

if (!spot) {
  reject('Every page of this document is rotated, so it cannot be signed automatically.')
}

process.stdout.write(JSON.stringify(spot))
