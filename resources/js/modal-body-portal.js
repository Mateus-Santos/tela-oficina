// Modais em stacking contexts locais devem ir para o body antes de abrir.
// Extraido do layout para cumprir CSP sem alterar o comportamento original.
const moverModalParaBody = (modal) => {
  if (modal instanceof HTMLElement &&
      modal.classList.contains('modal') &&
      modal.parentElement !== document.body) {
    document.body.appendChild(modal)
  }
}

const iniciarModalBodyPortal = () => {
  document.querySelectorAll('.modal').forEach(moverModalParaBody)
  document.addEventListener('show.bs.modal', event => {
    moverModalParaBody(event.target)
  })
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', iniciarModalBodyPortal, { once: true })
} else {
  iniciarModalBodyPortal()
}
