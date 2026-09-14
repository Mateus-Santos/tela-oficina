(function () {
    'use strict';

    function iniciarItens() {
        // ============================================================
        // CONFIGURAÇÕES
        // ============================================================

        const API_BUSCA = '/api/notas-itens/buscar';

        const PRODUTO_TYPE = 'App\\Models\\Produto';
        const ORDEM_SERVICO_TYPE = 'App\\Models\\OrdemServico';

        // ============================================================
        // ELEMENTOS DO EDITOR
        // ============================================================

        const tipoInput = document.getElementById(
            'builder_type'
        );

        const buscaInput = document.getElementById(
            'builder_item_busca'
        );

        const resultadosContainer = document.getElementById(
            'builder_resultados'
        );

        const buscaStatus = document.getElementById(
            'builder_busca_status'
        );

        const itemIdInput = document.getElementById(
            'builder_item_id'
        );

        const descricaoInput = document.getElementById(
            'builder_descricao'
        );

        const quantidadeInput = document.getElementById(
            'builder_quantidade'
        );

        const valorInput = document.getElementById(
            'builder_valor_unitario'
        );

        const descontoInput = document.getElementById(
            'builder_desconto'
        );

        const garantiaInput = document.getElementById(
            'builder_garantia_dias'
        );

        const botaoAdicionar = document.getElementById(
            'btn-adicionar-item'
        );

        // ============================================================
        // TABELA
        // ============================================================

        const tabelaItens = document.getElementById(
            'container-itens-dinamicos'
        );

        const linhaVazia = document.getElementById(
            'linha-vazia'
        );

        // ============================================================
        // VALIDAÇÃO BÁSICA
        // ============================================================

        if (
            !tipoInput ||
            !buscaInput ||
            !resultadosContainer ||
            !tabelaItens
        ) {
            console.warn(
                '[SOS Mecânica] Elementos do gerenciador de itens não encontrados.'
            );

            return;
        }

        // ============================================================
        // ESTADO
        // ============================================================

        let buscaTimeout = null;
        let buscaController = null;
        let indiceItem = 0;

        let itemSelecionado = {
            tipo: '',
            id: '',
            descricao: '',
            valor: 0
        };

        // ============================================================
        // UTILITÁRIOS
        // ============================================================

        function escaparHtml(valor) {
            if (
                valor === null ||
                typeof valor === 'undefined'
            ) {
                return '';
            }

            return String(valor)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function converterNumero(valor) {
            if (
                valor === null ||
                typeof valor === 'undefined'
            ) {
                return 0;
            }

            let texto = String(valor).trim();

            if (texto === '') {
                return 0;
            }

            /*
             * Aceita:
             *
             * 10
             * 10.50
             * 10,50
             * 1.250,50
             */

            if (texto.indexOf(',') !== -1) {
                texto = texto
                    .replace(/\./g, '')
                    .replace(',', '.');
            } else if (
                (texto.match(/\./g) || []).length > 1
            ) {
                texto = texto.replace(/\./g, '');
            }

            const numero = parseFloat(texto);

            if (!Number.isFinite(numero)) {
                return 0;
            }

            return numero;
        }

        function formatarMoeda(valor) {
            const numero = Number(valor);

            if (!Number.isFinite(numero)) {
                return 'R$ 0,00';
            }

            return numero.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
        }

        function formatarNumero(valor) {
            const numero = Number(valor);

            if (!Number.isFinite(numero)) {
                return '0,00';
            }

            return numero
                .toFixed(2)
                .replace('.', ',');
        }

        function obterTipoSelecionado() {
            return tipoInput.value || '';
        }

        function obterVeiculoClienteId() {
            const veiculoInput = document.getElementById(
                'veiculo_cliente_id'
            );

            if (!veiculoInput) {
                return '';
            }

            return veiculoInput.value || '';
        }

        // ============================================================
        // TIPOS
        // ============================================================

        function normalizarTipo(tipo) {
            const valor = String(tipo || '');

            if (
                valor === PRODUTO_TYPE ||
                valor === 'Produto' ||
                valor === 'produto'
            ) {
                return 'produto';
            }

            if (
                valor === ORDEM_SERVICO_TYPE ||
                valor === 'OrdemServico' ||
                valor === 'os'
            ) {
                return 'os';
            }

            return '';
        }

        function obterTipoLinha(linha) {
            if (!linha) {
                return '';
            }

            const tipoInputLinha = linha.querySelector(
                'input[name*="[itemable_type]"], input[name*="[tipo]"]'
            );

            if (!tipoInputLinha) {
                return '';
            }

            return normalizarTipo(
                tipoInputLinha.value
            );
        }

        function obterItemableType(tipo) {
            if (tipo === 'produto') {
                return PRODUTO_TYPE;
            }

            if (tipo === 'os') {
                return ORDEM_SERVICO_TYPE;
            }

            return '';
        }

        // ============================================================
        // CAMPOS DAS LINHAS
        // ============================================================

        function obterCampoLinha(linha, seletor) {
            if (!linha) {
                return null;
            }

            return linha.querySelector(seletor);
        }

        function obterQuantidadeLinha(linha) {
            const input = obterCampoLinha(
                linha,
                '.input-qtd, .item-quantidade'
            );

            return input
                ? converterNumero(input.value)
                : 0;
        }

        function obterValorUnitarioLinha(linha) {
            const input = obterCampoLinha(
                linha,
                '.input-valor, .item-valor-unitario'
            );

            return input
                ? converterNumero(input.value)
                : 0;
        }

        function obterDescontoLinha(linha) {
            const input = obterCampoLinha(
                linha,
                '.input-desconto, .item-desconto'
            );

            return input
                ? Math.max(
                    0,
                    converterNumero(input.value)
                )
                : 0;
        }

        // ============================================================
        // CÁLCULOS
        // ============================================================

        function calcularValorTotalItem(
            quantidade,
            valorUnitario,
            desconto
        ) {
            const subtotal =
                quantidade * valorUnitario;

            return Math.max(
                0,
                subtotal - Math.max(0, desconto)
            );
        }

        function calcularDadosLinha(linha) {
            const quantidade =
                obterQuantidadeLinha(linha);

            const valorUnitario =
                obterValorUnitarioLinha(linha);

            const desconto =
                obterDescontoLinha(linha);

            const subtotal =
                quantidade * valorUnitario;

            const total =
                calcularValorTotalItem(
                    quantidade,
                    valorUnitario,
                    desconto
                );

            return {
                tipo: obterTipoLinha(linha),
                quantidade,
                valorUnitario,
                desconto,
                subtotal,
                total
            };
        }

        function atualizarTotalLinha(linha) {
            if (!linha) {
                return;
            }

            const dados = calcularDadosLinha(linha);

            const totalElemento = linha.querySelector(
                '.valor-total-item'
            );

            if (totalElemento) {
                totalElemento.textContent =
                    formatarMoeda(dados.total);
            }
        }

        // ============================================================
        // DADOS FINANCEIROS DA NOTA
        //
        // ESTE É O ÚNICO LOCAL QUE CALCULA O FINANCEIRO.
        // ============================================================

        function obterDadosFinanceiros() {
            const dados = {
                produto: {
                    bruto: 0,
                    desconto: 0,
                    liquido: 0
                },

                os: {
                    bruto: 0,
                    desconto: 0,
                    liquido: 0
                },

                subtotal: 0,
                desconto: 0,
                total: 0
            };

            const linhas = tabelaItens.querySelectorAll(
                'tr[data-item-index]'
            );

            linhas.forEach(function (linha) {
                const item = calcularDadosLinha(linha);

                if (!item.tipo) {
                    return;
                }

                dados[item.tipo].bruto += item.subtotal;
                dados[item.tipo].desconto += item.desconto;
            });

            dados.produto.liquido = Math.max(
                0,
                dados.produto.bruto -
                dados.produto.desconto
            );

            dados.os.liquido = Math.max(
                0,
                dados.os.bruto -
                dados.os.desconto
            );

            dados.subtotal =
                dados.produto.bruto +
                dados.os.bruto;

            dados.desconto =
                dados.produto.desconto +
                dados.os.desconto;

            dados.total =
                dados.produto.liquido +
                dados.os.liquido;

            return dados;
        }

        // ============================================================
        // ATUALIZA RESUMO FINANCEIRO
        // ============================================================

        function atualizarResumoFinanceiro() {
            const linhas = tabelaItens.querySelectorAll(
                'tr[data-item-index]'
            );

            linhas.forEach(function (linha) {
                atualizarTotalLinha(linha);
            });

            const dados = obterDadosFinanceiros();

            const elementos = {
                pecasBruto:
                    document.getElementById(
                        'resumo-pecas-bruto'
                    ),

                pecasDesconto:
                    document.getElementById(
                        'resumo-pecas-desconto'
                    ),

                pecasLiquido:
                    document.getElementById(
                        'resumo-pecas-liquido'
                    ),

                servicosBruto:
                    document.getElementById(
                        'resumo-servicos-bruto'
                    ),

                servicosDesconto:
                    document.getElementById(
                        'resumo-servicos-desconto'
                    ),

                servicosLiquido:
                    document.getElementById(
                        'resumo-servicos-liquido'
                    ),

                totalDescontos:
                    document.getElementById(
                        'resumo-total-descontos'
                    ),

                totalGeral:
                    document.getElementById(
                        'valor-geral-os'
                    )
            };

            if (elementos.pecasBruto) {
                elementos.pecasBruto.textContent =
                    formatarMoeda(
                        dados.produto.bruto
                    );
            }

            if (elementos.pecasDesconto) {
                elementos.pecasDesconto.textContent =
                    formatarMoeda(
                        dados.produto.desconto
                    );
            }

            if (elementos.pecasLiquido) {
                elementos.pecasLiquido.textContent =
                    formatarMoeda(
                        dados.produto.liquido
                    );
            }

            if (elementos.servicosBruto) {
                elementos.servicosBruto.textContent =
                    formatarMoeda(
                        dados.os.bruto
                    );
            }

            if (elementos.servicosDesconto) {
                elementos.servicosDesconto.textContent =
                    formatarMoeda(
                        dados.os.desconto
                    );
            }

            if (elementos.servicosLiquido) {
                elementos.servicosLiquido.textContent =
                    formatarMoeda(
                        dados.os.liquido
                    );
            }

            if (elementos.totalDescontos) {
                elementos.totalDescontos.textContent =
                    formatarMoeda(
                        dados.desconto
                    );
            }

            if (elementos.totalGeral) {
                elementos.totalGeral.textContent =
                    formatarMoeda(
                        dados.total
                    );
            }

            return dados;
        }

        // ============================================================
        // API PÚBLICA PARA OUTROS MÓDULOS
        // ============================================================

        window.NotaItens = {
            recalcular: atualizarResumoFinanceiro,

            obterDadosFinanceiros: obterDadosFinanceiros,

            obterSubtotalCategoria: function (tipo) {
                const dados =
                    obterDadosFinanceiros();

                if (tipo === 'produto') {
                    return dados.produto.bruto;
                }

                if (tipo === 'os') {
                    return dados.os.bruto;
                }

                return 0;
            }
        };

        // ============================================================
        // RESULTADOS DA BUSCA
        // ============================================================

        function limparResultados() {
            resultadosContainer.innerHTML = '';
        }

        function limparSelecao() {
            itemSelecionado = {
                tipo: '',
                id: '',
                descricao: '',
                valor: 0
            };

            if (itemIdInput) {
                itemIdInput.value = '';
            }
        }

        function atualizarEstadoBusca() {
            const tipo = obterTipoSelecionado();

            if (!tipo) {
                buscaInput.disabled = true;
                buscaInput.value = '';

                limparResultados();
                limparSelecao();

                if (buscaStatus) {
                    buscaStatus.textContent =
                        'Selecione o tipo de item.';
                }

                return;
            }

            buscaInput.disabled = false;

            if (buscaStatus) {
                buscaStatus.textContent =
                    'Digite para pesquisar.';
            }
        }

        function selecionarItem(item) {
            const tipo = obterTipoSelecionado();

            if (!tipo || !item) {
                return;
            }

            let descricao = '';

            if (tipo === 'produto') {
                descricao = item.nome || '';
            }

            if (tipo === 'os') {
                descricao = item.descricao || '';
            }

            let valor = 0;

            if (tipo === 'produto') {
                valor = converterNumero(item.preco);
            }

            if (tipo === 'os') {
                valor = converterNumero(item.valor);
            }

            itemSelecionado = {
                tipo,
                id: item.id,
                descricao,
                valor
            };

            if (itemIdInput) {
                itemIdInput.value = item.id;
            }

            if (descricaoInput) {
                descricaoInput.value = descricao;
            }

            if (valorInput) {
                valorInput.value = valor.toFixed(2);
            }

            if (descontoInput) {
                descontoInput.value = '0.00';
            }

            if (buscaInput) {
                if (tipo === 'produto') {
                    buscaInput.value =
                        item.nome || '';
                } else {
                    buscaInput.value =
                        'O.S. #' +
                        item.id +
                        ' - ' +
                        descricao;
                }
            }

            limparResultados();

            if (buscaStatus) {
                buscaStatus.textContent =
                    'Item selecionado.';
            }

            atualizarPreviaItem();
        }

        function renderizarResultados(itens, tipo) {
            resultadosContainer.innerHTML = '';

            if (
                !Array.isArray(itens) ||
                itens.length === 0
            ) {
                resultadosContainer.innerHTML = `
                    <div class="list-group-item text-muted">
                        Nenhum item encontrado.
                    </div>
                `;

                return;
            }

            itens.forEach(function (item) {
                const botao =
                    document.createElement('button');

                botao.type = 'button';

                botao.className =
                    'list-group-item list-group-item-action';

                if (tipo === 'produto') {
                    const nome =
                        item.nome ||
                        'Produto';

                    const descricao =
                        item.descricao ||
                        '';

                    const codigo =
                        item.codigo_barras ||
                        item.codigo_fabricante ||
                        '';

                    botao.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <strong>
                                    ${escaparHtml(nome)}
                                </strong>

                                ${
                                    descricao
                                        ? `
                                            <div class="small text-muted">
                                                ${escaparHtml(descricao)}
                                            </div>
                                        `
                                        : ''
                                }

                                ${
                                    codigo
                                        ? `
                                            <div class="small text-muted">
                                                Código: ${escaparHtml(codigo)}
                                            </div>
                                        `
                                        : ''
                                }
                            </div>

                            <strong>
                                ${formatarMoeda(item.preco)}
                            </strong>
                        </div>
                    `;
                } else {
                    const descricao =
                        item.descricao ||
                        'Ordem de Serviço';

                    botao.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <strong>
                                    O.S. #${escaparHtml(item.id)}
                                </strong>

                                <div class="small text-muted">
                                    ${escaparHtml(descricao)}
                                </div>

                                ${
                                    item.status
                                        ? `
                                            <div class="small text-muted">
                                                Status: ${escaparHtml(item.status)}
                                            </div>
                                        `
                                        : ''
                                }
                            </div>

                            <strong>
                                ${formatarMoeda(item.valor)}
                            </strong>
                        </div>
                    `;
                }

                botao.addEventListener(
                    'click',
                    function () {
                        selecionarItem(item);
                    }
                );

                resultadosContainer.appendChild(botao);
            });
        }

        // ============================================================
        // BUSCA API
        // ============================================================

        async function buscarItens() {
            const tipo = obterTipoSelecionado();
            const busca = buscaInput.value.trim();

            if (!tipo) {
                limparResultados();

                if (buscaStatus) {
                    buscaStatus.textContent =
                        'Selecione o tipo de item.';
                }

                return;
            }

            if (!busca) {
                limparResultados();

                if (buscaStatus) {
                    buscaStatus.textContent =
                        'Digite para pesquisar.';
                }

                return;
            }

            if (buscaController) {
                buscaController.abort();
            }

            buscaController = new AbortController();

            if (buscaStatus) {
                buscaStatus.textContent =
                    'Buscando...';
            }

            const parametros =
                new URLSearchParams();

            parametros.set('tipo', tipo);
            parametros.set('q', busca);

            if (tipo === 'os') {
                const veiculoClienteId =
                    obterVeiculoClienteId();

                if (veiculoClienteId) {
                    parametros.set(
                        'veiculo_cliente_id',
                        veiculoClienteId
                    );
                }
            }

            const url =
                API_BUSCA +
                '?' +
                parametros.toString();

            try {
                const response =
                    await fetch(url, {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json'
                        },
                        signal: buscaController.signal
                    });

                if (!response.ok) {
                    throw new Error(
                        'Erro HTTP ' +
                        response.status
                    );
                }

                const data =
                    await response.json();

                const itens =
                    Array.isArray(data.data)
                        ? data.data
                        : [];

                renderizarResultados(
                    itens,
                    tipo
                );

                if (buscaStatus) {
                    if (itens.length === 0) {
                        buscaStatus.textContent =
                            'Nenhum item encontrado.';
                    } else {
                        buscaStatus.textContent =
                            itens.length +
                            ' resultado(s) encontrado(s).';
                    }
                }
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }

                console.error(
                    '[SOS Mecânica] Erro ao buscar item:',
                    error
                );

                resultadosContainer.innerHTML = `
                    <div class="list-group-item text-danger">
                        Não foi possível realizar a busca.
                    </div>
                `;

                if (buscaStatus) {
                    buscaStatus.textContent =
                        'Erro ao consultar os itens.';
                }
            } finally {
                buscaController = null;
            }
        }

        // ============================================================
        // PREVIEW DO ITEM
        // ============================================================

        function atualizarPreviaItem() {
            const totalElemento =
                document.getElementById(
                    'builder_total'
                );

            if (!totalElemento) {
                return;
            }

            const quantidade =
                quantidadeInput
                    ? converterNumero(
                        quantidadeInput.value
                    )
                    : 0;

            const valor =
                valorInput
                    ? converterNumero(
                        valorInput.value
                    )
                    : 0;

            const desconto =
                descontoInput
                    ? converterNumero(
                        descontoInput.value
                    )
                    : 0;

            const total =
                calcularValorTotalItem(
                    quantidade,
                    valor,
                    desconto
                );

            totalElemento.textContent =
                formatarMoeda(total);
        }

        // ============================================================
        // VALIDAÇÃO
        // ============================================================

        function validarItem() {
            if (!obterTipoSelecionado()) {
                alert(
                    'Selecione o tipo do item.'
                );

                return false;
            }

            if (
                !itemIdInput ||
                !itemIdInput.value
            ) {
                alert(
                    'Selecione um item da pesquisa.'
                );

                return false;
            }

            if (
                !descricaoInput ||
                !descricaoInput.value.trim()
            ) {
                alert(
                    'Informe a descrição do item.'
                );

                return false;
            }

            const quantidade =
                quantidadeInput
                    ? converterNumero(
                        quantidadeInput.value
                    )
                    : 0;

            if (
                !Number.isInteger(quantidade) ||
                quantidade < 1
            ) {
                alert(
                    'A quantidade deve ser um número inteiro maior ou igual a 1.'
                );

                if (quantidadeInput) {
                    quantidadeInput.focus();
                }

                return false;
            }

            const valor =
                valorInput
                    ? converterNumero(
                        valorInput.value
                    )
                    : 0;

            if (valor < 0) {
                alert(
                    'O valor do item não pode ser negativo.'
                );

                if (valorInput) {
                    valorInput.focus();
                }

                return false;
            }

            const desconto =
                descontoInput
                    ? Math.max(
                        0,
                        converterNumero(
                            descontoInput.value
                        )
                    )
                    : 0;

            const subtotal =
                quantidade * valor;

            if (desconto > subtotal) {
                alert(
                    'O desconto não pode ser maior que o valor do item.'
                );

                if (descontoInput) {
                    descontoInput.focus();
                }

                return false;
            }

            return true;
        }

        // ============================================================
        // ADICIONAR ITEM
        // ============================================================

        function adicionarItem() {
            if (!validarItem()) {
                return;
            }

            const tipo =
                obterTipoSelecionado();

            const itemId =
                itemIdInput.value;

            const descricao =
                descricaoInput.value.trim();

            const quantidade =
                Math.trunc(
                    converterNumero(
                        quantidadeInput.value
                    )
                );

            const valorUnitario =
                converterNumero(
                    valorInput.value
                );

            const desconto =
                descontoInput
                    ? Math.max(
                        0,
                        converterNumero(
                            descontoInput.value
                        )
                    )
                    : 0;

            const garantiaDias =
                garantiaInput
                    ? parseInt(
                        garantiaInput.value,
                        10
                    ) || 0
                    : 0;

            const valorTotal =
                calcularValorTotalItem(
                    quantidade,
                    valorUnitario,
                    desconto
                );

            const index =
                indiceItem++;

            const itemableType =
                obterItemableType(tipo);

            const linha =
                document.createElement('tr');

            linha.setAttribute(
                'data-item-index',
                index
            );

            linha.innerHTML = `
                <td>
                    <input
                        type="hidden"
                        name="itens[${index}][itemable_type]"
                        value="${escaparHtml(itemableType)}"
                    >

                    <input
                        type="hidden"
                        name="itens[${index}][itemable_id]"
                        value="${escaparHtml(itemId)}"
                    >

                    <strong>
                        ${escaparHtml(
                            tipo === 'produto'
                                ? 'Produto'
                                : 'O.S.'
                        )}
                    </strong>
                </td>

                <td>
                    <input
                        type="text"
                        name="itens[${index}][descricao]"
                        value="${escaparHtml(descricao)}"
                        class="form-control input-descricao"
                    >
                </td>

                <td>
                    <input
                        type="number"
                        name="itens[${index}][quantidade]"
                        value="${quantidade}"
                        min="1"
                        step="1"
                        class="form-control input-qtd"
                    >
                </td>

                <td>
                    <input
                        type="text"
                        name="itens[${index}][valor_unitario]"
                        value="${formatarNumero(valorUnitario)}"
                        class="form-control input-valor"
                    >
                </td>

                <td>
                    <input
                        type="text"
                        name="itens[${index}][desconto]"
                        value="${formatarNumero(desconto)}"
                        class="form-control input-desconto"
                    >
                </td>

                <td>
                    <input
                        type="number"
                        name="itens[${index}][garantia_dias]"
                        value="${
                            garantiaDias > 0
                                ? garantiaDias
                                : ''
                        }"
                        min="0"
                        step="1"
                        class="form-control input-garantia"
                    >
                </td>

                <td class="valor-total-item">
                    ${formatarMoeda(valorTotal)}
                </td>

                <td>
                    <button
                        type="button"
                        class="btn btn-danger btn-remover-item"
                        title="Remover item"
                    >
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;

            if (linhaVazia) {
                linhaVazia.style.display = 'none';
            }

            tabelaItens.appendChild(linha);

            atualizarResumoFinanceiro();

            limparEditorItem();

            fecharModalAdicionarItem();
        }

        // ============================================================
        // REMOVER ITEM
        // ============================================================

        function removerItem(linha) {
            if (!linha) {
                return;
            }

            linha.remove();

            verificarTabelaVazia();
            atualizarResumoFinanceiro();
        }

        // ============================================================
        // TABELA VAZIA
        // ============================================================

        function verificarTabelaVazia() {
            if (!linhaVazia) {
                return;
            }

            const linhas =
                tabelaItens.querySelectorAll(
                    'tr[data-item-index]'
                );

            linhaVazia.style.display =
                linhas.length === 0
                    ? ''
                    : 'none';
        }

        // ============================================================
        // LIMPAR EDITOR
        // ============================================================

        function limparEditorItem() {
            limparSelecao();
            limparResultados();

            if (buscaInput) {
                buscaInput.value = '';
            }

            if (descricaoInput) {
                descricaoInput.value = '';
            }

            if (quantidadeInput) {
                quantidadeInput.value = '1';
            }

            if (valorInput) {
                valorInput.value = '';
            }

            if (descontoInput) {
                descontoInput.value = '0.00';
            }

            if (garantiaInput) {
                garantiaInput.value = '';
            }

            /*
             * Não zeramos o tipo.
             *
             * Permite adicionar vários produtos
             * consecutivamente.
             */

            atualizarEstadoBusca();
            atualizarPreviaItem();

            if (buscaStatus) {
                buscaStatus.textContent =
                    'Digite para pesquisar.';
            }
        }

        // ============================================================
        // FECHAR MODAL DE ADIÇÃO
        // ============================================================

        function fecharModalAdicionarItem() {
            const modalElement =
                document.getElementById(
                    'modalAdicionarItem'
                );

            if (
                modalElement &&
                typeof bootstrap !== 'undefined'
            ) {
                const modal =
                    bootstrap.Modal.getInstance(
                        modalElement
                    );

                if (modal) {
                    modal.hide();
                }
            }
        }

        // ============================================================
        // EVENTO DO TIPO
        // ============================================================

        tipoInput.addEventListener(
            'change',
            function () {
                limparSelecao();
                limparResultados();

                buscaInput.value = '';

                if (descricaoInput) {
                    descricaoInput.value = '';
                }

                if (valorInput) {
                    valorInput.value = '';
                }

                if (descontoInput) {
                    descontoInput.value = '0.00';
                }

                atualizarEstadoBusca();
                atualizarPreviaItem();
            }
        );

        // ============================================================
        // EVENTO DA BUSCA
        // ============================================================

        buscaInput.addEventListener(
            'input',
            function () {
                limparSelecao();

                if (buscaTimeout) {
                    clearTimeout(buscaTimeout);
                }

                buscaTimeout = setTimeout(
                    buscarItens,
                    350
                );
            }
        );

        buscaInput.addEventListener(
            'keydown',
            function (event) {
                if (event.key === 'Escape') {
                    limparResultados();
                }
            }
        );

        // ============================================================
        // CAMPOS DO EDITOR
        // ============================================================

        if (quantidadeInput) {
            quantidadeInput.addEventListener(
                'input',
                atualizarPreviaItem
            );
        }

        if (valorInput) {
            valorInput.addEventListener(
                'input',
                atualizarPreviaItem
            );
        }

        if (descontoInput) {
            descontoInput.addEventListener(
                'input',
                atualizarPreviaItem
            );
        }

        // ============================================================
        // BOTÃO ADICIONAR
        // ============================================================

        if (botaoAdicionar) {
            botaoAdicionar.addEventListener(
                'click',
                adicionarItem
            );
        }

        // ============================================================
        // EVENTOS DA TABELA
        //
        // UM ÚNICO LISTENER DELEGADO.
        // ============================================================

        tabelaItens.addEventListener(
            'input',
            function (event) {
                if (
                    event.target.matches(
                        '.input-qtd, .input-valor, .input-desconto, .item-quantidade, .item-valor-unitario, .item-desconto'
                    )
                ) {
                    const linha =
                        event.target.closest(
                            'tr[data-item-index]'
                        );

                    if (linha) {
                        atualizarTotalLinha(
                            linha
                        );
                    }

                    atualizarResumoFinanceiro();
                }
            }
        );

        tabelaItens.addEventListener(
            'change',
            function (event) {
                if (
                    event.target.matches(
                        '.input-qtd, .input-valor, .input-desconto, .item-quantidade, .item-valor-unitario, .item-desconto'
                    )
                ) {
                    const linha =
                        event.target.closest(
                            'tr[data-item-index]'
                        );

                    if (linha) {
                        atualizarTotalLinha(
                            linha
                        );
                    }

                    atualizarResumoFinanceiro();
                }
            }
        );

        tabelaItens.addEventListener(
            'click',
            function (event) {
                const botao =
                    event.target.closest(
                        '.btn-remover-item'
                    );

                if (!botao) {
                    return;
                }

                const linha =
                    botao.closest(
                        'tr[data-item-index]'
                    );

                removerItem(linha);
            }
        );

        // ============================================================
        // LINHAS EXISTENTES
        // ============================================================

        const linhasExistentes =
            tabelaItens.querySelectorAll(
                'tr[data-item-index]'
            );

        if (linhasExistentes.length > 0) {
            let maiorIndice = -1;

            linhasExistentes.forEach(
                function (linha) {
                    const indice =
                        parseInt(
                            linha.getAttribute(
                                'data-item-index'
                            ),
                            10
                        );

                    if (
                        !Number.isNaN(indice) &&
                        indice > maiorIndice
                    ) {
                        maiorIndice = indice;
                    }
                }
            );

            indiceItem =
                maiorIndice + 1;
        }

        // ============================================================
        // MODAL DE ADIÇÃO
        // ============================================================

        const modalAdicionarItem =
            document.getElementById(
                'modalAdicionarItem'
            );

        if (modalAdicionarItem) {
            modalAdicionarItem.addEventListener(
                'shown.bs.modal',
                function () {
                    atualizarEstadoBusca();

                    if (
                        buscaInput &&
                        !buscaInput.disabled
                    ) {
                        buscaInput.focus();
                    }
                }
            );
        }

        // ============================================================
        // INICIALIZAÇÃO
        // ============================================================

        verificarTabelaVazia();
        atualizarEstadoBusca();
        atualizarPreviaItem();
        atualizarResumoFinanceiro();

        console.log(
            '[SOS Mecânica] itens.js inicializado com sucesso.'
        );
    }

    // ================================================================
    // INICIALIZAÇÃO ROBUSTA
    // ================================================================

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            iniciarItens,
            {
                once: true
            }
        );
    } else {
        iniciarItens();
    }
})();
