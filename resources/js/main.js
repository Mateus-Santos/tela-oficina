(function() {
  // Consultas simples ao DOM nao precisam de wrappers de eventos globais.
  const select = selector => document.querySelector(selector.trim())

  // Hashes de URLs sao identificadores, nao seletores CSS arbitrarios.
  const targetForHash = (hash) => {
    if (!hash || hash === '#') return null
    try {
      return document.getElementById(decodeURIComponent(hash.slice(1)))
    } catch {
      return null
    }
  }

  const scrollto = (hash) => {
    const target = targetForHash(hash)
    if (!target) return

    const header = select('#header')
    const offset = header ? header.offsetHeight : 0
    const elementPos = target.getBoundingClientRect().top + window.scrollY
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches

    window.scrollTo({
      top: elementPos - offset,
      behavior: reducedMotion ? 'auto' : 'smooth'
    })
  }

  /**
   * Adicionar  
   */
  let selectHeader = select('#header')
  if (selectHeader) {
    const headerScrolled = () => {
      if (window.scrollY > 100) {
        selectHeader.classList.add('header-scrolled')
      } else {
        selectHeader.classList.remove('header-scrolled')
      }
    }
    window.addEventListener('load', headerScrolled)
    document.addEventListener('scroll', headerScrolled, { passive: true })
  }

  let backtotop = select('.back-to-top')
  if (backtotop) {
    const toggleBacktotop = () => {
      if (window.scrollY > 100) {
        backtotop.classList.add('active')
      } else {
        backtotop.classList.remove('active')
      }
    }
    window.addEventListener('load', toggleBacktotop)
    document.addEventListener('scroll', toggleBacktotop, { passive: true })
  }

  // Nao interceptar links que apontam para outra pagina.
  document.querySelectorAll('.scrollto').forEach(link => {
    link.addEventListener('click', function (event) {
      const targetUrl = new URL(this.href, window.location.href)
      const currentUrl = new URL(window.location.href)
      if (targetUrl.origin !== currentUrl.origin ||
          targetUrl.pathname !== currentUrl.pathname ||
          targetUrl.search !== currentUrl.search ||
          !targetForHash(targetUrl.hash)) return

      event.preventDefault()
      scrollto(targetUrl.hash)
    })
  })

  window.addEventListener('load', () => {
    if (targetForHash(window.location.hash)) {
      scrollto(window.location.hash)
    }
  });

  // Liberar a pagina quando o DOM estiver pronto. Esperar window.load pode
  // deixar esta camada sobre os botoes por causa de recursos externos lentos.
  const preloader = select('#preloader');
  if (preloader) {
    const removerPreloader = () => preloader.remove()
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', removerPreloader, { once: true })
    } else {
      removerPreloader()
    }
  }

})()
