// FEUX L05: orientacao de busca no modal de Notas, sem afetar selecao ou API.
(function () {
    'use strict';

    function iniciar() {
        const modal = document.getElementById('modalAdicionarItem');
        const seletor = document.getElementById('builder_type');
        const orientacao = modal?.querySelector('.feux-notas-tipo-contexto__texto');
        if (!modal || !seletor || !orientacao || modal.dataset.feuxNotasLote05 === '1') return;
        modal.dataset.feuxNotasLote05 = '1';

        function atualizar() {
            const os = seletor.value === 'os';
            modal.dataset.feuxNotasTipo = os ? 'os' : 'produto';
            orientacao.textContent = os
                ? 'Ordens de serviço: pesquise por número ou descrição. Confira se a O.S. está disponível para vinculação.'
                : 'Produtos: pesquise por nome, código ou descrição e confira preço e estoque.';
        }

        seletor.addEventListener('change', atualizar);
        modal.addEventListener('shown.bs.modal', atualizar);
        atualizar();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar, { once: true });
    } else {
        iniciar();
    }
})();
