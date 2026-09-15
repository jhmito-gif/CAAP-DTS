import { PDFDocument, StandardFonts, rgb } from 'pdf-lib'

/*
 * Stamps a visual signature onto one page of a PDF. Run by
 * App\Support\PdfSignatureStamper; nothing touches the disk.
 *
 * stdin:  JSON { pdf, signature (base64 PNG), page (1-based), x, y, width,
 *         height (PDF points, origin bottom-left), lines: string[] }
 * stdout: the stamped PDF, base64
 * exit 2: the request itself is invalid; stderr holds a message for the user
 */

let chunks = []
for await (let chunk of process.stdin) chunks.push(chunk)
let args = JSON.parse(Buffer.concat(chunks).toString('utf8'))

function reject(message) {
  process.stderr.write(message)
  process.exit(2)
}

let pdf

try {
  pdf = await PDFDocument.load(Buffer.from(args.pdf, 'base64'), { updateMetadata: false })
} catch (error) {
  reject(/encrypt/i.test(String(error?.message))
    ? 'This PDF is password-protected and cannot be signed.'
    : 'This file is not a readable PDF.')
}

let pages = pdf.getPages()
let pageNumber = Number(args.page)

if (!Number.isInteger(pageNumber) || pageNumber < 1 || pageNumber > pages.length) {
  reject('The selected page does not exist.')
}

let page = pages[pageNumber - 1]

if (page.getRotation().angle % 360 !== 0) {
  reject('Rotated pages cannot be signed yet.')
}

let [x, y, width, height] = [args.x, args.y, args.width, args.height].map(Number)
let media = page.getMediaBox()
let insidePage = [x, y, width, height].every(Number.isFinite)
  && width >= 40 && height >= 24
  && x >= media.x - 0.5 && y >= media.y - 0.5
  && x + width <= media.x + media.width + 0.5
  && y + height <= media.y + media.height + 0.5

if (!insidePage) {
  reject('The signature must be placed fully inside the page.')
}

let image = await pdf.embedPng(Buffer.from(args.signature, 'base64'))
let regular = await pdf.embedFont(StandardFonts.Helvetica)
let bold = await pdf.embedFont(StandardFonts.HelveticaBold)

// Text lines (name, date, verification code) sit under the signature image.
let lines = (args.lines ?? []).map(String)
let size = Math.min(7, Math.max(4.5, height / 11))
let lineHeight = size * 1.3
let textBlock = lines.length * lineHeight

// The standard fonts cannot draw every character; fall back to plain ASCII.
function drawable(font, text) {
  try {
    font.encodeText(text)
    return text
  } catch {
    return text.normalize('NFKD').replace(/[^\x20-\x7E]/g, '')
  }
}

function fit(font, text, maxWidth) {
  let value = drawable(font, text)

  if (font.widthOfTextAtSize(value, size) <= maxWidth) {
    return value
  }

  while (value.length > 1 && font.widthOfTextAtSize(value + '...', size) > maxWidth) {
    value = value.slice(0, -1)
  }

  return value + '...'
}

let imageHeight = Math.max(1, height - textBlock)
let scale = Math.min(width / image.width, imageHeight / image.height)
let drawWidth = image.width * scale
let drawHeight = image.height * scale

page.drawImage(image, {
  x: x + (width - drawWidth) / 2,
  y: y + textBlock + (imageHeight - drawHeight) / 2,
  width: drawWidth,
  height: drawHeight,
})

lines.forEach((line, index) => {
  let font = index === 0 ? bold : regular
  let text = fit(font, line, width)

  page.drawText(text, {
    x: x + (width - font.widthOfTextAtSize(text, size)) / 2,
    y: y + textBlock - lineHeight * (index + 1) + (lineHeight - size) / 2,
    size,
    font,
    color: rgb(0, 0, 0),
  })
})

process.stdout.write(Buffer.from(await pdf.save()).toString('base64'))
