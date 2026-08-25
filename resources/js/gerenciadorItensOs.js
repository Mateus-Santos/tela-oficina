window.recalcularTotalGeral = function () {
    let brutoProdutos = 0;
    let descontoProdutos = 0;

    let brutoServicos = 0;
    let descontoServicos = 0;

    const linhas = document.querySelectorAll('.item-row');

    linhas.forEach(linha => {
        const inputType = linha.querySelector('input[name*="[itemable_type]"]');
        if (!inputType) return;

        // Aceita "Produto" independente das barras enviadas pelo servidor
        const isProduto = inputType.value.includes('Produto');

        const elQtd = linha.querySelector('.input-qtd');
        const elVunit = linha.querySelector('.input-vunit');
        const elDescItem = linha.querySelector('.input-desc-val');

        const qtd = elQtd ? parseFloat(elQtd.value) || 0 : 0;
        const vunit = elVunit ? parseFloat(elVunit.value) || 0 : 0;
        const descItem = elDescItem ? parseFloat(elDescItem.value) || 0 : 0;

        const subtotalLinhaBruto = qtd * vunit;

        if (isProduto) {
            brutoProdutos += subtotalLinhaBruto;
            descontoProdutos += descItem;
        } else {
            brutoServicos += subtotalLinhaBruto;
            descontoServicos += descItem;
        }
    });

    // Cálculos dos Subtotais Líquidos
    const liquidoProdutos = Math.max(0, brutoProdutos - descontoProdutos);
    const liquidoServicos = Math.max(0, brutoServicos - descontoServicos);
    const totalDescontos = descontoProdutos + descontoServicos;
    const valorTotalNota = liquidoProdutos + liquidoServicos;

    // Função de formatação para R$
    const fmt = (val) => 'R$ ' + val.toFixed(2).replace('.', ',');

    // Atualização dos elementos da DOM (Peças)
    const elPecasBruto = document.getElementById('resumo-pecas-bruto');
    const elPecasDesc = document.getElementById('resumo-pecas-desconto');
    const elPecasLiq = document.getElementById('resumo-pecas-liquido');

    if (elPecasBruto) elPecasBruto.innerText = fmt(brutoProdutos);
    if (elPecasDesc) elPecasDesc.innerText = fmt(descontoProdutos);
    if (elPecasLiq) elPecasLiq.innerText = fmt(liquidoProdutos);

    // Atualização dos elementos da DOM (Serviços)
    const elServicosBruto = document.getElementById('resumo-servicos-bruto');
    const elServicosDesc = document.getElementById('resumo-servicos-desconto');
    const elServicosLiq = document.getElementById('resumo-servicos-liquido');

    if (elServicosBruto) elServicosBruto.innerText = fmt(brutoServicos);
    if (elServicosDesc) elServicosDesc.innerText = fmt(descontoServicos);
    if (elServicosLiq) elServicosLiq.innerText = fmt(liquidoServicos);

    // Atualização Totais Gerais
    const elTotalDesc = document.getElementById('resumo-total-descontos');
    const elValorGeralOs = document.getElementById('valor-geral-os');

    if (elTotalDesc) elTotalDesc.innerText = fmt(totalDescontos);
    if (elValorGeralOs) elValorGeralOs.innerText = fmt(valorTotalNota);
};

document.addEventListener('DOMContentLoaded', () => {

    const typeSelect = document.getElementById('builder_type');
    const itemIdSelect = document.getElementById('builder_item_id');
    const valorUnitInput = document.getElementById('builder_valor_unitario');
    const descInput = document.getElementById('builder_descricao');
    const qtdInput = document.getElementById('builder_quantidade');
    const descontoInput = document.getElementById('builder_desconto');
    const garantiaInput = document.getElementById('builder_garantia');
    const btnAdicionar = document.getElementById('btn-adicionar-item');

    const containerItens = document.getElementById('container-itens-dinamicos');
    const linhaVazia = document.getElementById('linha-vazia');

    // Elementos do Modal
    const modalDescPecasValor = document.getElementById('modal-desc-pecas-valor');
    const modalDescPecasPorcent = document.getElementById('modal-desc-pecas-porcent');
    const modalDescServicosValor = document.getElementById('modal-desc-servicos-valor');
    const modalDescServicosPorcent = document.getElementById('modal-desc-servicos-porcent');
    const btnAplicarModal = document.getElementById('btn-aplicar-descontos-modal');
    const modalElement = document.getElementById('modalDescontos');

    let itemIndex = containerItens
        ? containerItens.querySelectorAll('tr.item-row').length
        : 0;


    // ============================================================
    // SELEÇÃO DE TIPO DE ITEM
    // ============================================================

    if (typeSelect) {

        typeSelect.addEventListener('change', function () {

            const type = this.value;

            itemIdSelect.innerHTML =
                '<option value="">Selecione um item...</option>';

            // Limpa descrição e valor ao trocar o tipo
            if (valorUnitInput) {
                valorUnitInput.value = '';
            }

            if (descInput) {
                descInput.value = '';
            }

            if (!type) {
                itemIdSelect.disabled = true;
                return;
            }


            // ========================================================
            // PRODUTO
            // ========================================================

            if (type.includes('Produto')) {

                const sourceSelect =
                    document.getElementById('produtos_estatiticos_local');

                if (sourceSelect && sourceSelect.options.length > 0) {

                    Array.from(sourceSelect.options).forEach(option => {

                        const opt = document.createElement('option');

                        opt.value = option.value;
                        opt.textContent = option.textContent;
                        opt.dataset.preco =
                            option.dataset.preco || 0;

                        itemIdSelect.appendChild(opt);
                    });

                    itemIdSelect.disabled = false;

                } else {

                    itemIdSelect.innerHTML =
                        '<option value="">Nenhum produto encontrado</option>';

                    itemIdSelect.disabled = true;
                }

                return;
            }


            // ========================================================
            // ORDEM DE SERVIÇO
            // ========================================================

            if (type.includes('OrdemServico')) {

                const ordensServico =
                    window.ordensServicoVeiculo || [];

                if (ordensServico.length === 0) {

                    itemIdSelect.innerHTML =
                        '<option value="">Nenhuma O.S encontrada para este veículo</option>';

                    itemIdSelect.disabled = true;

                    return;
                }

                ordensServico.forEach(os => {

                    const opt = document.createElement('option');

                    opt.value = os.id;

                    opt.textContent =
                        os.descricao ||
                        `OS #${os.id}`;

                    // A API atual ainda não envia o valor da O.S
                    opt.dataset.preco = 0;

                    itemIdSelect.appendChild(opt);
                });

                itemIdSelect.disabled = false;

                return;
            }

            itemIdSelect.disabled = true;
        });
    }


    // ============================================================
    // SELEÇÃO DO ITEM
    // ============================================================

    if (itemIdSelect) {

        itemIdSelect.addEventListener('change', function () {

            const selectedOption =
                this.options[this.selectedIndex];

            if (selectedOption && selectedOption.value) {

                const preco =
                    selectedOption.dataset.preco || 0;

                const descricao =
                    selectedOption.textContent.trim();

                if (valorUnitInput) {
                    valorUnitInput.value =
                        parseFloat(preco).toFixed(2);
                }

                if (descInput) {
                    descInput.value = descricao;
                }
            }
        });
    }


    // ============================================================
    // LER TABELA E CARREGAR VALORES NO MODAL
    // ============================================================

    if (modalElement) {

        modalElement.addEventListener('show.bs.modal', () => {

            let subtotalPecas = 0;
            let descontoPecas = 0;

            let subtotalServicos = 0;
            let descontoServicos = 0;

            document.querySelectorAll('.item-row').forEach(linha => {

                const inputType =
                    linha.querySelector(
                        'input[name*="[itemable_type]"]'
                    );

                if (!inputType) return;

                const isProduto =
                    inputType.value.includes('Produto');

                const qtd =
                    parseFloat(
                        linha.querySelector('.input-qtd')?.value
                    ) || 0;

                const vunit =
                    parseFloat(
                        linha.querySelector('.input-vunit')?.value
                    ) || 0;

                const desc =
                    parseFloat(
                        linha.querySelector('.input-desc-val')?.value
                    ) || 0;

                const subtotalItem = qtd * vunit;

                if (isProduto) {

                    subtotalPecas += subtotalItem;
                    descontoPecas += desc;

                } else {

                    subtotalServicos += subtotalItem;
                    descontoServicos += desc;
                }
            });

            if (modalDescPecasValor) {
                modalDescPecasValor.value =
                    descontoPecas.toFixed(2);
            }

            if (modalDescPecasPorcent) {
                modalDescPecasPorcent.value =
                    subtotalPecas > 0
                        ? ((descontoPecas / subtotalPecas) * 100).toFixed(2)
                        : '0.00';
            }

            if (modalDescServicosValor) {
                modalDescServicosValor.value =
                    descontoServicos.toFixed(2);
            }

            if (modalDescServicosPorcent) {
                modalDescServicosPorcent.value =
                    subtotalServicos > 0
                        ? ((descontoServicos / subtotalServicos) * 100).toFixed(2)
                        : '0.00';
            }
        });
    }


    // ============================================================
    // CÁLCULOS DINÂMICOS NO MODAL
    // ============================================================

    function recalcularValoresModal(tipo, origem) {

        let subtotalTipo = 0;

        document.querySelectorAll('.item-row').forEach(linha => {

            const inputType =
                linha.querySelector(
                    'input[name*="[itemable_type]"]'
                );

            if (!inputType) return;

            const isProduto =
                inputType.value.includes('Produto');

            if (
                (tipo === 'pecas' && isProduto) ||
                (tipo === 'servicos' && !isProduto)
            ) {

                const qtd =
                    parseFloat(
                        linha.querySelector('.input-qtd')?.value
                    ) || 0;

                const vunit =
                    parseFloat(
                        linha.querySelector('.input-vunit')?.value
                    ) || 0;

                subtotalTipo += qtd * vunit;
            }
        });

        const inputVal =
            tipo === 'pecas'
                ? modalDescPecasValor
                : modalDescServicosValor;

        const inputPct =
            tipo === 'pecas'
                ? modalDescPecasPorcent
                : modalDescServicosPorcent;

        if (!inputVal || !inputPct) return;

        if (origem === 'valor') {

            const val =
                parseFloat(inputVal.value) || 0;

            inputPct.value =
                subtotalTipo > 0
                    ? ((val / subtotalTipo) * 100).toFixed(2)
                    : '0.00';

        } else if (origem === 'porcentagem') {

            const pct =
                parseFloat(inputPct.value) || 0;

            inputVal.value =
                ((subtotalTipo * pct) / 100).toFixed(2);
        }
    }


    if (modalDescPecasValor) {
        modalDescPecasValor.addEventListener(
            'input',
            () => recalcularValoresModal('pecas', 'valor')
        );
    }

    if (modalDescPecasPorcent) {
        modalDescPecasPorcent.addEventListener(
            'input',
            () => recalcularValoresModal('pecas', 'porcentagem')
        );
    }

    if (modalDescServicosValor) {
        modalDescServicosValor.addEventListener(
            'input',
            () => recalcularValoresModal('servicos', 'valor')
        );
    }

    if (modalDescServicosPorcent) {
        modalDescServicosPorcent.addEventListener(
            'input',
            () => recalcularValoresModal('servicos', 'porcentagem')
        );
    }


    // ============================================================
    // APLICAR DESCONTO PROPORCIONAL AOS ITENS
    // ============================================================

    if (btnAplicarModal) {

        btnAplicarModal.addEventListener('click', () => {

            const descPecasPct =
                parseFloat(modalDescPecasPorcent?.value) || 0;

            const descServicosPct =
                parseFloat(modalDescServicosPorcent?.value) || 0;

            document.querySelectorAll('.item-row').forEach(linha => {

                const inputType =
                    linha.querySelector(
                        'input[name*="[itemable_type]"]'
                    );

                if (!inputType) return;

                const isProduto =
                    inputType.value.includes('Produto');

                const pctAplicar =
                    isProduto
                        ? descPecasPct
                        : descServicosPct;

                const elQtd =
                    linha.querySelector('.input-qtd');

                const elVunit =
                    linha.querySelector('.input-vunit');

                const elDescItem =
                    linha.querySelector('.input-desc-val');

                const elVtotal =
                    linha.querySelector('.input-vtotal');

                const qtd =
                    parseFloat(elQtd?.value) || 0;

                const vunit =
                    parseFloat(elVunit?.value) || 0;

                const subtotalBruto =
                    qtd * vunit;

                const descontoProporcional =
                    (subtotalBruto * pctAplicar) / 100;

                const totalComDesconto =
                    Math.max(
                        0,
                        subtotalBruto - descontoProporcional
                    );

                if (elDescItem) {
                    elDescItem.value =
                        descontoProporcional.toFixed(2);
                }

                if (elVtotal) {
                    elVtotal.value =
                        totalComDesconto
                            .toFixed(2)
                            .replace('.', ',');
                }
            });

            window.recalcularTotalGeral();

            if (modalElement && window.bootstrap) {

                const bsModal =
                    bootstrap.Modal.getInstance(modalElement);

                if (bsModal) {
                    bsModal.hide();
                }
            }
        });
    }


    // ============================================================
    // INSERIR NOVO ITEM NA TABELA
    // ============================================================

    if (btnAdicionar) {

        btnAdicionar.addEventListener('click', () => {

            if (
                !typeSelect.value ||
                !itemIdSelect.value ||
                !descInput.value.trim() ||
                !valorUnitInput.value
            ) {

                alert('Preencha os campos obrigatórios do item.');

                return;
            }

            const itemableType =
                typeSelect.value;

            const itemableId =
                itemIdSelect.value;

            const isProduto =
                itemableType.includes('Produto');

            const tipoText =
                isProduto
                    ? 'Produto'
                    : 'Serviço';

            const badgeColor =
                isProduto
                    ? 'bg-info'
                    : 'bg-warning';

            const descricao =
                descInput.value.trim();

            const quantidade =
                parseInt(qtdInput.value) || 1;

            const valorUnitario =
                parseFloat(valorUnitInput.value) || 0;

            const desconto =
                parseFloat(descontoInput.value) || 0;

            const garantiaDias =
                garantiaInput.value
                    ? parseInt(garantiaInput.value)
                    : '';

            const valorTotalItem =
                Math.max(
                    0,
                    (quantidade * valorUnitario) - desconto
                );

            if (linhaVazia) {
                linhaVazia.style.display = 'none';
            }

            const novaLinha =
                document.createElement('tr');

            novaLinha.classList.add('item-row');

            novaLinha.innerHTML = `
                <td>
                    <span class="badge ${badgeColor} text-dark">
                        ${tipoText}
                    </span>

                    <input
                        type="hidden"
                        name="itens[${itemIndex}][itemable_type]"
                        value="${itemableType}"
                    >

                    <input
                        type="hidden"
                        name="itens[${itemIndex}][itemable_id]"
                        value="${itemableId}"
                    >
                </td>

                <td>
                    <input
                        type="text"
                        name="itens[${itemIndex}][descricao]"
                        class="form-control form-control-sm input-desc"
                        value="${descricao}"
                        required
                    >
                </td>

                <td>
                    <input
                        type="number"
                        name="itens[${itemIndex}][quantidade]"
                        class="form-control form-control-sm input-qtd"
                        value="${quantidade}"
                        min="1"
                        step="1"
                        required
                    >
                </td>

                <td>
                    <input
                        type="number"
                        name="itens[${itemIndex}][valor_unitario]"
                        class="form-control form-control-sm input-vunit"
                        value="${valorUnitario.toFixed(2)}"
                        step="0.01"
                        min="0"
                        required
                    >
                </td>

                <td>
                    <input
                        type="number"
                        name="itens[${itemIndex}][desconto]"
                        class="form-control form-control-sm input-desc-val"
                        value="${desconto.toFixed(2)}"
                        step="0.01"
                        min="0"
                    >
                </td>

                <td>
                    <input
                        type="text"
                        class="form-control form-control-sm input-vtotal fw-bold bg-light"
                        value="${valorTotalItem.toFixed(2).replace('.', ',')}"
                        readonly
                    >
                </td>

                <td>
                    <input
                        type="number"
                        name="itens[${itemIndex}][garantia_dias]"
                        class="form-control form-control-sm input-garantia"
                        value="${garantiaDias}"
                        min="0"
                        placeholder="Dias"
                    >
                </td>

                <td class="text-center">
                    <button
                        type="button"
                        class="btn btn-sm btn-danger btn-remover-item"
                        title="Remover Item"
                    >
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;

            containerItens.appendChild(novaLinha);

            itemIndex++;

            window.recalcularTotalGeral();
        });
    }


    // ============================================================
    // REMOÇÃO E EDIÇÃO
    // ============================================================

    if (containerItens) {

        containerItens.addEventListener('click', (e) => {

            if (e.target.closest('.btn-remover-item')) {

                e.target
                    .closest('tr')
                    .remove();

                window.recalcularTotalGeral();
            }
        });


        containerItens.addEventListener('input', (e) => {

            if (
                e.target.classList.contains('input-qtd') ||
                e.target.classList.contains('input-vunit') ||
                e.target.classList.contains('input-desc-val')
            ) {

                const linha =
                    e.target.closest('tr');

                if (linha) {

                    const qtd =
                        parseFloat(
                            linha.querySelector('.input-qtd')?.value
                        ) || 0;

                    const vunit =
                        parseFloat(
                            linha.querySelector('.input-vunit')?.value
                        ) || 0;

                    const desc =
                        parseFloat(
                            linha.querySelector('.input-desc-val')?.value
                        ) || 0;

                    const totalItem =
                        Math.max(
                            0,
                            (qtd * vunit) - desc
                        );

                    const inputTotal =
                        linha.querySelector('.input-vtotal');

                    if (inputTotal) {
                        inputTotal.value =
                            totalItem
                                .toFixed(2)
                                .replace('.', ',');
                    }
                }

                window.recalcularTotalGeral();
            }
        });
    }


    // ============================================================
    // CÁLCULO INICIAL
    // ============================================================

    window.recalcularTotalGeral();
});
