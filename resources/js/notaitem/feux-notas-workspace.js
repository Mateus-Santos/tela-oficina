// Painel de leitura para Notas: sem novos endpoints, eventos de gravacao ou calculos.
(function () {
    'use strict';
    function iniciar() {
        const tela = document.querySelector('.feux-workspace-redesign');
        if (!tela || tela.dataset.feuxWorkspace === '1') return;
        const formulario = tela.querySelector('#form-os-itens');
        const linhas = tela.querySelector('#container-itens-dinamicos');
        const totalOriginal = tela.querySelector('#valor-geral-os');
        if (!formulario || !linhas || !totalOriginal) return;

        const resumoItens = tela.querySelector('[data-feux-workspace-items]');
        const resumoTotal = tela.querySelector('[data-feux-workspace-total]');
        const resumoCliente = tela.querySelector('[data-feux-workspace-cliente]');
        const resumoVeiculo = tela.querySelector('[data-feux-workspace-veiculo]');
        if (!resumoItens || !resumoTotal || !resumoCliente || !resumoVeiculo) return;
        tela.dataset.feuxWorkspace = '1';

        function atualizar() {
            const quantidade = linhas.querySelectorAll('tr[data-item-index]').length;
            const valor = totalOriginal.textContent.trim();
            resumoItens.textContent = `${quantidade} ${quantidade === 1 ? 'item' : 'itens'}`;
            resumoTotal.textContent = valor || 'R$ 0,00';
            const cliente = formulario.querySelector('input[name="cliente_id"]');
            const veiculo = formulario.querySelector('input[name="veiculo_cliente_id"]');
            resumoCliente.textContent = cliente && cliente.value ? 'Selecionado' : 'Venda de balcão';
            resumoVeiculo.textContent = veiculo && veiculo.value ? 'Selecionado' : 'Não informado';
        }

        // Observa apenas fontes existentes; nunca o proprio painel (sem loops).
        if (typeof MutationObserver !== 'undefined') {
            const observar = (alvo, opcoes) => {
                if (alvo) new MutationObserver(atualizar).observe(alvo, opcoes);
            };
            observar(linhas, { childList: true, subtree: true });
            observar(totalOriginal, { childList: true, characterData: true, subtree: true });
            observar(tela.querySelector('.feux-nota-seletores'), { childList: true, subtree: true, attributes: true, attributeFilter: ['value'] });
        }
        tela.addEventListener('input', atualizar);
        tela.addEventListener('change', atualizar);
        document.addEventListener('livewire:navigated', atualizar);
        atualizar();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar, { once: true });
    } else iniciar();
})();
