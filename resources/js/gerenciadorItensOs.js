window.recalcularTotalGeral = function () {
    let brutoProdutos = 0;
    let descontoProdutos = 0;
    let brutoServicos = 0;
    let descontoServicos = 0;

    const linhas = document.querySelectorAll('.item-row');

    linhas.forEach((linha) => {
        const inputType = linha.querySelector(
            'input[name*="[itemable_type]"]'
        );

        if (!inputType) {
            return;
        }

        const isProduto = inputType.value.includes('Produto');

        const elQtd = linha.querySelector('.input-qtd');
        const elVunit = linha.querySelector('.input-vunit');
        const elDescItem = linha.querySelector('.input-desc-val');

        const qtd = elQtd
            ? parseFloat(elQtd.value) || 0
            : 0;

        const vunit = elVunit
            ? parseFloat(elVunit.value) || 0
            : 0;

        const descItem = elDescItem
            ? parseFloat(elDescItem.value) || 0
            : 0;

        const subtotalLinhaBruto = qtd * vunit;

        if (isProduto) {
            brutoProdutos += subtotalLinhaBruto;
            descontoProdutos += descItem;
        } else {
            brutoServicos += subtotalLinhaBruto;
            descontoServicos += descItem;
        }
    });

    const liquidoProdutos = Math.max(
        0,
        brutoProdutos - descontoProdutos
    );

    const liquidoServicos = Math.max(
        0,
        brutoServicos - descontoServicos
    );

    const totalDescontos =
        descontoProdutos +
        descontoServicos;

    const valorTotalNota =
        liquidoProdutos +
        liquidoServicos;

    const fmt = (valor) => {
        return 'R$ ' +
            valor
                .toFixed(2)
                .replace('.', ',');
    };

    const elPecasBruto =
        document.getElementById('resumo-pecas-bruto');

    const elPecasDesc =
        document.getElementById('resumo-pecas-desconto');

    const elPecasLiq =
        document.getElementById('resumo-pecas-liquido');

    if (elPecasBruto) {
        elPecasBruto.innerText = fmt(brutoProdutos);
    }

    if (elPecasDesc) {
        elPecasDesc.innerText = fmt(descontoProdutos);
    }

    if (elPecasLiq) {
        elPecasLiq.innerText = fmt(liquidoProdutos);
    }

    const elServicosBruto =
        document.getElementById('resumo-servicos-bruto');

    const elServicosDesc =
        document.getElementById('resumo-servicos-desconto');

    const elServicosLiq =
        document.getElementById('resumo-servicos-liquido');

    if (elServicosBruto) {
        elServicosBruto.innerText = fmt(brutoServicos);
    }

    if (elServicosDesc) {
        elServicosDesc.innerText = fmt(descontoServicos);
    }

    if (elServicosLiq) {
        elServicosLiq.innerText = fmt(liquidoServicos);
    }

    const elTotalDesc =
        document.getElementById('resumo-total-descontos');

    const elValorGeralOs =
        document.getElementById('valor-geral-os');

    if (elTotalDesc) {
        elTotalDesc.innerText = fmt(totalDescontos);
    }

    if (elValorGeralOs) {
        elValorGeralOs.innerText = fmt(valorTotalNota);
    }
};


document.addEventListener('DOMContentLoaded', () => {

    const typeSelect =
        document.getElementById('builder_type');

    const buscaInput =
        document.getElementById('builder_item_busca');

    const resultados =
        document.getElementById('builder_resultados');

    const buscaStatus =
        document.getElementById('builder_busca_status');

    const itemIdInput =
        document.getElementById('builder_item_id');

    const valorUnitInput =
        document.getElementById('builder_valor_unitario');

    const descInput =
        document.getElementById('builder_descricao');

    const qtdInput =
        document.getElementById('builder_quantidade');

    const descontoInput =
        document.getElementById('builder_desconto');

    const garantiaInput =
        document.getElementById('builder_garantia');

    const btnAdicionar =
        document.getElementById('btn-adicionar-item');

    const containerItens =
        document.getElementById('container-itens-dinamicos');

    const linhaVazia =
        document.getElementById('linha-vazia');

    const modalDescPecasValor =
        document.getElementById('modal-desc-pecas-valor');

    const modalDescPecasPorcent =
        document.getElementById('modal-desc-pecas-porcent');

    const modalDescServicosValor =
        document.getElementById('modal-desc-servicos-valor');

    const modalDescServicosPorcent =
        document.getElementById('modal-desc-servicos-porcent');

    const btnAplicarModal =
        document.getElementById('btn-aplicar-descontos-modal');

    const modalElement =
        document.getElementById('modalDescontos');

    let itemIndex = containerItens
        ? containerItens.querySelectorAll('tr.item-row').length
        : 0;

    let buscaTimer = null;
    let buscaController = null;


    // ============================================================
    // BUSCA DE ITENS PELA API
    // ============================================================

    async function buscarItens() {

        if (!typeSelect || !buscaInput || !resultados) {
            return;
        }

        const type = typeSelect.value;
        const busca = buscaInput.value.trim();

        resultados.innerHTML = '';

        if (!type) {
            buscaStatus.textContent =
                'Selecione o tipo de item.';

            buscaStatus.className =
                'text-muted';

            return;
        }

        if (busca.length < 2) {
            buscaStatus.textContent =
                'Digite pelo menos 2 caracteres para pesquisar.';

            buscaStatus.className =
                'text-muted';

            return;
        }

        const tipoApi =
            type.includes('Produto')
                ? 'produto'
                : 'os';

        const veiculoInput =
            document.getElementById('veiculo_cliente_id');

        const veiculoClienteId =
            veiculoInput?.value || '';

        buscaStatus.textContent =
            'Buscando...';

        buscaStatus.className =
            'text-primary';

        if (buscaController) {
            buscaController.abort();
        }

        buscaController =
            new AbortController();

        try {

            const params =
                new URLSearchParams({
                    tipo: tipoApi,
                    q: busca,
                });

            if (
                tipoApi === 'os' &&
                veiculoClienteId
            ) {
                params.set(
                    'veiculo_cliente_id',
                    veiculoClienteId
                );
            }

            const response =
                await fetch(
                    `/api/notas-itens/buscar?${params.toString()}`,
                    {
                        headers: {
                            'Accept': 'application/json',
                        },
                        signal: buscaController.signal,
                    }
                );

            if (!response.ok) {

                if (response.status === 401) {
                    throw new Error(
                        'Sua sessão expirou. Atualize a página e tente novamente.'
                    );
                }

                if (response.status === 403) {
                    throw new Error(
                        'Você não possui permissão para realizar esta busca.'
                    );
                }

                throw new Error(
                    `Erro HTTP ${response.status}`
                );
            }

            const resposta =
                await response.json();

            const itens =
                Array.isArray(resposta.data)
                    ? resposta.data
                    : [];

            resultados.innerHTML = '';

            if (itens.length === 0) {

                buscaStatus.textContent =
                    tipoApi === 'produto'
                        ? 'Nenhum produto encontrado.'
                        : 'Nenhuma O.S encontrada.';

                buscaStatus.className =
                    'text-muted';

                return;
            }

            buscaStatus.textContent =
                `${itens.length} resultado(s) encontrado(s).`;

            buscaStatus.className =
                'text-success';

            itens.forEach((item) => {

                const botao =
                    document.createElement('button');

                botao.type = 'button';

                botao.className =
                    'list-group-item list-group-item-action';

                if (tipoApi === 'produto') {

                    const codigo =
                        item.codigo_barras ||
                        item.codigo_fabricante ||
                        '';

                    botao.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start">
                            <strong>${escaparHtml(item.nome || 'Produto')}</strong>
                            <span class="badge bg-primary">
                                R$ ${formatarNumero(item.preco)}
                            </span>
                        </div>

                        ${
                            item.descricao
                                ? `<small class="text-muted">
                                    ${escaparHtml(item.descricao)}
                                </small>`
                                : ''
                        }

                        ${
                            codigo
                                ? `<small class="d-block text-secondary">
                                    Código: ${escaparHtml(codigo)}
                                </small>`
                                : ''
                        }
                    `;

                    botao.addEventListener(
                        'click',
                        () => selecionarItem({
                            type,
                            id: item.id,
                            descricao:
                                item.nome ||
                                item.descricao ||
                                '',
                            valor:
                                parseFloat(item.preco) || 0,
                        })
                    );

                } else {

                    botao.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start">
                            <strong>
                                O.S. #${item.id}
                            </strong>

                            <span class="badge bg-warning text-dark">
                                R$ ${formatarNumero(item.valor)}
                            </span>
                        </div>

                        <small class="text-muted">
                            ${escaparHtml(item.descricao || 'Sem descrição')}
                        </small>

                        ${
                            item.status
                                ? `<small class="d-block text-secondary">
                                    Status: ${escaparHtml(item.status)}
                                </small>`
                                : ''
                        }
                    `;

                    botao.addEventListener(
                        'click',
                        () => selecionarItem({
                            type,
                            id: item.id,
                            descricao:
                                item.descricao ||
                                `O.S. #${item.id}`,
                            valor:
                                parseFloat(item.valor) || 0,
                        })
                    );
                }

                resultados.appendChild(botao);
            });

        } catch (error) {

            if (error.name === 'AbortError') {
                return;
            }

            console.error(
                '[SOS Mecânica] Erro na busca de itens:',
                error
            );

            resultados.innerHTML = '';

            buscaStatus.textContent =
                error.message ||
                'Não foi possível realizar a busca.';

            buscaStatus.className =
                'text-danger';
        }
    }


    // ============================================================
    // SELECIONAR ITEM ENCONTRADO
    // ============================================================

    function selecionarItem(item) {

        if (!itemIdInput) {
            return;
        }

        itemIdInput.value =
            item.id;

        if (descInput) {
            descInput.value =
                item.descricao;
        }

        if (valorUnitInput) {
            valorUnitInput.value =
                Number(item.valor || 0)
                    .toFixed(2);
        }

        if (buscaInput) {
            buscaInput.value =
                item.descricao;
        }

        if (resultados) {
            resultados.innerHTML = '';
        }

        if (buscaStatus) {
            buscaStatus.textContent =
                'Item selecionado.';

            buscaStatus.className =
                'text-success';
        }
    }


    // ============================================================
    // TROCA DE TIPO
    // ============================================================

    if (typeSelect) {

        typeSelect.addEventListener(
            'change',
            () => {

                if (buscaInput) {
                    buscaInput.value = '';
                    buscaInput.disabled =
                        !typeSelect.value;
                }

                if (itemIdInput) {
                    itemIdInput.value = '';
                }

                if (resultados) {
                    resultados.innerHTML = '';
                }

                if (valorUnitInput) {
                    valorUnitInput.value = '';
                }

                if (descInput) {
                    descInput.value = '';
                }

                if (buscaStatus) {

                    if (!typeSelect.value) {

                        buscaStatus.textContent =
                            'Selecione o tipo de item.';

                        buscaStatus.className =
                            'text-muted';

                    } else if (
                        typeSelect.value.includes('Produto')
                    ) {

                        buscaStatus.textContent =
                            'Digite nome, código ou descrição do produto.';

                        buscaStatus.className =
                            'text-muted';

                    } else {

                        const veiculo =
                            document.getElementById(
                                'veiculo_cliente_id'
                            )?.value;

                        buscaStatus.textContent =
                            veiculo
                                ? 'Digite a descrição ou número da O.S.'
                                : 'Informe uma placa para buscar O.S. do veículo.';

                        buscaStatus.className =
                            'text-muted';
                    }
                }
            }
        );
    }


    // ============================================================
    // PESQUISA COM DEBOUNCE
    // ============================================================

    if (buscaInput) {

        buscaInput.addEventListener(
            'input',
            () => {

                if (itemIdInput) {
                    itemIdInput.value = '';
                }

                clearTimeout(buscaTimer);

                buscaTimer = setTimeout(
                    buscarItens,
                    350
                );
            }
        );

        buscaInput.addEventListener(
            'keydown',
            (event) => {

                if (event.key === 'Escape') {

                    buscaInput.value = '';

                    if (itemIdInput) {
                        itemIdInput.value = '';
                    }

                    if (resultados) {
                        resultados.innerHTML = '';
                    }
                }
            }
        );
    }


    // ============================================================
    // FORMATAÇÃO
    // ============================================================

    function formatarNumero(valor) {

        return Number(valor || 0)
            .toFixed(2)
            .replace('.', ',');
    }


    function escaparHtml(valor) {

        const div =
            document.createElement('div');

        div.textContent =
            String(valor ?? '');

        return div.innerHTML;
    }


    // ============================================================
    // MODAL DE DESCONTOS
    // ============================================================

    if (modalElement) {

        modalElement.addEventListener(
            'show.bs.modal',
            () => {

                let subtotalPecas = 0;
                let descontoPecas = 0;
                let subtotalServicos = 0;
                let descontoServicos = 0;

                document
                    .querySelectorAll('.item-row')
                    .forEach((linha) => {

                        const inputType =
                            linha.querySelector(
                                'input[name*="[itemable_type]"]'
                            );

                        if (!inputType) {
                            return;
                        }

                        const isProduto =
                            inputType.value.includes(
                                'Produto'
                            );

                        const qtd =
                            parseFloat(
                                linha.querySelector(
                                    '.input-qtd'
                                )?.value
                            ) || 0;

                        const vunit =
                            parseFloat(
                                linha.querySelector(
                                    '.input-vunit'
                                )?.value
                            ) || 0;

                        const desc =
                            parseFloat(
                                linha.querySelector(
                                    '.input-desc-val'
                                )?.value
                            ) || 0;

                        const subtotalItem =
                            qtd * vunit;

                        if (isProduto) {

                            subtotalPecas +=
                                subtotalItem;

                            descontoPecas +=
                                desc;

                        } else {

                            subtotalServicos +=
                                subtotalItem;

                            descontoServicos +=
                                desc;
                        }
                    });

                if (modalDescPecasValor) {

                    modalDescPecasValor.value =
                        descontoPecas.toFixed(2);
                }

                if (modalDescPecasPorcent) {

                    modalDescPecasPorcent.value =
                        subtotalPecas > 0
                            ? (
                                (
                                    descontoPecas /
                                    subtotalPecas
                                ) * 100
                            ).toFixed(2)
                            : '0.00';
                }

                if (modalDescServicosValor) {

                    modalDescServicosValor.value =
                        descontoServicos.toFixed(2);
                }

                if (modalDescServicosPorcent) {

                    modalDescServicosPorcent.value =
                        subtotalServicos > 0
                            ? (
                                (
                                    descontoServicos /
                                    subtotalServicos
                                ) * 100
                            ).toFixed(2)
                            : '0.00';
                }
            }
        );
    }


    // ============================================================
    // RECALCULAR DESCONTO DO MODAL
    // ============================================================

    function recalcularValoresModal(
        tipo,
        origem
    ) {

        let subtotalTipo = 0;

        document
            .querySelectorAll('.item-row')
            .forEach((linha) => {

                const inputType =
                    linha.querySelector(
                        'input[name*="[itemable_type]"]'
                    );

                if (!inputType) {
                    return;
                }

                const isProduto =
                    inputType.value.includes(
                        'Produto'
                    );

                if (
                    (
                        tipo === 'pecas' &&
                        isProduto
                    ) ||
                    (
                        tipo === 'servicos' &&
                        !isProduto
                    )
                ) {

                    const qtd =
                        parseFloat(
                            linha.querySelector(
                                '.input-qtd'
                            )?.value
                        ) || 0;

                    const vunit =
                        parseFloat(
                            linha.querySelector(
                                '.input-vunit'
                            )?.value
                        ) || 0;

                    subtotalTipo +=
                        qtd * vunit;
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

        if (!inputVal || !inputPct) {
            return;
        }

        if (origem === 'valor') {

            const val =
                parseFloat(inputVal.value) || 0;

            inputPct.value =
                subtotalTipo > 0
                    ? (
                        (val / subtotalTipo) *
                        100
                    ).toFixed(2)
                    : '0.00';

        } else {

            const pct =
                parseFloat(inputPct.value) || 0;

            inputVal.value =
                (
                    (subtotalTipo * pct) /
                    100
                ).toFixed(2);
        }
    }


    if (modalDescPecasValor) {

        modalDescPecasValor.addEventListener(
            'input',
            () =>
                recalcularValoresModal(
                    'pecas',
                    'valor'
                )
        );
    }

    if (modalDescPecasPorcent) {

        modalDescPecasPorcent.addEventListener(
            'input',
            () =>
                recalcularValoresModal(
                    'pecas',
                    'porcentagem'
                )
        );
    }

    if (modalDescServicosValor) {

        modalDescServicosValor.addEventListener(
            'input',
            () =>
                recalcularValoresModal(
                    'servicos',
                    'valor'
                )
        );
    }

    if (modalDescServicosPorcent) {

        modalDescServicosPorcent.addEventListener(
            'input',
            () =>
                recalcularValoresModal(
                    'servicos',
                    'porcentagem'
                )
        );
    }


    // ============================================================
    // APLICAR DESCONTOS
    // ============================================================

    if (btnAplicarModal) {

        btnAplicarModal.addEventListener(
            'click',
            () => {

                const descPecasPct =
                    parseFloat(
                        modalDescPecasPorcent?.value
                    ) || 0;

                const descServicosPct =
                    parseFloat(
                        modalDescServicosPorcent?.value
                    ) || 0;

                document
                    .querySelectorAll('.item-row')
                    .forEach((linha) => {

                        const inputType =
                            linha.querySelector(
                                'input[name*="[itemable_type]"]'
                            );

                        if (!inputType) {
                            return;
                        }

                        const isProduto =
                            inputType.value.includes(
                                'Produto'
                            );

                        const pctAplicar =
                            isProduto
                                ? descPecasPct
                                : descServicosPct;

                        const elQtd =
                            linha.querySelector(
                                '.input-qtd'
                            );

                        const elVunit =
                            linha.querySelector(
                                '.input-vunit'
                            );

                        const elDescItem =
                            linha.querySelector(
                                '.input-desc-val'
                            );

                        const elVtotal =
                            linha.querySelector(
                                '.input-vtotal'
                            );

                        const qtd =
                            parseFloat(
                                elQtd?.value
                            ) || 0;

                        const vunit =
                            parseFloat(
                                elVunit?.value
                            ) || 0;

                        const subtotalBruto =
                            qtd * vunit;

                        const descontoProporcional =
                            (
                                subtotalBruto *
                                pctAplicar
                            ) / 100;

                        const totalComDesconto =
                            Math.max(
                                0,
                                subtotalBruto -
                                descontoProporcional
                            );

                        if (elDescItem) {

                            elDescItem.value =
                                descontoProporcional
                                    .toFixed(2);
                        }

                        if (elVtotal) {

                            elVtotal.value =
                                totalComDesconto
                                    .toFixed(2)
                                    .replace(
                                        '.',
                                        ','
                                    );
                        }
                    });

                window.recalcularTotalGeral();

                if (
                    modalElement &&
                    window.bootstrap
                ) {

                    const bsModal =
                        bootstrap.Modal.getInstance(
                            modalElement
                        );

                    if (bsModal) {
                        bsModal.hide();
                    }
                }
            }
        );
    }


    // ============================================================
    // ADICIONAR ITEM
    // ============================================================

    if (btnAdicionar) {

        btnAdicionar.addEventListener(
            'click',
            () => {

                if (
                    !typeSelect?.value ||
                    !itemIdInput?.value ||
                    !descInput?.value.trim() ||
                    !valorUnitInput?.value
                ) {

                    alert(
                        'Selecione um item e preencha os campos obrigatórios.'
                    );

                    return;
                }

                const itemableType =
                    typeSelect.value;

                const itemableId =
                    itemIdInput.value;

                const isProduto =
                    itemableType.includes(
                        'Produto'
                    );

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
                    parseInt(
                        qtdInput?.value
                    ) || 1;

                const valorUnitario =
                    parseFloat(
                        valorUnitInput.value
                    ) || 0;

                const desconto =
                    parseFloat(
                        descontoInput?.value
                    ) || 0;

                const garantiaDias =
                    garantiaInput?.value
                        ? parseInt(
                            garantiaInput.value
                        )
                        : '';

                const valorTotalItem =
                    Math.max(
                        0,
                        (
                            quantidade *
                            valorUnitario
                        ) - desconto
                    );

                if (linhaVazia) {
                    linhaVazia.style.display =
                        'none';
                }

                const novaLinha =
                    document.createElement('tr');

                novaLinha.classList.add(
                    'item-row'
                );

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
                            value="${escaparHtml(descricao)}"
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

                containerItens.appendChild(
                    novaLinha
                );

                itemIndex++;

                // Limpa o construtor
                typeSelect.value = '';

                if (buscaInput) {
                    buscaInput.value = '';
                    buscaInput.disabled = true;
                }

                if (itemIdInput) {
                    itemIdInput.value = '';
                }

                if (descInput) {
                    descInput.value = '';
                }

                if (valorUnitInput) {
                    valorUnitInput.value = '';
                }

                if (qtdInput) {
                    qtdInput.value = '1';
                }

                if (descontoInput) {
                    descontoInput.value = '0.00';
                }

                if (garantiaInput) {
                    garantiaInput.value = '';
                }

                if (resultados) {
                    resultados.innerHTML = '';
                }

                if (buscaStatus) {
                    buscaStatus.textContent =
                        'Selecione o tipo de item.';

                    buscaStatus.className =
                        'text-muted';
                }

                window.recalcularTotalGeral();
            }
        );
    }


    // ============================================================
    // REMOÇÃO E EDIÇÃO DOS ITENS
    // ============================================================

    if (containerItens) {

        containerItens.addEventListener(
            'click',
            (event) => {

                const botao =
                    event.target.closest(
                        '.btn-remover-item'
                    );

                if (!botao) {
                    return;
                }

                botao
                    .closest('tr')
                    ?.remove();

                const existemItens =
                    containerItens.querySelector(
                        'tr.item-row'
                    );

                if (
                    !existemItens &&
                    linhaVazia
                ) {
                    linhaVazia.style.display =
                        '';
                }

                window.recalcularTotalGeral();
            }
        );


        containerItens.addEventListener(
            'input',
            (event) => {

                if (
                    !event.target.classList.contains(
                        'input-qtd'
                    ) &&
                    !event.target.classList.contains(
                        'input-vunit'
                    ) &&
                    !event.target.classList.contains(
                        'input-desc-val'
                    )
                ) {
                    return;
                }

                const linha =
                    event.target.closest('tr');

                if (!linha) {
                    return;
                }

                const qtd =
                    parseFloat(
                        linha.querySelector(
                            '.input-qtd'
                        )?.value
                    ) || 0;

                const vunit =
                    parseFloat(
                        linha.querySelector(
                            '.input-vunit'
                        )?.value
                    ) || 0;

                const desc =
                    parseFloat(
                        linha.querySelector(
                            '.input-desc-val'
                        )?.value
                    ) || 0;

                const totalItem =
                    Math.max(
                        0,
                        (
                            qtd * vunit
                        ) - desc
                    );

                const inputTotal =
                    linha.querySelector(
                        '.input-vtotal'
                    );

                if (inputTotal) {

                    inputTotal.value =
                        totalItem
                            .toFixed(2)
                            .replace(
                                '.',
                                ','
                            );
                }

                window.recalcularTotalGeral();
            }
        );
    }


    // ============================================================
    // CÁLCULO INICIAL
    // ============================================================

    window.recalcularTotalGeral();
});
