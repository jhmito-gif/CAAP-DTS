import * as pdfjsLib from 'pdfjs-dist'
import PdfWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?worker'

pdfjsLib.GlobalWorkerOptions.workerPort = new PdfWorker()

// Default stamp size and the smallest allowed, in PDF points.
let DEFAULT_SIZE = { width: 180, height: 80 }
let MIN_SIZE = { width: 40, height: 24 }

/*
 * PDF viewer for the signing page (App\Livewire\SignDocument). Renders the
 * document with pdf.js and lets the signer place, drag and resize their
 * signature box. Only the placement (page + PDF-point rectangle) is sent to
 * the component; the server applies the stamp.
 */
/*
 * Lets the sending office mark where each signature goes, on PDFs that are
 * still uploads (App\Livewire\CreateOutgoing). One box per signatory per
 * document; marks are sent back as PDF points, the same units the stamper uses.
 */
document.addEventListener('alpine:init', () => {
  window.Alpine.data('placementPicker', ({ files, signers, marks }) => {
    let pdf = null
    let renderTask = null

    return {
      open: false,
      files,
      signers,
      marks: marks ?? {},
      fileKey: files[0]?.key ?? null,
      signerId: signers[0]?.id ?? null,
      pageNumber: 1,
      pageCount: 0,
      canvasWidth: 0,
      scale: 1,
      view: [0, 0, 612, 792],
      rotated: false,
      loading: true,
      error: null,
      drag: null,

      get file() {
        return this.files.find((file) => file.key === this.fileKey) ?? null
      },

      get markKey() {
        return `${this.fileKey}|${this.signerId}`
      },

      /** Every box marked for the chosen signatory on the chosen document. */
      get boxes() {
        return this.marks[this.markKey] ?? []
      },

      /** The box on the page being viewed, if one is set. */
      get mark() {
        return this.boxes.find((box) => box.page === this.pageNumber) ?? null
      },

      get markedPages() {
        return [...new Set(this.boxes.map((box) => box.page))].sort((a, b) => a - b)
      },

      markedFor(signerId) {
        return (this.marks[`${this.fileKey}|${signerId}`] ?? []).length
      },

      async init() {
        await this.load()

        let resizeTimer
        window.addEventListener('resize', () => {
          clearTimeout(resizeTimer)
          resizeTimer = setTimeout(() => this.render(), 150)
        })
      },

      async load() {
        this.loading = true
        this.error = null
        this.pageNumber = 1

        try {
          if (!this.file?.url) throw new Error('No preview available')

          pdf = await pdfjsLib.getDocument({ url: this.file.url }).promise
          this.pageCount = pdf.numPages
          await this.render()
        } catch (error) {
          console.error('Placement picker:', error)
          this.error = 'This document could not be opened for marking.'
        } finally {
          this.loading = false
        }
      },

      async render() {
        if (!pdf) return

        let page = await pdf.getPage(this.pageNumber)
        let base = page.getViewport({ scale: 1 })
        let available = Math.max(200, this.$refs.stage.clientWidth - 32)

        this.scale = Math.max(0.3, Math.min(1.6, available / base.width))
        this.view = [...page.view]
        this.rotated = page.rotate % 360 !== 0

        let viewport = page.getViewport({ scale: this.scale })
        let ratio = window.devicePixelRatio || 1
        let canvas = this.$refs.canvas

        canvas.width = Math.floor(viewport.width * ratio)
        canvas.height = Math.floor(viewport.height * ratio)
        canvas.style.width = `${viewport.width}px`
        canvas.style.height = `${viewport.height}px`
        this.canvasWidth = viewport.width

        renderTask?.cancel()
        renderTask = page.render({
          canvasContext: canvas.getContext('2d'),
          viewport,
          transform: ratio === 1 ? null : [ratio, 0, 0, ratio, 0, 0],
        })

        try {
          await renderTask.promise
        } catch (error) {
          if (error?.name !== 'RenderingCancelledException') throw error
        }
      },

      async selectFile(key) {
        if (key === this.fileKey) return

        this.fileKey = key
        await this.load()
      },

      async go(delta) {
        let next = this.pageNumber + delta
        if (next < 1 || next > this.pageCount) return

        this.pageNumber = next
        await this.render()
      },

      get boxVisible() {
        return Boolean(this.mark)
      },

      // PDF points (origin bottom-left) to CSS pixels on the rendered page.
      get boxStyle() {
        let mark = this.mark
        if (!mark) return ''

        let left = (mark.x - this.view[0]) * this.scale
        let top = (this.view[3] - (mark.y + mark.height)) * this.scale

        return `left:${left}px;top:${top}px;width:${mark.width * this.scale}px;height:${mark.height * this.scale}px`
      },

      place(event) {
        if (this.rotated || this.drag || !this.signerId) return

        let rect = this.$refs.canvas.getBoundingClientRect()
        let pdfX = this.view[0] + (event.clientX - rect.left) / this.scale
        let pdfY = this.view[3] - (event.clientY - rect.top) / this.scale
        let { width, height } = this.mark ?? this.boxes[0] ?? DEFAULT_SIZE

        this.update({ page: this.pageNumber, x: pdfX - width / 2, y: pdfY - height / 2, width, height })
      },

      /** Same spot on every page -- for documents initialled throughout. */
      applyToAllPages() {
        let mark = this.mark
        if (!mark) return

        let all = []
        for (let page = 1; page <= this.pageCount; page++) {
          all.push({ ...mark, page })
        }

        this.marks = { ...this.marks, [this.markKey]: all }
        this.commit()
      },

      startDrag(event, mode) {
        if (!this.mark) return

        this.drag = { mode, startX: event.clientX, startY: event.clientY, origin: { ...this.mark } }

        let move = (e) => this.onDrag(e)
        let up = () => {
          window.removeEventListener('pointermove', move)
          window.removeEventListener('pointerup', up)
          this.commit()
          setTimeout(() => { this.drag = null }, 0)
        }

        window.addEventListener('pointermove', move)
        window.addEventListener('pointerup', up)
      },

      onDrag(event) {
        let { mode, startX, startY, origin } = this.drag
        let dx = (event.clientX - startX) / this.scale
        let dy = (event.clientY - startY) / this.scale

        if (mode === 'move') {
          this.update({ ...origin, x: origin.x + dx, y: origin.y - dy }, false)
          return
        }

        let width = Math.max(MIN_SIZE.width, origin.width + dx)
        let height = Math.max(MIN_SIZE.height, origin.height + dy)
        this.update({ ...origin, width, height, y: origin.y + origin.height - height }, false)
      },

      update(next, commit = true) {
        let [left, bottom, right, top] = this.view
        let width = Math.min(Math.max(next.width, MIN_SIZE.width), right - left)
        let height = Math.min(Math.max(next.height, MIN_SIZE.height), top - bottom)
        let x = Math.min(Math.max(next.x, left), right - width)
        let y = Math.min(Math.max(next.y, bottom), top - height)

        this.marks = {
          ...this.marks,
          [this.markKey]: [
            ...this.boxes.filter((box) => box.page !== next.page),
            { page: next.page, x, y, width, height },
          ].sort((a, b) => a.page - b.page),
        }

        if (commit) this.commit()
      },

      /** Hand the marks to Livewire; dragging commits once, on release. */
      commit() {
        let round = (value) => Math.round(value * 100) / 100

        this.$wire.setPlacements(this.fileKey, this.signerId, this.boxes.map((box) => ({
          page: box.page,
          x: round(box.x),
          y: round(box.y),
          width: round(box.width),
          height: round(box.height),
        })))
      },

      /** Drop the mark on the page being viewed. */
      clearPage() {
        this.marks = { ...this.marks, [this.markKey]: this.boxes.filter((box) => box.page !== this.pageNumber) }
        this.commit()
      },

      clearAll() {
        let { [this.markKey]: removed, ...rest } = this.marks

        this.marks = rest
        this.$wire.clearPlacements(this.fileKey, this.signerId)
      },
    }
  })
})

/*
 * Small preview of the page a document will be signed on, with the detected
 * signature box outlined. Used by the signing queue
 * (App\Livewire\SignatureQueue) so a wrong placement is visible before signing.
 */
document.addEventListener('alpine:init', () => {
  window.Alpine.data('signatureThumb', ({ url, page, box }) => ({
    loading: true,
    failed: false,

    async init() {
      try {
        let pdf = await pdfjsLib.getDocument({ url }).promise
        let number = Math.min(Math.max(1, Number(page) || 1), pdf.numPages)
        let pdfPage = await pdf.getPage(number)
        let scale = 200 / pdfPage.getViewport({ scale: 1 }).width
        let viewport = pdfPage.getViewport({ scale })
        let canvas = this.$refs.canvas

        canvas.width = viewport.width
        canvas.height = viewport.height

        let context = canvas.getContext('2d')
        await pdfPage.render({ canvasContext: context, viewport }).promise

        if (box) {
          // PDF points start bottom-left; the canvas starts top-left.
          let x = box.x * scale
          let y = canvas.height - (box.y + box.height) * scale
          let width = box.width * scale
          let height = box.height * scale

          context.fillStyle = 'rgba(2, 132, 199, 0.15)'
          context.fillRect(x, y, width, height)
          context.strokeStyle = '#0284c7'
          context.lineWidth = 1.5
          context.setLineDash([4, 3])
          context.strokeRect(x, y, width, height)
        }
      } catch (error) {
        console.error('Signature preview:', error)
        this.failed = true
      } finally {
        this.loading = false
      }
    },
  }))
})

document.addEventListener('alpine:init', () => {
  window.Alpine.data('esignViewer', ({ url, canPlace, signature, initial }) => {
    // pdf.js objects use private class fields, which break inside Alpine's
    // reactive proxies, so they live outside the component state.
    let pdf = null
    let renderTask = null

    return {
      canPlace,
      signature,
      loading: true,
      error: null,
      pageNumber: 1,
      pageCount: 0,
      canvasWidth: 0,
      scale: 1,
      view: [0, 0, 612, 792],
      rotated: false,
      // One box per page, pre-filled with whatever the sender marked; a
      // document may need signing on several pages.
      placements: Array.isArray(initial) ? [...initial] : (initial ? [initial] : []),
      drag: null,

      async init() {
        try {
          pdf = await pdfjsLib.getDocument({ url }).promise
          this.pageCount = pdf.numPages
          await this.render()
        } catch (error) {
          console.error('E-sign viewer:', error)
          this.error = 'The document could not be loaded.'
        } finally {
          this.loading = false
        }

        let resizeTimer
        window.addEventListener('resize', () => {
          clearTimeout(resizeTimer)
          resizeTimer = setTimeout(() => this.render(), 150)
        })
      },

      async render() {
        if (!pdf) return

        let page = await pdf.getPage(this.pageNumber)
        let base = page.getViewport({ scale: 1 })
        let available = Math.max(200, this.$refs.stage.clientWidth - 32)

        this.scale = Math.max(0.3, Math.min(1.6, available / base.width))
        this.view = [...page.view]
        this.rotated = page.rotate % 360 !== 0

        let viewport = page.getViewport({ scale: this.scale })
        let ratio = window.devicePixelRatio || 1
        let canvas = this.$refs.canvas

        canvas.width = Math.floor(viewport.width * ratio)
        canvas.height = Math.floor(viewport.height * ratio)
        canvas.style.width = `${viewport.width}px`
        canvas.style.height = `${viewport.height}px`
        this.canvasWidth = viewport.width

        renderTask?.cancel()
        renderTask = page.render({
          canvasContext: canvas.getContext('2d'),
          viewport,
          transform: ratio === 1 ? null : [ratio, 0, 0, ratio, 0, 0],
        })

        try {
          await renderTask.promise
        } catch (error) {
          if (error?.name !== 'RenderingCancelledException') throw error
        }
      },

      async go(delta) {
        let next = this.pageNumber + delta
        if (next < 1 || next > this.pageCount) return

        this.pageNumber = next
        await this.render()
      },

      /** The box on the page being viewed, if one is set. */
      get placement() {
        return this.placements.find((box) => box.page === this.pageNumber) ?? null
      },

      get markedPages() {
        return [...new Set(this.placements.map((box) => box.page))].sort((a, b) => a - b)
      },

      get boxVisible() {
        return Boolean(this.placement)
      },

      // PDF points (origin bottom-left) to CSS pixels on the rendered page.
      get boxStyle() {
        let p = this.placement
        if (!p) return ''

        let left = (p.x - this.view[0]) * this.scale
        let top = (this.view[3] - (p.y + p.height)) * this.scale

        return `left:${left}px;top:${top}px;width:${p.width * this.scale}px;height:${p.height * this.scale}px`
      },

      place(event) {
        if (!this.canPlace || this.rotated || this.drag) return

        let rect = this.$refs.canvas.getBoundingClientRect()
        let pdfX = this.view[0] + (event.clientX - rect.left) / this.scale
        let pdfY = this.view[3] - (event.clientY - rect.top) / this.scale
        let { width, height } = this.placement ?? this.placements[0] ?? DEFAULT_SIZE

        this.update({ page: this.pageNumber, x: pdfX - width / 2, y: pdfY - height / 2, width, height })
      },

      /** Drop the box on the page being viewed. */
      clearPage() {
        this.placements = this.placements.filter((box) => box.page !== this.pageNumber)
        this.push()
      },

      startDrag(event, mode) {
        if (!this.canPlace || !this.placement) return

        this.drag = { mode, startX: event.clientX, startY: event.clientY, origin: { ...this.placement } }

        let move = (e) => this.onDrag(e)
        let up = () => {
          window.removeEventListener('pointermove', move)
          window.removeEventListener('pointerup', up)
          // Let the click that ends a drag pass without re-placing the box.
          setTimeout(() => { this.drag = null }, 0)
        }

        window.addEventListener('pointermove', move)
        window.addEventListener('pointerup', up)
      },

      onDrag(event) {
        let { mode, startX, startY, origin } = this.drag
        let dx = (event.clientX - startX) / this.scale
        let dy = (event.clientY - startY) / this.scale

        if (mode === 'move') {
          this.update({ ...origin, x: origin.x + dx, y: origin.y - dy })
          return
        }

        // Resizing from the bottom-right corner keeps the top edge fixed.
        let width = Math.max(MIN_SIZE.width, origin.width + dx)
        let height = Math.max(MIN_SIZE.height, origin.height + dy)
        this.update({ ...origin, width, height, y: origin.y + origin.height - height })
      },

      update(next) {
        let [left, bottom, right, top] = this.view
        let width = Math.min(Math.max(next.width, MIN_SIZE.width), right - left)
        let height = Math.min(Math.max(next.height, MIN_SIZE.height), top - bottom)
        let x = Math.min(Math.max(next.x, left), right - width)
        let y = Math.min(Math.max(next.y, bottom), top - height)

        this.placements = [
          ...this.placements.filter((box) => box.page !== next.page),
          { page: next.page, x, y, width, height },
        ].sort((a, b) => a.page - b.page)

        this.push()
      },

      /** Deferred: sent with the sign action, not as separate requests. */
      push() {
        let round = (value) => Math.round(value * 100) / 100

        this.$wire.$set('placements', this.placements.map((box) => ({
          page: box.page,
          x: round(box.x),
          y: round(box.y),
          width: round(box.width),
          height: round(box.height),
        })), false)
      },
    }
  })
})
