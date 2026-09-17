import * as pdfjsLib from 'pdfjs-dist'
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url'

pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl

/*
 * Floating document viewer.
 *
 * Any page can open a document in a window of its own:
 *
 *   window.dispatchEvent(new CustomEvent('open-document', { detail: {
 *     key, name, url, kind: 'pdf' | 'image' | 'other', download, record,
 *   }}))
 *
 * Several windows can be open at once. Each is dragged by its title bar,
 * resized from its corner, minimised to the strip at the foot of the screen,
 * and remembers where it was put (per document, in this browser).
 */

let SIZES = { width: 720, height: 780 }
let MIN = { width: 360, height: 280 }
let STORE = 'dts.viewer.windows'

/** Where the details panel gets what it shows (routes/web.php). */
let INFO_URL = '/documents/info'

/**
 * Draws one page onto a canvas.
 *
 * Fitted, the whole page is made to sit inside the space available, so a
 * document is readable the moment it opens without anyone reaching for the
 * zoom. Zooming turns fitting off until "Fit" is pressed again.
 */
export async function drawPage({ pdf, number, canvas, width, height, fit = true, scale = 1 }) {
  let page = await pdf.getPage(number)
  let unscaled = page.getViewport({ scale: 1 })

  let factor = fit
    ? Math.min(width / unscaled.width, height / unscaled.height)
    : (width / unscaled.width) * scale

  let viewport = page.getViewport({ scale: Math.max(0.1, factor) })

  canvas.width = viewport.width
  canvas.height = viewport.height
  canvas.style.width = `${viewport.width}px`
  canvas.style.height = `${viewport.height}px`

  await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise

  return factor
}

export function loadPdf(url) {
  return pdfjsLib.getDocument({ url, withCredentials: true }).promise
}

/*
 * Open pdf.js documents, kept out of Alpine's reactive state: proxying one
 * breaks the private fields it uses internally, and every page read fails.
 */
let documents = new Map()

/** Where this document's window was last left, if anywhere. */
function remembered(key) {
  try {
    return JSON.parse(localStorage.getItem(STORE) ?? '{}')[key] ?? null
  } catch {
    return null
  }
}

function remember(key, box) {
  try {
    let all = JSON.parse(localStorage.getItem(STORE) ?? '{}')
    all[key] = box
    localStorage.setItem(STORE, JSON.stringify(all))
  } catch {
    // A private window, or storage turned off: the viewer still works.
  }
}

document.addEventListener('alpine:init', () => {
  /*
   * A file's own page: the document filling the space it has, fitted so it can
   * be read without touching the zoom, and re-fitted when the window changes
   * size. The floating window above is a convenience on top of this.
   */
  window.Alpine.data('documentPage', (file) => ({
    file,
    page: 1,
    pages: 0,
    fit: true,
    scale: 1,
    loading: file.mime === 'application/pdf',
    error: null,
    pdf: null,

    init() {
      if (this.file.mime !== 'application/pdf') {
        this.loading = false

        return
      }

      this.load()

      // Re-fitted whenever there is more or less room.
      let redraw = () => this.draw()
      window.addEventListener('resize', redraw)
      this.$el.addEventListener('alpine:destroyed', () => window.removeEventListener('resize', redraw))
    },

    async load() {
      try {
        let document_ = await loadPdf(this.file.view_url)

        documents.set(`page:${this.file.key}`, document_)
        this.pages = document_.numPages
        this.loading = false

        await this.draw()
      } catch (error) {
        console.error('document page:', error)
        this.loading = false
        this.error = 'This file could not be opened here.'
      }
    },

    async draw() {
      let document_ = documents.get(`page:${this.file.key}`)
      let canvas = this.$refs.canvas

      if (! document_ || ! canvas) return

      let stage = this.$refs.stage

      await drawPage({
        pdf: document_,
        number: this.page,
        canvas,
        width: Math.max(240, (stage?.clientWidth ?? 800) - 32),
        // Fitted to the screen, leaving room for the header and controls.
        height: Math.max(320, window.innerHeight - 220),
        fit: this.fit,
        scale: this.scale,
      })
    },

    async go(delta) {
      let next = this.page + delta

      if (next < 1 || next > this.pages) return

      this.page = next
      await this.draw()
    },

    async zoom(factor) {
      this.scale = Math.min(3, Math.max(0.3, this.scale * factor))
      this.fit = false
      await this.draw()
    },

    async fitToPage() {
      this.fit = true
      this.scale = 1
      await this.draw()
    },

    openFloating() {
      window.dispatchEvent(new CustomEvent('open-document', {
        detail: {
          key: this.file.key,
          name: this.file.name,
          url: this.file.view_url,
          kind: this.file.mime === 'application/pdf' ? 'pdf' : (this.file.mime.startsWith('image/') ? 'image' : 'other'),
          download: this.file.download_url,
          record: this.file.record_url,
          meta: this.file.kind,
        },
      }))
    },
  }))

  window.Alpine.data('documentWindows', () => ({
    windows: [],
    top: 10,
    dragging: null,
    resizing: null,

    init() {
      window.addEventListener('open-document', (event) => this.open(event.detail ?? {}))

      // Livewire components reach the viewer through their own dispatch.
      window.addEventListener('open-document-window', (event) => this.open(event.detail ?? {}))

      // Docked windows follow the edge when the screen changes size.
      window.addEventListener('resize', () => {
        for (let win of this.windows) {
          if (win.dock !== 'float') this.applyDock(win)
        }
      })
    },

    open(detail) {
      let key = detail.key ?? detail.url

      if (! key || ! detail.url) return

      let existing = this.windows.find((w) => w.key === key)

      if (existing) {
        existing.minimised = false
        this.focus(existing)

        return
      }

      let saved = remembered(key)
      let box = saved ?? this.cascade()

      this.windows.push({
        key,
        name: detail.name ?? 'Document',
        url: detail.url,
        kind: detail.kind ?? 'other',
        download: detail.download ?? null,
        record: detail.record ?? null,
        meta: detail.meta ?? '',
        ...box,
        // Floating, or docked to the left or right edge.
        dock: saved?.dock ?? 'float',
        showInfo: saved?.showInfo ?? false,
        info: null,
        infoError: null,
        z: ++this.top,
        minimised: false,
        maximised: false,
        page: 1,
        pages: 0,
        // Fitted to the window until someone zooms.
        fit: true,
        scale: 1,
        loading: detail.kind === 'pdf',
        error: null,
        restore: null,
      })

      this.$nextTick(() => {
        let created = this.windows[this.windows.length - 1]

        if (created.dock !== 'float') this.applyDock(created)
        if (created.kind === 'pdf') this.load(created)
        if (created.showInfo) this.loadInfo(created)
      })
    },

    /*
     * Docking. A docked window sits against an edge, full height, and is
     * resized by dragging its inner edge -- the explorer stays usable beside
     * it. Floating puts it back where it last was.
     */
    dockTo(win, side) {
      if (win.dock === side) {
        win.dock = 'float'
        Object.assign(win, win.restore ?? this.cascade())
        this.store(win)

        if (win.kind === 'pdf') this.render(win)

        return
      }

      if (win.dock === 'float') {
        win.restore = { x: win.x, y: win.y, width: win.width, height: win.height }
      }

      win.dock = side
      win.maximised = false
      this.applyDock(win)
      this.focus(win)
      this.store(win)

      if (win.kind === 'pdf') this.render(win)
    },

    applyDock(win) {
      let width = Math.min(Math.max(win.width, MIN.width), Math.round(window.innerWidth * 0.75))

      Object.assign(win, {
        width,
        y: 0,
        height: window.innerHeight,
        x: win.dock === 'left' ? 0 : window.innerWidth - width,
      })
    },

    /** Everything known about the file, for the panel beside it. */
    async toggleInfo(win) {
      win.showInfo = ! win.showInfo
      this.store(win)

      if (win.showInfo && ! win.info) await this.loadInfo(win)
      if (win.kind === 'pdf') await this.render(win)
    },

    async loadInfo(win) {
      try {
        let response = await fetch(`${INFO_URL}?key=${encodeURIComponent(win.key)}`, {
          credentials: 'same-origin',
          headers: { Accept: 'application/json' },
        })

        if (! response.ok) throw new Error('not allowed')

        win.info = await response.json()
        win.infoError = null
      } catch {
        win.info = null
        win.infoError = 'The details of this file are not available to you.'
      }
    },

    /**
     * Each new window sits a little down and right of the last, clear of the
     * folder tree so the explorer stays usable behind it.
     */
    cascade() {
      let offset = (this.windows.length % 6) * 28

      return {
        x: Math.max(12, Math.min(window.innerWidth - SIZES.width - 24, 300 + offset)),
        y: Math.max(12, 70 + offset),
        width: Math.min(SIZES.width, window.innerWidth - 48),
        height: Math.min(SIZES.height, window.innerHeight - 120),
      }
    },

    close(win) {
      this.store(win)
      documents.get(win.key)?.destroy?.()
      documents.delete(win.key)
      this.windows = this.windows.filter((w) => w.key !== win.key)
    },

    focus(win) {
      win.z = ++this.top
    },

    minimise(win) {
      this.store(win)
      win.minimised = true
    },

    maximise(win) {
      if (win.maximised) {
        Object.assign(win, win.restore ?? {}, { maximised: false })
      } else {
        win.restore = { x: win.x, y: win.y, width: win.width, height: win.height }
        Object.assign(win, {
          x: 8,
          y: 64,
          width: window.innerWidth - 16,
          height: window.innerHeight - 80,
          maximised: true,
        })
      }

      this.focus(win)

      if (win.kind === 'pdf') this.render(win)
    },

    store(win) {
      if (win.maximised) return

      // A docked window remembers its width and which edge; where it sits is
      // worked out from the screen.
      remember(win.key, {
        x: win.x,
        y: win.y,
        width: win.width,
        height: win.height,
        dock: win.dock,
        showInfo: win.showInfo,
        ...(win.dock !== 'float' ? win.restore ?? {} : {}),
      })
    },

    /*
     * Dragging and resizing. Pointer events are listened for on the window
     * itself, so a drag survives the pointer leaving the title bar.
     */
    startDrag(event, win) {
      this.focus(win)

      // Dragging a docked window pulls it off the edge, as on a desktop.
      if (win.dock !== 'float') {
        win.dock = 'float'
        Object.assign(win, win.restore ?? this.cascade(), { y: Math.max(0, event.clientY - 14) })

        if (win.kind === 'pdf') this.render(win)
      }

      this.dragging = { key: win.key, dx: event.clientX - win.x, dy: event.clientY - win.y }
      event.target.setPointerCapture?.(event.pointerId)
    },

    startResize(event, win) {
      this.focus(win)
      this.resizing = { key: win.key, x: event.clientX, y: event.clientY, width: win.width, height: win.height }
      event.target.setPointerCapture?.(event.pointerId)
    },

    onMove(event) {
      let move = this.dragging ?? this.resizing

      if (! move) return

      let win = this.windows.find((w) => w.key === move.key)

      if (! win) return

      if (this.dragging) {
        win.x = Math.max(-win.width + 120, Math.min(window.innerWidth - 80, event.clientX - move.dx))
        win.y = Math.max(0, Math.min(window.innerHeight - 40, event.clientY - move.dy))
      } else if (win.dock === 'left') {
        // Docked: only the inner edge moves.
        win.width = Math.max(MIN.width, Math.min(window.innerWidth - 80, event.clientX))
      } else if (win.dock === 'right') {
        win.width = Math.max(MIN.width, Math.min(window.innerWidth - 80, window.innerWidth - event.clientX))
        win.x = window.innerWidth - win.width
      } else {
        win.width = Math.max(MIN.width, move.width + (event.clientX - move.x))
        win.height = Math.max(MIN.height, move.height + (event.clientY - move.y))
      }
    },

    endMove() {
      let win = this.windows.find((w) => w.key === (this.dragging ?? this.resizing)?.key)
      let wasResizing = Boolean(this.resizing)

      this.dragging = null
      this.resizing = null

      if (! win) return

      this.store(win)

      // A resized window re-renders to fill its new width.
      if (wasResizing && win.kind === 'pdf') this.render(win)
    },

    /*
     * PDFs, through pdf.js: page by page, so a long document stays cheap.
     */
    async load(win) {
      try {
        let document_ = await loadPdf(win.url)

        documents.set(win.key, document_)
        win.pages = document_.numPages
        win.loading = false

        await this.render(win)
      } catch (error) {
        console.error('document viewer:', error)
        win.loading = false
        win.error = 'This file could not be opened here.'
      }
    },

    async render(win) {
      let document_ = documents.get(win.key)

      if (! document_) return

      let canvas = await this.canvasFor(win)

      if (! canvas) return

      // The space left once the details panel and the chrome have had theirs.
      let panel = win.showInfo ? 288 : 0

      await drawPage({
        pdf: document_,
        number: win.page,
        canvas,
        width: Math.max(200, win.width - panel - 32),
        height: Math.max(200, win.height - 96),
        // Fitted to the window unless the reader has zoomed.
        fit: win.fit,
        scale: win.scale,
      })
    },

    /** The window's canvas, once Alpine has put it on the page. */
    async canvasFor(win) {
      for (let attempt = 0; attempt < 10; attempt++) {
        let canvas = document.querySelector(`canvas[data-canvas="${CSS.escape(win.key)}"]`)

        if (canvas) return canvas

        await new Promise((resolve) => setTimeout(resolve, 50))
      }

      return null
    },

    async go(win, delta) {
      let next = win.page + delta

      if (next < 1 || next > win.pages) return

      win.page = next
      await this.render(win)
    },

    async zoom(win, factor) {
      // The first zoom starts from wherever the fitted view had got to.
      win.scale = Math.min(3, Math.max(0.3, win.scale * factor))
      win.fit = false
      await this.render(win)
    },

    async fitToWindow(win) {
      win.fit = true
      win.scale = 1
      await this.render(win)
    },
  }))
})
