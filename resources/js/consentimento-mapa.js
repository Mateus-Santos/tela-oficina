/* Consentimento apenas para o mapa externo. Cookies de sessao/CSRF permanecem ativos. */
(() => {
    const CHAVE = 'sos_privacidade_google_maps_v1';

    function iniciar() {
        const mapa = document.querySelector('[data-sos-map]');
        const aviso = document.querySelector('[data-sos-cookie-banner]');
        if (!mapa || !aviso) return;

        const placeholder = mapa.querySelector('[data-sos-map-placeholder]');
        const preferencias = mapa.querySelector('[data-sos-map-preferences]');
        if (!placeholder || !preferencias) return;

        function lerPreferencia() {
            try {
                const valor = window.localStorage.getItem(CHAVE);
                return valor === 'permitido' || valor === 'recusado' ? valor : null;
            } catch (_) {
                return null;
            }
        }

        let escolha = lerPreferencia();
        let retornandoDasPreferencias = false;

        function atualizarMapa() {
            const permitido = escolha === 'permitido';
            const existente = mapa.querySelector('iframe[data-sos-map-frame]');

            if (permitido && !existente) {
                const url = mapa.getAttribute('data-sos-map-src');
                if (!url || !url.startsWith('https://www.google.com/maps?')) return;

                const iframe = document.createElement('iframe');
                iframe.setAttribute('data-sos-map-frame', '');
                iframe.title = 'Mapa de localizacao da Oficina SOS Mecanica';
                iframe.width = '100%';
                iframe.height = '310';
                iframe.loading = 'lazy';
                iframe.referrerPolicy = 'no-referrer-when-downgrade';
                iframe.allowFullscreen = true;
                iframe.src = url; // SOMENTE depois da escolha positiva.
                mapa.insertBefore(iframe, preferencias);
            } else if (!permitido && existente) {
                existente.remove();
            }

            placeholder.hidden = permitido;
        }

        function salvarPreferencia(valor) {
            escolha = valor;
            try {
                window.localStorage.setItem(CHAVE, valor);
            } catch (_) {
                // Sem armazenamento: a escolha vale nesta pagina.
            }
            atualizarMapa();
            aviso.hidden = true;
            if (retornandoDasPreferencias) {
                preferencias.focus();
                retornandoDasPreferencias = false;
            }
        }

        document.querySelectorAll('[data-sos-map-choice]').forEach(botao => {
            botao.addEventListener('click', () => {
                salvarPreferencia(
                    botao.getAttribute('data-sos-map-choice') === 'permitir'
                        ? 'permitido' : 'recusado'
                );
            });
        });

        preferencias.addEventListener('click', () => {
            retornandoDasPreferencias = true;
            aviso.hidden = false;
            const rejeitar = aviso.querySelector('[data-sos-map-choice="recusar"]');
            rejeitar?.focus();
        });

        atualizarMapa();
        aviso.hidden = escolha !== null;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar, { once: true });
    } else {
        iniciar();
    }
})();
