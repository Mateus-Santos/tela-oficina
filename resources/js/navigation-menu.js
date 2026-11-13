// Bootstrap continua sendo a unica fonte de verdade para Collapse/Dropdown.
// Este modulo so fecha o menu recolhido quando a navegacao exige.
const iniciarNavegacao = () => {
  const nav = document.getElementById('navbarNavDropdown')
  if (!nav) return

  const toggler = document.querySelector('[aria-controls="navbarNavDropdown"]')
  const telaRecolhida = () => window.matchMedia('(max-width: 1199.98px)').matches
  const recolher = () => {
    if (!nav.classList.contains('show')) return
    const Collapse = window.bootstrap?.Collapse
    if (!Collapse) return
    Collapse.getOrCreateInstance(nav, { toggle: false }).hide()
  }

  nav.addEventListener('click', event => {
    if (!telaRecolhida() ||
        (event.button !== undefined && event.button !== 0) ||
        event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return

    const link = event.target.closest('a[href]')
    if (!link || !nav.contains(link)) return

    const destino = new URL(link.getAttribute('href'), window.location.href)
    const atual = new URL(window.location.href)
    if (destino.origin !== atual.origin ||
        destino.pathname !== atual.pathname ||
        destino.search !== atual.search ||
        !destino.hash) return

    recolher()
  })

  // Capture no documento: apos o clique o foco fica no botao, fora do nav.
  // Nao capturar Escape de modais, formularios ou menus de outros componentes.
  // Com dropdown aberto, Bootstrap recebe Escape e fecha somente o dropdown.
  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape' || !telaRecolhida() ||
        !nav.classList.contains('show')) return

    const origem = event.target
    const ativo = document.activeElement
    const dentroDoMenu = element => element === toggler || nav.contains(element)
    if (!dentroDoMenu(origem) && !dentroDoMenu(ativo)) return
    if (nav.querySelector('.dropdown-menu.show')) return

    recolher()
    toggler?.focus()
    event.preventDefault()
  }, true)
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', iniciarNavegacao, { once: true })
} else {
  iniciarNavegacao()
}
