import BuscaItens from './busca/busca-itens.js';

import {
    inicializarMascaraPreco,
    obterValorNumerico,
    formatarPreco
} from './mascaras/preco.js';

document.addEventListener('DOMContentLoaded', function () {

    const itensContainer =
        document.getElementById('itens-container');

    const btnAdicionarItem =
        document.getElementById('btn-adicionar-item');

    if (!itensContainer || !btnAdicionarItem) {
        return;
    }

    let proximoIndice =
        document.querySelectorAll('.compra-item').length;

    function formatarMoeda(valor) {
        return Number(valor || 0).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function obterValorCampo(elemento) {
        if (!elemento) {
            return 0;
        }

        if (
            elemento.classList.contains(
                'item-valor-unitario'
            )
        ) {
            return obterValorNumerico(elemento);
        }

        return parseFloat(elemento.value) || 0;
    }

    function calcularItem(item) {
        const campoQuantidade =
            item.querySelector('.item-quantidade');

        const campoValorUnitario =
            item.querySelector('.item-valor-unitario');

        const campoDesconto =
            item.querySelector('.item-desconto');

        const quantidade = campoQuantidade
            ? parseFloat(campoQuantidade.value) || 0
            : 0;

        const valorUnitario =
            obterValorCampo(campoValorUnitario);

        const desconto = campoDesconto
            ? parseFloat(campoDesconto.value) || 0
            : 0;

        const total = Math.max(
            0,
            (quantidade * valorUnitario) - desconto
        );

        const exibicao =
            item.querySelector('.item-valor-total');

        const input =
            item.querySelector('.item-valor-total-input');

        if (exibicao) {
            exibicao.textContent =
                'R$ ' + formatarMoeda(total);
        }

        if (input) {
            input.value = total.toFixed(2);
        }

        return total;
    }

    function calcularTotais() {
        let valorProdutos = 0;

        document
            .querySelectorAll('.compra-item')
            .forEach(function (item) {
                valorProdutos += calcularItem(item);
            });

        const campoDesconto =
            document.getElementById('desconto');

        const campoFrete =
            document.getElementById('frete');

        const campoOutrasDespesas =
            document.getElementById('outras_despesas');

        const desconto = campoDesconto
            ? parseFloat(campoDesconto.value) || 0
            : 0;

        const frete = campoFrete
            ? parseFloat(campoFrete.value) || 0
            : 0;

        const outrasDespesas = campoOutrasDespesas
            ? parseFloat(campoOutrasDespesas.value) || 0
            : 0;

        const valorTotal = Math.max(
            0,
            valorProdutos - desconto + frete + outrasDespesas
        );

        const valorProdutosInput =
            document.getElementById('valor_produtos');

        const valorTotalInput =
            document.getElementById('valor_total');

        const valorTotalExibicao =
            document.getElementById('valor-total-exibicao');

        if (valorProdutosInput) {
            valorProdutosInput.value =
                valorProdutos.toFixed(2);
        }

        if (valorTotalInput) {
            valorTotalInput.value =
                valorTotal.toFixed(2);
        }

        if (valorTotalExibicao) {
            valorTotalExibicao.textContent =
                'R$ ' + formatarMoeda(valorTotal);
        }
    }

    function atualizarNumeracao() {
        document
            .querySelectorAll('.compra-item')
            .forEach(function (item, index) {
                const numero =
                    item.querySelector('.item-numero');

                if (numero) {
                    numero.textContent = index + 1;
                }
            });
    }

    function escapeHtml(value) {
        const div =
            document.createElement('div');

        div.textContent = value || '';

        return div.innerHTML;
    }

    function mostrarProdutoSelecionado(item, produto) {
        const container =
            item.querySelector(
                '.item-produto-selecionado'
            );

        if (!container) {
            return;
        }

        container.classList.remove('d-none');

        container.innerHTML = `
            <div class="alert alert-light border mb-0 py-2">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-check-circle text-success"></i>

                    <div>
                        <strong>
                            ${escapeHtml(produto.nome)}
                        </strong>

                        ${
                            produto.marca
                                ? `
                                    <div class="small text-muted">
                                        ${escapeHtml(produto.marca)}
                                    </div>
                                `
                                : ''
                        }

                        ${
                            produto.codigo_fabricante
                                ? `
                                    <div class="small text-muted">
                                        Código:
                                        ${escapeHtml(
                                            produto.codigo_fabricante
                                        )}
                                    </div>
                                `
                                : ''
                        }

                        ${
                            produto.codigo_barras
                                ? `
                                    <div class="small text-muted">
                                        Código de barras:
                                        ${escapeHtml(
                                            produto.codigo_barras
                                        )}
                                    </div>
                                `
                                : ''
                        }
                    </div>
                </div>
            </div>
        `;
    }

    function limparProduto(item) {
        const buscaInput =
            item.querySelector(
                '.item-produto-busca'
            );

        const produtoIdInput =
            item.querySelector(
                '.item-produto-id'
            );

        const resultados =
            item.querySelector(
                '.item-produto-resultados'
            );

        const selecionado =
            item.querySelector(
                '.item-produto-selecionado'
            );

        const status =
            item.querySelector(
                '.item-produto-status'
            );

        if (buscaInput) {
            buscaInput.value = '';
            buscaInput.focus();
        }

        if (produtoIdInput) {
            produtoIdInput.value = '';
        }

        if (resultados) {
            resultados.innerHTML = '';
        }

        if (selecionado) {
            selecionado.innerHTML = '';
            selecionado.classList.add('d-none');
        }

        if (status) {
            status.textContent =
                'Digite para pesquisar.';
        }

        calcularTotais();
    }

    function configurarBuscaProduto(item) {
        const buscaInput =
            item.querySelector(
                '.item-produto-busca'
            );

        const resultados =
            item.querySelector(
                '.item-produto-resultados'
            );

        const status =
            item.querySelector(
                '.item-produto-status'
            );

        const produtoIdInput =
            item.querySelector(
                '.item-produto-id'
            );

        const descricaoInput =
            item.querySelector(
                '.item-descricao'
            );

        const valorUnitarioInput =
            item.querySelector(
                '.item-valor-unitario'
            );

        if (
            !buscaInput ||
            !resultados ||
            !produtoIdInput
        ) {
            return;
        }

        new BuscaItens({
            input: buscaInput,
            resultados: resultados,
            status: status,
            endpoint: '/api/produtos/buscar',
            debounce: 350,
            minimoCaracteres: 1,

            obterParametros: function (busca) {
                return {
                    q: busca
                };
            },

            renderizarItem: function (produto) {
                const container =
                    document.createElement('div');

                container.className =
                    'd-flex flex-column gap-1';

                const nome =
                    document.createElement('strong');

                nome.textContent =
                    produto.nome || '';

                container.appendChild(nome);

                if (produto.marca) {
                    const marca =
                        document.createElement('small');

                    marca.className =
                        'text-muted';

                    marca.textContent =
                        'Marca: ' + produto.marca;

                    container.appendChild(marca);
                }

                if (produto.codigo_fabricante) {
                    const codigo =
                        document.createElement('small');

                    codigo.className =
                        'text-muted';

                    codigo.textContent =
                        'Código fabricante: ' +
                        produto.codigo_fabricante;

                    container.appendChild(codigo);
                }

                if (produto.codigo_barras) {
                    const codigoBarras =
                        document.createElement('small');

                    codigoBarras.className =
                        'text-muted';

                    codigoBarras.textContent =
                        'Código de barras: ' +
                        produto.codigo_barras;

                    container.appendChild(codigoBarras);
                }

                const preco =
                    document.createElement('small');

                preco.className =
                    'text-muted';

                preco.textContent =
                    'Preço atual: R$ ' +
                    formatarMoeda(produto.preco);

                container.appendChild(preco);

                return container;
            },

            aoSelecionar: function (produto) {
                produtoIdInput.value =
                    produto.id || '';

                buscaInput.value =
                    produto.nome || '';

                if (descricaoInput) {
                    descricaoInput.value =
                        produto.nome || '';
                }

                if (
                    valorUnitarioInput &&
                    !valorUnitarioInput.value
                ) {
                    valorUnitarioInput.value =
                        String(produto.preco || 0);

                    formatarPreco(
                        valorUnitarioInput
                    );
                }

                mostrarProdutoSelecionado(
                    item,
                    produto
                );

                calcularTotais();
            }
        });
    }

    function adicionarEventosItem(item) {
        configurarBuscaProduto(item);

        const valorUnitarioInput =
            item.querySelector(
                '.item-valor-unitario'
            );

        const quantidadeInput =
            item.querySelector(
                '.item-quantidade'
            );

        const descontoInput =
            item.querySelector(
                '.item-desconto'
            );

        if (valorUnitarioInput) {
            inicializarMascaraPreco(
                valorUnitarioInput
            );

            formatarPreco(
                valorUnitarioInput
            );

            valorUnitarioInput.addEventListener(
                'input',
                calcularTotais
            );
        }

        [
            quantidadeInput,
            descontoInput
        ].forEach(function (input) {
            if (input) {
                input.addEventListener(
                    'input',
                    calcularTotais
                );
            }
        });

        const btnLimparProduto =
            item.querySelector(
                '.btn-limpar-produto'
            );

        if (btnLimparProduto) {
            btnLimparProduto.addEventListener(
                'click',
                function () {
                    limparProduto(item);
                }
            );
        }

        const btnRemover =
            item.querySelector(
                '.btn-remover-item'
            );

        if (btnRemover) {
            btnRemover.addEventListener(
                'click',
                function () {
                    const itens =
                        document.querySelectorAll(
                            '.compra-item'
                        );

                    if (itens.length <= 1) {
                        alert(
                            'A compra deve possuir pelo menos um produto.'
                        );

                        return;
                    }

                    item.remove();

                    atualizarNumeracao();
                    calcularTotais();
                }
            );
        }
    }

    function criarItem() {
        const index =
            proximoIndice++;

        const item =
            document.createElement('div');

        item.className =
            'card shadow-sm compra-item';

        item.dataset.itemIndex =
            index;

        item.innerHTML = `
            <div class="card-body">

                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">

                    <h3 class="h6 mb-0">

                        <i class="bi bi-box"></i>

                        Item

                        <span class="item-numero">1</span>

                    </h3>

                    <button
                        type="button"
                        class="btn btn-danger btn-sm btn-remover-item"
                        title="Remover item"
                    >

                        <i class="bi bi-trash"></i>

                    </button>

                </div>

                <div class="row g-3">

                    <div class="col-12">

                        <label class="form-label">

                            <i class="bi bi-box-seam"></i>

                            Produto *

                        </label>

                        <div class="position-relative">

                            <div class="input-group">

                                <span class="input-group-text">

                                    <i class="bi bi-search"></i>

                                </span>

                                <input
                                    type="search"
                                    class="form-control item-produto-busca"
                                    placeholder="Digite nome, código ou código de barras..."
                                    autocomplete="off"
                                >

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-limpar-produto"
                                    title="Limpar produto"
                                >

                                    <i class="bi bi-x-lg"></i>

                                </button>

                            </div>

                            <div
                                class="list-group position-absolute w-100 item-produto-resultados shadow-sm"
                                style="z-index: 1050;"
                            ></div>

                        </div>

                        <div class="item-produto-status small text-muted mt-1">

                            Digite para pesquisar.

                        </div>

                        <div class="item-produto-selecionado mt-2 d-none"></div>

                        <input
                            type="hidden"
                            name="itens[${index}][produto_id]"
                            class="item-produto-id"
                            value=""
                            required
                        >

                    </div>

                    <div class="col-12 col-md-6">

                        <label class="form-label">

                            <i class="bi bi-card-text"></i>

                            Descrição *

                        </label>

                        <input
                            type="text"
                            name="itens[${index}][descricao]"
                            class="form-control item-descricao"
                            maxlength="255"
                            required
                        >

                    </div>

                    <div class="col-12 col-md-4">

                        <label class="form-label">

                            <i class="bi bi-boxes"></i>

                            Quantidade *

                        </label>

                        <input
                            type="number"
                            name="itens[${index}][quantidade]"
                            class="form-control item-quantidade"
                            value="1"
                            min="0.001"
                            step="0.001"
                            required
                        >

                    </div>

                    <div class="col-12 col-md-4">

                        <label class="form-label">

                            <i class="bi bi-currency-dollar"></i>

                            Valor unitário *

                        </label>

                        <input
                            type="text"
                            inputmode="numeric"
                            name="itens[${index}][valor_unitario]"
                            class="form-control item-valor-unitario"
                            placeholder="0,00"
                            autocomplete="off"
                            required
                        >

                    </div>

                    <div class="col-12 col-md-4">

                        <label class="form-label">

                            <i class="bi bi-percent"></i>

                            Desconto

                        </label>

                        <input
                            type="number"
                            name="itens[${index}][desconto]"
                            class="form-control item-desconto"
                            value="0"
                            min="0"
                            step="0.01"
                        >

                    </div>

                    <div class="col-12">

                        <div class="d-flex justify-content-end">

                            <div class="text-end">

                                <small class="text-muted">

                                    Total do item

                                </small>

                                <div class="fw-bold item-valor-total">

                                    R$ 0,00

                                </div>

                            </div>

                        </div>

                        <input
                            type="hidden"
                            name="itens[${index}][valor_total]"
                            class="item-valor-total-input"
                            value="0"
                        >

                    </div>

                </div>

            </div>
        `;

        itensContainer.appendChild(item);

        adicionarEventosItem(item);

        atualizarNumeracao();

        calcularTotais();
    }

    document
        .querySelectorAll('.compra-item')
        .forEach(function (item) {
            adicionarEventosItem(item);
        });

    const campoDesconto =
        document.getElementById('desconto');

    const campoFrete =
        document.getElementById('frete');

    const campoOutrasDespesas =
        document.getElementById('outras_despesas');

    if (campoDesconto) {
        campoDesconto.addEventListener(
            'input',
            calcularTotais
        );
    }

    if (campoFrete) {
        campoFrete.addEventListener(
            'input',
            calcularTotais
        );
    }

    if (campoOutrasDespesas) {
        campoOutrasDespesas.addEventListener(
            'input',
            calcularTotais
        );
    }

    btnAdicionarItem.addEventListener(
        'click',
        criarItem
    );

    calcularTotais();

    /* ANEXOS */

    const anexosContainer =
        document.getElementById(
            'anexos-container'
        );

    const btnAdicionarAnexo =
        document.getElementById(
            'btn-adicionar-anexo'
        );

    function atualizarIndicesAnexos() {
        if (!anexosContainer) {
            return;
        }

        anexosContainer
            .querySelectorAll('.anexo-item')
            .forEach(function (item, index) {

                const tipo =
                    item.querySelector(
                        '[name$="[tipo]"]'
                    );

                const arquivo =
                    item.querySelector(
                        '[name$="[arquivo]"]'
                    );

                const observacoes =
                    item.querySelector(
                        '[name$="[observacoes]"]'
                    );

                if (tipo) {
                    tipo.name =
                        `anexos[${index}][tipo]`;
                }

                if (arquivo) {
                    arquivo.name =
                        `anexos[${index}][arquivo]`;
                }

                if (observacoes) {
                    observacoes.name =
                        `anexos[${index}][observacoes]`;
                }
            });
    }

    function limparAnexo(item) {
        const tipo =
            item.querySelector('select');

        const arquivo =
            item.querySelector(
                'input[type="file"]'
            );

        const observacoes =
            item.querySelector(
                'input[type="text"]'
            );

        if (tipo) {
            tipo.value = '';
        }

        if (arquivo) {
            arquivo.value = '';
        }

        if (observacoes) {
            observacoes.value = '';
        }
    }

    if (
        btnAdicionarAnexo &&
        anexosContainer
    ) {
        btnAdicionarAnexo.addEventListener(
            'click',
            function () {

                const modelo =
                    anexosContainer.querySelector(
                        '.anexo-item'
                    );

                if (!modelo) {
                    return;
                }

                const novoAnexo =
                    modelo.cloneNode(true);

                limparAnexo(novoAnexo);

                anexosContainer.appendChild(
                    novoAnexo
                );

                atualizarIndicesAnexos();
            }
        );

        anexosContainer.addEventListener(
            'click',
            function (event) {

                const botao =
                    event.target.closest(
                        '.btn-remover-anexo'
                    );

                if (!botao) {
                    return;
                }

                const item =
                    botao.closest(
                        '.anexo-item'
                    );

                const itens =
                    anexosContainer.querySelectorAll(
                        '.anexo-item'
                    );

                if (!item) {
                    return;
                }

                if (itens.length === 1) {
                    limparAnexo(item);
                    return;
                }

                item.remove();

                atualizarIndicesAnexos();
            }
        );

        atualizarIndicesAnexos();
    }
});

