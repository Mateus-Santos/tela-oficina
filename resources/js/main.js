(function() {

  //Função de selecionar o "icone".
  const select = (el, all = false) => {
    el = el.trim()
    if (all) {
      return [...document.querySelectorAll(el)]
    } else {
      return document.querySelector(el)
    }
  }

  //Função para carregamento das telas.

  const on = (type, el, listener, all = false) => {
    let selectEl = select(el, all)
    if (selectEl) {
      if (all) {
        selectEl.forEach(e => e.addEventListener(type, listener))
      } else {
        selectEl.addEventListener(type, listener)
      }
    }
  }

  //Para carregamento das telas.

  const onscroll = (el, listener) => {
    el.addEventListener('scroll', listener)
  }
  
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
    onscroll(document, headerScrolled)
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
    onscroll(document, toggleBacktotop)
  }

  // Nao interceptar links que apontam para outra pagina.
  on('click', '.scrollto', function(e) {
    const targetUrl = new URL(this.href, window.location.href)
    const currentUrl = new URL(window.location.href)
    if (targetUrl.origin !== currentUrl.origin ||
        targetUrl.pathname !== currentUrl.pathname ||
        targetUrl.search !== currentUrl.search ||
        !targetForHash(targetUrl.hash)) return

    e.preventDefault()
    scrollto(targetUrl.hash)
  }, true)

  window.addEventListener('load', () => {
    if (targetForHash(window.location.hash)) {
      scrollto(window.location.hash)
    }
  });

  let preloader = select('#preloader');
  if (preloader) {
    window.addEventListener('load', () => {
      preloader.remove()
    });
  }

})()
