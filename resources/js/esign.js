import * as pdfjsLib from 'pdfjs-dist'
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url'

pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl

// Default stamp size and the smallest allowed, in PDF points.
let DEFAULT_SIZE = { width: 180, height: 80 }
let MIN_SIZE = { width: 40, height: 24 }

/*
 * PDF viewer for the signing page (App\Livewire\SignDocument). Renders the
 * document with pdf.js and lets the signer place, drag and resize their
 * signature box. Only the placement (page + PDF-point rectangle) is sent to
 * the component; the server applies the stamp.
 */
document.addEventListener('alpine:init', () => {
  window.Alpine.data('esignViewer', ({ url, canPlace, signature }) => {
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
      placement: null,
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

      get boxVisible() {
        return Boolean(this.placement && this.placement.page === this.pageNumber)
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
        let { width, height } = this.placement ?? DEFAULT_SIZE

        this.update({ page: this.pageNumber, x: pdfX - width / 2, y: pdfY - height / 2, width, height })
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
        let round = (value) => Math.round(value * 100) / 100

        this.placement = { page: next.page, x, y, width, height }

        // Deferred: sent with the sign action, not as separate requests.
        this.$wire.$set('page', next.page, false)
        this.$wire.$set('x', round(x), false)
        this.$wire.$set('y', round(y), false)
        this.$wire.$set('width', round(width), false)
        this.$wire.$set('height', round(height), false)
      },
    }
  })
})
