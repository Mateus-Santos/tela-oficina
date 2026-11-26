// FEUX LOTE 02: somente contador visual. Nao altera campos, CSRF, envio ou calculos.
(function () {
    function iniciar() {
        const form = document.getElementById('form-os-itens');
        const tbody = document.getElementById('container-itens-dinamicos');
        const label = document.getElementById('feux-notas-contador');
        if (!form || !tbody || !label || form.dataset.feuxNotasContador === '1') return;
        form.dataset.feuxNotasContador = '1';

        let anterior = -1;
        function atualizar() {
            const total = tbody.querySelectorAll('tr[data-item-index]').length;
            if (total === anterior) return;
            anterior = total;
            label.textContent = `(${total})`;
            label.setAttribute('aria-label', `${total} ${total === 1 ? 'item selecionado' : 'itens selecionados'}`);
        }
        atualizar();
        new MutationObserver(atualizar).observe(tbody, { childList: true, subtree: true });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar, { once: true });
    } else {
        iniciar();
    }
})();
