import { getDocument } from 'pdfjs-dist/legacy/build/pdf.mjs'
import { PDFiumLibrary } from '@hyzyla/pdfium'
import { createCanvas, ImageData } from '@napi-rs/canvas'
import { createWorker } from 'tesseract.js'

/*
 * Reads the words out of a document so it can be searched by its contents.
 * Run by App\Support\DocumentReader; nothing touches the disk except the
 * language data tesseract caches.
 *
 * stdin:  JSON { file (base64), mime, maxPages, ocr (bool), cachePath, langPath }
 * stdout: JSON { pages, text, method, characters, ocrPages }
 * exit 2: the file cannot be read; stderr holds a message for the user
 *
 * A PDF made by a word processor carries its text already, and reading that is
 * near-instant. A scan carries none, so those pages are drawn and passed to
 * OCR, which is far slower -- hence the page cap.
 */

let chunks = []
for await (let chunk of process.stdin) chunks.push(chunk)
let args = JSON.parse(Buffer.concat(chunks).toString('utf8'))

function reject(message) {
  process.stderr.write(message)
  process.exit(2)
}

let file = Buffer.from(args.file ?? '', 'base64')
let mime = String(args.mime ?? '')
let maxPages = Number(args.maxPages) > 0 ? Number(args.maxPages) : 30
let mayOcr = args.ocr !== false

// A page with fewer characters than this is treated as a scan.
let TEXT_PER_PAGE = 60

let worker = null

async function ocr(image) {
  worker ??= await createWorker('eng', 1, {
    cachePath: args.cachePath || undefined,
    langPath: args.langPath || undefined,
    logger: () => {},
    errorHandler: () => {},
  })

  let { data } = await worker.recognize(image)

  return String(data?.text ?? '').trim()
}

function tidy(text) {
  return text.replace(/[ \t]+/g, ' ').replace(/\n{3,}/g, '\n\n').trim()
}

async function readImage() {
  if (! mayOcr) return { pages: 1, text: '', method: 'none', ocrPages: 0 }

  return { pages: 1, text: await ocr(file), method: 'ocr', ocrPages: 1 }
}

/*
 * Drawing pages is pdfium's job, not pdf.js's: pdf.js rendering onto a native
 * canvas under Node corrupts the heap and takes the process down with it.
 * pdfium is WebAssembly, so it needs nothing installed on the machine.
 */
let pdfium = null

async function drawPage(file, number, scale = 2) {
  pdfium ??= await PDFiumLibrary.init()

  let document_ = await pdfium.loadDocument(file)

  try {
    let { data, width, height } = await document_.getPage(number - 1).render({ scale, render: 'bitmap' })

    // pdfium hands back BGRA; a canvas wants RGBA.
    let pixels = new Uint8ClampedArray(data.length)

    for (let i = 0; i < data.length; i += 4) {
      pixels[i] = data[i + 2]
      pixels[i + 1] = data[i + 1]
      pixels[i + 2] = data[i]
      pixels[i + 3] = data[i + 3]
    }

    let canvas = createCanvas(width, height)
    canvas.getContext('2d').putImageData(new ImageData(pixels, width, height), 0, 0)

    return canvas.toBuffer('image/png')
  } finally {
    document_.destroy()
  }
}

async function readPdf() {
  let pdf

  try {
    pdf = await getDocument({
      data: new Uint8Array(file),
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

  let pages = Math.min(pdf.numPages, maxPages)
  let parts = []
  let ocrPages = 0
  let layerPages = 0

  for (let number = 1; number <= pages; number++) {
    let page = await pdf.getPage(number)
    let content = await page.getTextContent()
    let text = tidy(content.items.map((item) => item.str ?? '').join(' '))

    if (text.length >= TEXT_PER_PAGE || ! mayOcr) {
      if (text.length >= TEXT_PER_PAGE) layerPages++
      parts.push(text)

      continue
    }

    // Little or no text layer: draw the page (at about 150 dpi) and read it.
    let read = await ocr(await drawPage(file, number, 2))

    if (read !== '') ocrPages++

    parts.push(tidy(read))
  }

  let method = ocrPages > 0 && layerPages > 0 ? 'mixed' : (ocrPages > 0 ? 'ocr' : 'text layer')

  return { pages, text: parts.join('\n\n'), method, ocrPages }
}

try {
  let result = mime.startsWith('image/') ? await readImage() : await readPdf()

  result.text = tidy(result.text)
  result.characters = result.text.length

  process.stdout.write(JSON.stringify(result))
} catch (error) {
  reject(`This file could not be read: ${String(error?.message ?? error).slice(0, 200)}`)
} finally {
  await worker?.terminate()
  pdfium?.destroy()
}
