// FEUX L06: feedback visual do envio nativo. Nao intercepta nem impede o submit.
(function () {
    'use strict';

    function iniciar() {
        const form = document.getElementById('form-os-itens');
        const botao = document.getElementById('btn-salvar-nota');
        const status = document.querySelector('.feux-notas-envio-status');
        if (!form || !botao || !status || form.dataset.feuxNotasLote06 === '1') return;
        form.dataset.feuxNotasLote06 = '1';

        function restaurar() {
            delete form.dataset.feuxEnviando;
            botao.removeAttribute('aria-busy');
            status.textContent = '';
        }

        form.addEventListener('submit', function () {
            // O navegador permanece responsavel pela validacao HTML nativa.
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) return;
            form.dataset.feuxEnviando = '1';
            botao.setAttribute('aria-busy', 'true');
            status.textContent = 'Enviando os dados da nota.';
        });

        // Se o navegador restaurar o formulario pelo historico, liberar as acoes.
        window.addEventListener('pageshow', restaurar);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar, { once: true });
    } else {
        iniciar();
    }
})();
