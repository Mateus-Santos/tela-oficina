document.addEventListener('DOMContentLoaded', function () {
    const itensContainer = document.getElementById('itens-container');
    const btnAdicionarItem = document.getElementById('btn-adicionar-item');

    if (!itensContainer || !btnAdicionarItem) {
        return;
    }

    const produtos = window.compraProdutos || [];
    let proximoIndice = document.querySelectorAll('.compra-item').length;

    function formatarMoeda(valor) {
        return Number(valor || 0).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function calcularItem(item) {
        const campoQuantidade = item.querySelector('.item-quantidade');
        const campoValorUnitario = item.querySelector('.item-valor-unitario');
        const campoDesconto = item.querySelector('.item-desconto');

        const quantidade = campoQuantidade ? parseFloat(campoQuantidade.value) || 0 : 0;
        const valorUnitario = campoValorUnitario ? parseFloat(campoValorUnitario.value) || 0 : 0;
        const desconto = campoDesconto ? parseFloat(campoDesconto.value) || 0 : 0;

        const total = Math.max(0, (quantidade * valorUnitario) - desconto);
        const exibicao = item.querySelector('.item-valor-total');
        const input = item.querySelector('.item-valor-total-input');

        if (exibicao) {
            exibicao.textContent = 'R$ ' + formatarMoeda(total);
        }

        if (input) {
            input.value = total.toFixed(2);
        }

        return total;
    }

    function calcularTotais() {
        let valorProdutos = 0;

        document.querySelectorAll('.compra-item').forEach(function (item) {
            valorProdutos += calcularItem(item);
        });

        const campoDesconto = document.getElementById('desconto');
        const campoFrete = document.getElementById('frete');
        const campoOutrasDespesas = document.getElementById('outras_despesas');

        const desconto = campoDesconto ? parseFloat(campoDesconto.value) || 0 : 0;
        const frete = campoFrete ? parseFloat(campoFrete.value) || 0 : 0;
        const outrasDespesas = campoOutrasDespesas ? parseFloat(campoOutrasDespesas.value) || 0 : 0;

        const valorTotal = Math.max(
            0,
            valorProdutos - desconto + frete + outrasDespesas
        );

        const valorProdutosInput = document.getElementById('valor_produtos');
        const valorTotalInput = document.getElementById('valor_total');
        const valorTotalExibicao = document.getElementById('valor-total-exibicao');

        if (valorProdutosInput) {
            valorProdutosInput.value = valorProdutos.toFixed(2);
        }

        if (valorTotalInput) {
            valorTotalInput.value = valorTotal.toFixed(2);
        }

        if (valorTotalExibicao) {
            valorTotalExibicao.textContent = 'R$ ' + formatarMoeda(valorTotal);
        }
    }

    function atualizarNumeracao() {
        document.querySelectorAll('.compra-item').forEach(function (item, index) {
            const numero = item.querySelector('.item-numero');

            if (numero) {
                numero.textContent = index + 1;
            }
        });
    }

    function adicionarEventosItem(item) {
        const produtoSelect = item.querySelector('.item-produto');
        const descricaoInput = item.querySelector('.item-descricao');
        const valorUnitarioInput = item.querySelector('.item-valor-unitario');
        const quantidadeInput = item.querySelector('.item-quantidade');
        const descontoInput = item.querySelector('.item-desconto');

        if (produtoSelect) {
            produtoSelect.addEventListener('change', function () {
                const option = produtoSelect.options[produtoSelect.selectedIndex];

                if (!option || !option.value) {
                    return;
                }

                if (descricaoInput && !descricaoInput.value) {
                    descricaoInput.value = option.dataset.descricao || '';
                }

                if (valorUnitarioInput && !valorUnitarioInput.value) {
                    valorUnitarioInput.value = option.dataset.preco || '';
                }

                calcularTotais();
            });
        }

        [
            quantidadeInput,
            valorUnitarioInput,
            descontoInput
        ].forEach(function (input) {
            if (input) {
                input.addEventListener('input', calcularTotais);
            }
        });

        const btnRemover = item.querySelector('.btn-remover-item');

        if (btnRemover) {
            btnRemover.addEventListener('click', function () {
                const itens = document.querySelectorAll('.compra-item');

                if (itens.length <= 1) {
                    alert('A compra deve possuir pelo menos um produto.');
                    return;
                }

                item.remove();
                atualizarNumeracao();
                calcularTotais();
            });
        }
    }

    function criarItem() {
        const index = proximoIndice++;
        const item = document.createElement('div');

        item.className = 'card shadow-sm compra-item';
        item.dataset.itemIndex = index;

        let options = '<option value="">Selecione o produto</option>';

        produtos.forEach(function (produto) {
            options += `
                <option
                    value="${produto.id}"
                    data-descricao="${escapeHtml(produto.nome)}"
                    data-preco="${produto.preco_uni}"
                >
                    ${escapeHtml(produto.nome)}
                    ${
                        produto.codigo_fabricante
                            ? ' — ' + escapeHtml(produto.codigo_fabricante)
                            : ''
                    }
                </option>
            `;
        });

        item.innerHTML = `
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <h3 class="h6 mb-0">
                        <i class="bi bi-box"></i>
                        Item <span class="item-numero">1</span>
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
                    <div class="col-12 col-md-6">
                        <label class="form-label">
                            <i class="bi bi-box-seam"></i>
                            Produto *
                        </label>
                        <select
                            name="itens[${index}][produto_id]"
                            class="form-select item-produto"
                            required
                        >
                            ${options}
                        </select>
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

                    <div class="col-12 col-md-3">
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

                    <div class="col-12 col-md-3">
                        <label class="form-label">
                            <i class="bi bi-check2-square"></i>
                            Quantidade conferida
                        </label>
                        <input
                            type="number"
                            name="itens[${index}][quantidade_conferida]"
                            class="form-control"
                            min="0"
                            step="0.001"
                        >
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label">
                            <i class="bi bi-currency-dollar"></i>
                            Valor unitário *
                        </label>
                        <input
                            type="number"
                            name="itens[${index}][valor_unitario]"
                            class="form-control item-valor-unitario"
                            min="0"
                            step="0.01"
                            required
                        >
                    </div>

                    <div class="col-12 col-md-3">
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

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }

    document
        .querySelectorAll('.compra-item')
        .forEach(adicionarEventosItem);

    const campoDesconto = document.getElementById('desconto');
    const campoFrete = document.getElementById('frete');
    const campoOutrasDespesas = document.getElementById('outras_despesas');

    if (campoDesconto) {
        campoDesconto.addEventListener('input', calcularTotais);
    }

    if (campoFrete) {
        campoFrete.addEventListener('input', calcularTotais);
    }

    if (campoOutrasDespesas) {
        campoOutrasDespesas.addEventListener('input', calcularTotais);
    }

    btnAdicionarItem.addEventListener('click', criarItem);

    calcularTotais();

    /* ANEXOS */

    const anexosContainer = document.getElementById('anexos-container');
    const btnAdicionarAnexo = document.getElementById('btn-adicionar-anexo');

    function atualizarIndicesAnexos() {
        if (!anexosContainer) {
            return;
        }

        anexosContainer.querySelectorAll('.anexo-item').forEach(function (item, index) {
            const tipo = item.querySelector('[name$="[tipo]"]');
            const arquivo = item.querySelector('[name$="[arquivo]"]');
            const observacoes = item.querySelector('[name$="[observacoes]"]');

            if (tipo) {
                tipo.name = `anexos[${index}][tipo]`;
            }

            if (arquivo) {
                arquivo.name = `anexos[${index}][arquivo]`;
            }

            if (observacoes) {
                observacoes.name = `anexos[${index}][observacoes]`;
            }
        });
    }

    function limparAnexo(item) {
        const tipo = item.querySelector('select');
        const arquivo = item.querySelector('input[type="file"]');
        const observacoes = item.querySelector('input[type="text"]');

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

    if (btnAdicionarAnexo && anexosContainer) {
        btnAdicionarAnexo.addEventListener('click', function () {
            const modelo = anexosContainer.querySelector('.anexo-item');

            if (!modelo) {
                return;
            }

            const novoAnexo = modelo.cloneNode(true);

            limparAnexo(novoAnexo);
            anexosContainer.appendChild(novoAnexo);
            atualizarIndicesAnexos();
        });

        anexosContainer.addEventListener('click', function (event) {
            const botao = event.target.closest('.btn-remover-anexo');

            if (!botao) {
                return;
            }

            const item = botao.closest('.anexo-item');
            const itens = anexosContainer.querySelectorAll('.anexo-item');

            if (!item) {
                return;
            }

            if (itens.length === 1) {
                limparAnexo(item);
                return;
            }

            item.remove();
            atualizarIndicesAnexos();
        });

        atualizarIndicesAnexos();
    }
});
