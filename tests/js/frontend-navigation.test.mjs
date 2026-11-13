import test from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const source = path => readFileSync(new URL(path, import.meta.url), 'utf8')

test('layout preserva permissoes e carrega o bundle CSP', () => {
  const layout = source('../../resources/views/layouts/layout.blade.php')
  const app = source('../../resources/js/app.js')
  const vite = source('../../vite.config.js')
  for (const trecho of [
    'auth()->user()->isAdmin()', 'auth()->user()->isCliente()',
    'LiberacaoPortalCliente::class', 'marketing_cliente_acessos',
    'route($item[1])', "url('/logout')", '@guest', '@auth',
  ]) assert.ok(layout.includes(trecho), trecho)
  assert.ok(!layout.includes('<script id="bootstrap-modal-body-portal">'))
  assert.ok(app.includes("import './modal-body-portal'"))
  assert.ok(app.includes("import './navigation-menu'"))
  assert.ok(app.includes("import './main'"))
  assert.ok(!vite.includes("'resources/js/main.js'"))
  assert.ok(layout.includes('aria-current="page"'))
})

test('portal de modais move elementos iniciais e dinamicos', async () => {
  const antigoDocument = globalThis.document
  const antigoHTMLElement = globalThis.HTMLElement
  class FakeElement {
    constructor(modal) {
      this.modal = modal
      this.parentElement = { name: 'aninhado' }
      this.classList = { contains: key => key === 'modal' && this.modal }
    }
  }
  const eventos = new Map()
  const inicial = new FakeElement(true)
  const dinamico = new FakeElement(true)
  const naoModal = new FakeElement(false)
  const body = { appendChild(elemento) { elemento.parentElement = body } }
  globalThis.HTMLElement = FakeElement
  globalThis.document = {
    readyState: 'loading', body,
    querySelectorAll: () => [inicial, naoModal],
    addEventListener: (tipo, callback) => eventos.set(tipo, callback),
  }
  try {
    await import('../../resources/js/modal-body-portal.js?teste=front013')
    assert.ok(eventos.has('DOMContentLoaded'))
    eventos.get('DOMContentLoaded')()
    assert.equal(inicial.parentElement, body)
    assert.notEqual(naoModal.parentElement, body)
    eventos.get('show.bs.modal')({ target: dinamico })
    assert.equal(dinamico.parentElement, body)
  } finally {
    if (antigoDocument === undefined) delete globalThis.document
    else globalThis.document = antigoDocument
    if (antigoHTMLElement === undefined) delete globalThis.HTMLElement
    else globalThis.HTMLElement = antigoHTMLElement
  }
})

test('menu recolhido: Escape no botao fecha; dropdown e foco externo sao preservados', async () => {
  const antigoDocument = globalThis.document
  const antigoWindow = globalThis.window
  const eventos = new Map()
  const eventosNav = new Map()
  let fechamento = 0
  let foco = 0
  let evitados = 0
  let dropdownAberto = false
  const breakpoint = { matches: true }
  const toggler = { focus: () => foco++ }
  const fora = {}
  const nav = {
    classList: { contains: name => name === 'show' },
    contains: element => element?.dentroDoNav === true,
    querySelector: seletor => seletor === '.dropdown-menu.show' && dropdownAberto ? {} : null,
    addEventListener: (tipo, callback) => eventosNav.set(tipo, callback),
  }
  const documento = {
    readyState: 'loading',
    activeElement: toggler,
    getElementById: id => id === 'navbarNavDropdown' ? nav : null,
    querySelector: () => toggler,
    addEventListener: (tipo, callback) => eventos.set(tipo, callback),
  }
  globalThis.document = documento
  globalThis.window = {
    location: { href: 'https://oficina.test/' },
    matchMedia: () => breakpoint,
    bootstrap: { Collapse: { getOrCreateInstance: () => ({ hide: () => fechamento++ }) } },
  }
  const click = href => {
    const link = { dentroDoNav: true, getAttribute: () => href, closest: () => link }
    eventosNav.get('click')({ target: link, button: 0, defaultPrevented: false })
  }
  const escape = target => eventos.get('keydown')({
    key: 'Escape', target,
    preventDefault: () => evitados++,
  })
  try {
    await import('../../resources/js/navigation-menu.js?teste=front017d')
    eventos.get('DOMContentLoaded')()
    assert.ok(eventos.has('keydown'), 'Escape deve ser ouvido no documento')
    click('#servicos')
    assert.equal(fechamento, 1)
    click('https://fornecedor.test/peca#detalhe')
    click('/login')
    assert.equal(fechamento, 1)
    breakpoint.matches = false
    click('#sobre')
    escape(toggler)
    assert.equal(fechamento, 1)
    breakpoint.matches = true
    documento.activeElement = fora
    escape(fora)
    assert.equal(fechamento, 1, 'Escape de fora nao pode fechar o menu')
    documento.activeElement = toggler
    dropdownAberto = true
    escape(toggler)
    assert.equal(fechamento, 1, 'Bootstrap deve fechar primeiro o dropdown')
    dropdownAberto = false
    escape(toggler)
    assert.equal(fechamento, 2, 'Escape com foco no toggler deve fechar')
    assert.equal(foco, 1, 'Foco deve retornar ao toggler')
    assert.equal(evitados, 1)
  } finally {
    if (antigoDocument === undefined) delete globalThis.document
    else globalThis.document = antigoDocument
    if (antigoWindow === undefined) delete globalThis.window
    else globalThis.window = antigoWindow
  }
})

test('preloader libera clique apos DOMContentLoaded sem aguardar window.load', async () => {
  const antigoDocument = globalThis.document
  const antigoWindow = globalThis.window
  const documentEvents = new Map()
  const windowEvents = new Map()
  let removed = 0
  const preloader = { remove: () => removed++ }
  globalThis.document = {
    readyState: 'loading',
    querySelector: seletor => seletor === '#preloader' ? preloader : null,
    querySelectorAll: () => [],
    getElementById: () => null,
    addEventListener: (tipo, fn) => documentEvents.set(tipo, fn),
  }
  globalThis.window = {
    location: { href: 'https://oficina.test/', hash: '' },
    scrollY: 0,
    addEventListener: (tipo, fn) => windowEvents.set(tipo, fn),
    matchMedia: () => ({ matches: false }),
  }
  try {
    await import('../../resources/js/main.js?teste=front017d-preloader')
    assert.equal(removed, 0)
    assert.ok(documentEvents.has('DOMContentLoaded'))
    documentEvents.get('DOMContentLoaded')()
    assert.equal(removed, 1)
    assert.ok(windowEvents.has('load'))
  } finally {
    if (antigoDocument === undefined) delete globalThis.document
    else globalThis.document = antigoDocument
    if (antigoWindow === undefined) delete globalThis.window
    else globalThis.window = antigoWindow
  }
})

test('ancoras validas respeitam header e movimento reduzido; hashes invalidos nao quebram', async () => {
  const antigoDocument = globalThis.document
  const antigoWindow = globalThis.window
  const clicks = new Map()
  const link = {
    href: 'https://oficina.test/#servicos',
    addEventListener: (tipo, callback) => clicks.set(tipo, callback),
  }
  const header = { offsetHeight: 80, classList: { add() {}, remove() {} } }
  const secao = { getBoundingClientRect: () => ({ top: 500 }) }
  const movimentos = []
  const listeners = new Map()
  globalThis.document = {
    querySelector: seletor => seletor === '#header' ? header : null,
    querySelectorAll: seletor => seletor === '.scrollto' ? [link] : [],
    getElementById: id => id === 'servicos' ? secao : null,
    addEventListener: (tipo, callback) => listeners.set(tipo, callback),
  }
  globalThis.window = {
    location: { href: 'https://oficina.test/', hash: '' },
    scrollY: 100,
    addEventListener: (tipo, callback) => listeners.set(tipo, callback),
    matchMedia: () => ({ matches: true }),
    scrollTo: opt => movimentos.push(opt),
  }
  try {
    await import('../../resources/js/main.js?teste=front013')
    let prevenido = false
    clicks.get('click').call(link, { preventDefault() { prevenido = true } })
    assert.equal(prevenido, true)
    assert.deepEqual(movimentos[0], { top: 520, behavior: 'auto' })
    link.href = 'https://fornecedor.test/#servicos'
    clicks.get('click').call(link, { preventDefault() { throw new Error('link externo interceptado') } })
    assert.equal(movimentos.length, 1)
    link.href = 'https://oficina.test/#'
    clicks.get('click').call(link, { preventDefault() { throw new Error('hash vazio interceptado') } })
    assert.equal(movimentos.length, 1)
    link.href = 'https://oficina.test/#%ZZ'
    clicks.get('click').call(link, { preventDefault() { throw new Error('hash malformado interceptado') } })
    assert.equal(movimentos.length, 1)
  } finally {
    if (antigoDocument === undefined) delete globalThis.document
    else globalThis.document = antigoDocument
    if (antigoWindow === undefined) delete globalThis.window
    else globalThis.window = antigoWindow
  }
})
