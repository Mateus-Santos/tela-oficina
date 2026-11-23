// FEUX L04 — navegação por teclado nos resultados da busca de Notas.
// Não consulta APIs, não seleciona nem grava itens automaticamente.
(function () {
    'use strict';

    function iniciar() {
        const modal = document.getElementById('modalAdicionarItem');
        const busca = document.getElementById('builder_item_busca');
        const resultados = document.getElementById('builder_resultados');
        if (!modal || !busca || !resultados || modal.dataset.feuxNotasTeclado === '1') return;
        modal.dataset.feuxNotasTeclado = '1';

        function opcoes() {
            return Array.from(resultados.querySelectorAll('button.list-group-item-action:not(:disabled)'));
        }

        busca.addEventListener('keydown', function (event) {
            if (event.key !== 'ArrowDown' || busca.disabled) return;
            const primeira = opcoes()[0];
            if (!primeira) return;
            event.preventDefault();
            primeira.focus();
        });

        resultados.addEventListener('keydown', function (event) {
            const teclas = ['ArrowDown', 'ArrowUp', 'Home', 'End'];
            if (!teclas.includes(event.key)) return;
            const botao = event.target.closest?.('button.list-group-item-action');
            if (!botao || !resultados.contains(botao)) return;
            const botoes = opcoes();
            const indice = botoes.indexOf(botao);
            if (indice === -1 || botoes.length === 0) return;

            event.preventDefault();
            if (event.key === 'ArrowUp' && indice === 0) {
                busca.focus();
                return;
            }
            const proximo = event.key === 'Home' ? 0
                : event.key === 'End' ? botoes.length - 1
                : Math.max(0, Math.min(botoes.length - 1,
                    indice + (event.key === 'ArrowDown' ? 1 : -1)));
            botoes[proximo].focus();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar, { once: true });
    } else {
        iniciar();
    }
})();
