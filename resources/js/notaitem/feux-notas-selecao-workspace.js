/* FEUX Notas Workspace L03. Aprimoramento progressivo de interface:
 * seleciona tipo pelo select existente e dispara seu change nativo;
 * nao faz chamadas de rede, submit, persistencia nem alteracoes no calculo. */
(function () {
    'use strict';

    function iniciar() {
        const modal = document.getElementById('modalAdicionarItem');
        const tipo = document.getElementById('builder_type');
        const busca = document.getElementById('builder_item_busca');
        const resultados = document.getElementById('builder_resultados');
        const selecionado = document.getElementById('builder_item_id');
        const descricao = document.getElementById('builder_descricao');
        const quantidade = document.getElementById('builder_quantidade');
        const valor = document.getElementById('builder_valor_unitario');
        const desconto = document.getElementById('builder_desconto');
        const total = document.getElementById('builder_total');
        const coluna = modal?.querySelector('.feux-notas-selecao-configurar');
        if (!modal || !tipo || !busca || !resultados || !selecionado ||
            !descricao || !quantidade || !total || !coluna || modal.dataset.feuxSelecaoWorkspace === '1') return;
        modal.dataset.feuxSelecaoWorkspace = '1';

        // Os botões não duplicam lógica: sempre acionam o select original.
        const tipos = document.createElement('div');
        tipos.className = 'feux-notas-selecao-tipos';
        tipos.setAttribute('role', 'group');
        tipos.setAttribute('aria-label', 'Escolher produto ou ordem de serviço');
        tipos.innerHTML = `
          <button type="button" data-feux-modal-tipo="produto" aria-pressed="false">
            <i class="bi bi-box-seam" aria-hidden="true"></i> Produto
          </button>
          <button type="button" data-feux-modal-tipo="os" aria-pressed="false">
            <i class="bi bi-tools" aria-hidden="true"></i> Serviço / O.S.
          </button>`;
        tipo.insertAdjacentElement('afterend', tipos);
        const botoes = Array.from(tipos.querySelectorAll('[data-feux-modal-tipo]'));
        tipo.classList.add('feux-notas-selecao-nativo');
        tipo.setAttribute('tabindex', '-1');
        tipo.setAttribute('aria-hidden', 'true');

        const resumo = document.createElement('div');
        resumo.className = 'feux-notas-selecao-status';
        resumo.setAttribute('role', 'status');
        resumo.setAttribute('aria-live', 'polite');
        resumo.innerHTML = `<span class="feux-notas-selecao-status__icone" aria-hidden="true"><i class="bi bi-circle-dotted"></i></span>
            <span class="feux-notas-selecao-status__texto">Nenhum item selecionado. Escolha um resultado na coluna de busca.</span>`;
        const statusTexto = resumo.querySelector('.feux-notas-selecao-status__texto');
        coluna.querySelector('.feux-notas-selecao-passo')?.insertAdjacentElement('afterend', resumo);

        const acessos = document.createElement('div');
        acessos.className = 'feux-notas-selecao-quantidade';
        acessos.setAttribute('role', 'group');
        acessos.setAttribute('aria-label', 'Ajustar quantidade');
        acessos.innerHTML = `<button type="button" data-feux-qtd="-1" aria-label="Diminuir quantidade">
            <i class="bi bi-dash" aria-hidden="true"></i></button>
            <span>Quantidade</span>
            <button type="button" data-feux-qtd="1" aria-label="Aumentar quantidade">
            <i class="bi bi-plus" aria-hidden="true"></i></button>`;
        const qtdContainer = quantidade.closest('.col-md-4');
        if (qtdContainer) qtdContainer.appendChild(acessos);
        const botoesQtd = Array.from(acessos.querySelectorAll('[data-feux-qtd]'));

        function refletir() {
            const temItem = String(selecionado.value || '').trim().length > 0;
            const nome = String(descricao.value || '').trim();
            const id = String(selecionado.value || '').trim();
            modal.dataset.feuxItemEscolhido = temItem ? '1' : '0';
            statusTexto.textContent = temItem
                ? `Selecionado: ${nome || (tipo.value === 'os' ? 'Ordem de serviço' : 'Produto')} (código ${id}). Revise os valores abaixo.`
                : 'Nenhum item selecionado. Escolha um resultado na coluna de busca.';
            for (const botao of botoes) {
                botao.setAttribute('aria-pressed', String(botao.dataset.feuxModalTipo === tipo.value));
            }
        }

        for (const botao of botoes) {
            botao.addEventListener('click', () => {
                if (tipo.value !== botao.dataset.feuxModalTipo) {
                    tipo.value = botao.dataset.feuxModalTipo;
                    tipo.dispatchEvent(new Event('change', { bubbles: true }));
                }
                refletir();
                busca.focus();
            });
        }
        for (const botao of botoesQtd) {
            botao.addEventListener('click', () => {
                const delta = Number(botao.dataset.feuxQtd);
                const anterior = Number(quantidade.value);
                const inicial = Number.isFinite(anterior) && anterior >= 1 ? Math.floor(anterior) : 1;
                quantidade.value = String(Math.max(1, inicial + delta));
                quantidade.dispatchEvent(new Event('input', { bubbles: true }));
                quantidade.focus();
                refletir();
            });
        }
        tipo.addEventListener('change', refletir);
        for (const elemento of [descricao, quantidade, valor, desconto]) {
            elemento?.addEventListener('input', refletir);
        }
        resultados.addEventListener('click', refletir);
        modal.addEventListener('shown.bs.modal', () => {
            refletir();
            if (!document.activeElement || document.activeElement === document.body) busca.focus();
        });
        modal.addEventListener('hidden.bs.modal', refletir);
        if (typeof MutationObserver !== 'undefined') {
            const codigoWrapper = document.getElementById('builder_item_codigo_wrapper');
            if (codigoWrapper) {
                new MutationObserver(refletir).observe(codigoWrapper, {
                    childList: true, attributes: true, subtree: true, characterData: true,
                });
            }
        }
        refletir();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar, { once: true });
    } else {
        iniciar();
    }
})();
