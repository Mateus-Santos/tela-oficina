/* FEUX Notas Workspace L02 — navegador de itens existentes.
 * Filtro estritamente visual: tr permanece no DOM e entra no submit nativo.
 * Nao altera calculos, estoques, dados do item, inclusao ou remocao. */
(function () {
    'use strict';

    function normalizar(valor) {
        return String(valor ?? '').normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR').trim();
    }

    function classificar(linha) {
        const campo = linha.querySelector('input.itemable-type');
        const tipo = String(linha.dataset.itemableType || (campo ? campo.value : '') || '');
        if (tipo.includes('Produto')) return 'produto';
        if (tipo.includes('OrdemServico')) return 'os';
        return 'outro';
    }

    function textoPesquisavel(linha) {
        const campos = Array.from(linha.querySelectorAll('input:not([type="hidden"]), textarea'));
        const valores = campos.map(campo => campo.value || '').join(' ');
        return normalizar(`${linha.textContent || ''} ${valores}`);
    }

    function iniciar() {
        const tela = document.querySelector('.feux-workspace-redesign');
        if (!tela || tela.dataset.feuxItensOperacao === '1') return;
        const tabela = tela.querySelector('#tabela-itens-os');
        const corpo = tela.querySelector('#container-itens-dinamicos');
        const wrapper = tabela && tabela.closest('.feux-notas-itens-mobile');
        const destino = wrapper && wrapper.parentElement;
        if (!tabela || !corpo || !wrapper || !destino) return;
        tela.dataset.feuxItensOperacao = '1';

        const barra = document.createElement('section');
        barra.className = 'feux-notas-operacao';
        barra.setAttribute('aria-label', 'Gerenciador visual de itens da nota');
        barra.innerHTML = `
            <div class="feux-notas-operacao__topo">
                <div>
                    <p class="feux-notas-operacao__eyebrow">Área de trabalho</p>
                    <h3 class="feux-notas-operacao__titulo">Gerenciar itens</h3>
                    <p class="feux-notas-operacao__ajuda">Localize e edite os itens sem perder o contexto da nota.</p>
                </div>
                <div class="feux-notas-operacao__total" aria-live="polite">
                    <strong data-feux-total-itens>0</strong><span>itens na nota</span>
                </div>
            </div>
            <div class="feux-notas-operacao__ferramentas">
                <label class="feux-notas-operacao__busca">
                    <span><i class="bi bi-search" aria-hidden="true"></i> Localizar item já adicionado</span>
                    <input data-feux-busca-itens type="search" autocomplete="off" placeholder="Produto, serviço, descrição ou código…" aria-label="Pesquisar nos itens já adicionados">
                </label>
                <div class="feux-notas-operacao__filtros" role="group" aria-label="Tipo de item">
                    <button type="button" data-feux-tipo="todos" aria-pressed="true">Todos</button>
                    <button type="button" data-feux-tipo="produto" aria-pressed="false">Produtos</button>
                    <button type="button" data-feux-tipo="os" aria-pressed="false">Serviços / O.S.</button>
                </div>
            </div>
            <div class="feux-notas-operacao__rodape">
                <p class="feux-notas-operacao__status" data-feux-status-itens role="status" aria-live="polite"></p>
                <button type="button" data-feux-editar-primeiro class="feux-notas-operacao__atalho">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i> Editar primeiro resultado
                </button>
            </div>
            <p class="feux-notas-operacao__informacao">O filtro apenas organiza a visualização. Todos os itens continuam na nota e no total.</p>
        `;
        destino.insertBefore(barra, wrapper);
        const busca = barra.querySelector('[data-feux-busca-itens]');
        const filtros = Array.from(barra.querySelectorAll('[data-feux-tipo]'));
        const total = barra.querySelector('[data-feux-total-itens]');
        const status = barra.querySelector('[data-feux-status-itens]');
        const editar = barra.querySelector('[data-feux-editar-primeiro]');
        let tipoAtivo = 'todos';
        let quantidadeAnterior = corpo.querySelectorAll('tr[data-item-index]').length;

        function filtrar() {
            const linhas = Array.from(corpo.querySelectorAll('tr[data-item-index]'));
            const texto = normalizar(busca.value);
            let visiveis = 0;
            for (const linha of linhas) {
                const tipoConfere = tipoAtivo === 'todos' || classificar(linha) === tipoAtivo;
                const textoConfere = !texto || textoPesquisavel(linha).includes(texto);
                linha.hidden = !(tipoConfere && textoConfere);
                if (!linha.hidden) visiveis++;
            }
            total.textContent = String(linhas.length);
            status.textContent = `${visiveis} de ${linhas.length} ${linhas.length === 1 ? 'item visível' : 'itens visíveis'}`;
            if (!visiveis && linhas.length) status.textContent += ' — nenhum item corresponde ao filtro.';
            editar.disabled = visiveis === 0;
            for (const botao of filtros) {
                botao.setAttribute('aria-pressed', String(botao.dataset.feuxTipo === tipoAtivo));
            }
        }

        busca.addEventListener('input', filtrar);
        busca.addEventListener('keydown', event => {
            if (event.key !== 'Escape' || !busca.value) return;
            busca.value = '';
            filtrar();
            event.stopPropagation();
        });
        for (const botao of filtros) {
            botao.addEventListener('click', () => {
                tipoAtivo = botao.dataset.feuxTipo;
                filtrar();
            });
        }
        editar.addEventListener('click', () => {
            const primeira = Array.from(corpo.querySelectorAll('tr[data-item-index]'))
                .find(linha => !linha.hidden);
            const campo = primeira && primeira.querySelector('.input-descricao, .input-qtd');
            if (!campo) return;
            campo.focus();
            if (typeof campo.scrollIntoView === 'function') {
                campo.scrollIntoView({ block: 'center', behavior: 'auto' });
            }
        });

        if (typeof MutationObserver !== 'undefined') {
            new MutationObserver(() => {
                const quantidadeAtual = corpo.querySelectorAll('tr[data-item-index]').length;
                // Novo item deve ficar visivel mesmo quando havia um filtro em uso.
                if (quantidadeAtual > quantidadeAnterior) {
                    tipoAtivo = 'todos';
                    busca.value = '';
                }
                quantidadeAnterior = quantidadeAtual;
                filtrar();
            }).observe(corpo, { childList: true });
        }
        filtrar();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar, { once: true });
    } else {
        iniciar();
    }
})();
