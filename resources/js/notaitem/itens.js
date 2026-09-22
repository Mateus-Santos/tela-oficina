import BuscaItens from '../busca/busca-itens.js';

(function () {
    'use strict';

    function iniciarItens() {
        const PRODUTO_TYPE = 'App\\Models\\Produto';
        const ORDEM_SERVICO_TYPE = 'App\\Models\\OrdemServico';
        const tipoInput = document.getElementById('builder_type');
        const buscaInput = document.getElementById('builder_item_busca');
        const resultadosContainer = document.getElementById('builder_resultados');
        const buscaStatus = document.getElementById('builder_busca_status');
        const itemIdInput = document.getElementById('builder_item_id');
        const descricaoInput = document.getElementById('builder_descricao');
        const quantidadeInput = document.getElementById('builder_quantidade');
        const valorInput = document.getElementById('builder_valor_unitario');
        const descontoInput = document.getElementById('builder_desconto');
        const garantiaInput = document.getElementById('builder_garantia_dias');
        const botaoAdicionar = document.getElementById('btn-adicionar-item');
        const tabelaItens = document.getElementById('container-itens-dinamicos');
        const linhaVazia = document.getElementById('linha-vazia');

        if (!tipoInput || !buscaInput || !resultadosContainer || !tabelaItens) {
            console.warn('[SOS Mecânica] Elementos do gerenciador de itens não encontrados.');
            return;
        }

        let indiceItem = 0;
        let itemSelecionado = { tipo: '', id: '', descricao: '', valor: 0 };

        const escaparHtml = function (valor) {
            if (valor == null) return '';
            return String(valor)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };

        function converterNumero(valor) {
            if (valor == null || String(valor).trim() === '') return 0;

            let texto = String(valor).trim();

            if (texto.includes(',')) {
                texto = texto.replace(/\./g, '').replace(',', '.');
            } else if ((texto.match(/\./g) || []).length > 1) {
                texto = texto.replace(/\./g, '');
            }

            const numero = parseFloat(texto);
            return Number.isFinite(numero) ? numero : 0;
        }

        const formatarMoeda = function (valor) {
            const numero = Number(valor);
            return Number.isFinite(numero)
                ? numero.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
                : 'R$ 0,00';
        };

        const formatarNumero = function (valor) {
            const numero = Number(valor);
            return Number.isFinite(numero) ? numero.toFixed(2).replace('.', ',') : '0,00';
        };

        const obterTipoSelecionado = function () {
            return tipoInput.value || '';
        };

        function obterVeiculoClienteId() {
            const elemento = document.getElementById('veiculo_cliente_id');
            return elemento ? elemento.value || '' : '';
        }

        function normalizarTipo(tipo) {
            tipo = String(tipo || '');

            if ([PRODUTO_TYPE, 'Produto', 'produto'].includes(tipo)) return 'produto';
            if ([ORDEM_SERVICO_TYPE, 'OrdemServico', 'os'].includes(tipo)) return 'os';

            return '';
        }

        function obterTipoLinha(linha) {
            if (!linha) return '';

            const input = linha.querySelector(
                'input[name*="[itemable_type]"], input[name*="[tipo]"]'
            );

            return input ? normalizarTipo(input.value) : '';
        }

        const obterItemableType = function (tipo) {
            if (tipo === 'produto') return PRODUTO_TYPE;
            if (tipo === 'os') return ORDEM_SERVICO_TYPE;
            return '';
        };

        const obterCampoLinha = function (linha, seletor) {
            return linha ? linha.querySelector(seletor) : null;
        };

        function obterQuantidadeLinha(linha) {
            const input = obterCampoLinha(linha, '.input-qtd, .item-quantidade');
            return input ? converterNumero(input.value) : 0;
        }

        function obterValorUnitarioLinha(linha) {
            const input = obterCampoLinha(linha, '.input-valor, .item-valor-unitario');
            return input ? converterNumero(input.value) : 0;
        }

        function obterDescontoLinha(linha) {
            const input = obterCampoLinha(linha, '.input-desconto, .item-desconto');
            return input ? Math.max(0, converterNumero(input.value)) : 0;
        }

        function calcularValorTotalItem(quantidade, valorUnitario, desconto) {
            return Math.max(0, quantidade * valorUnitario - Math.max(0, desconto));
        }

        function calcularDadosLinha(linha) {
            const quantidade = obterQuantidadeLinha(linha);
            const valorUnitario = obterValorUnitarioLinha(linha);
            const desconto = obterDescontoLinha(linha);
            const subtotal = quantidade * valorUnitario;

            return {
                tipo: obterTipoLinha(linha),
                quantidade: quantidade,
                valorUnitario: valorUnitario,
                desconto: desconto,
                subtotal: subtotal,
                total: calcularValorTotalItem(quantidade, valorUnitario, desconto)
            };
        }

        function atualizarTotalLinha(linha) {
            if (!linha) return;

            const elemento = linha.querySelector('.valor-total-item');
            if (elemento) elemento.textContent = formatarMoeda(calcularDadosLinha(linha).total);
        }

        function obterDadosFinanceiros() {
            const dados = {
                produto: { bruto: 0, desconto: 0, liquido: 0 },
                os: { bruto: 0, desconto: 0, liquido: 0 },
                subtotal: 0,
                desconto: 0,
                total: 0
            };

            tabelaItens.querySelectorAll('tr[data-item-index]').forEach(function (linha) {
                const item = calcularDadosLinha(linha);
                if (!item.tipo) return;

                dados[item.tipo].bruto += item.subtotal;
                dados[item.tipo].desconto += item.desconto;
            });

            ['produto', 'os'].forEach(function (tipo) {
                dados[tipo].liquido = Math.max(0, dados[tipo].bruto - dados[tipo].desconto);
            });

            dados.subtotal = dados.produto.bruto + dados.os.bruto;
            dados.desconto = dados.produto.desconto + dados.os.desconto;
            dados.total = dados.produto.liquido + dados.os.liquido;

            return dados;
        }

        function atualizarResumoFinanceiro() {
            tabelaItens.querySelectorAll('tr[data-item-index]').forEach(atualizarTotalLinha);

            const dados = obterDadosFinanceiros();

            const elementos = {
                pecasBruto: 'resumo-pecas-bruto',
                pecasDesconto: 'resumo-pecas-desconto',
                pecasLiquido: 'resumo-pecas-liquido',
                servicosBruto: 'resumo-servicos-bruto',
                servicosDesconto: 'resumo-servicos-desconto',
                servicosLiquido: 'resumo-servicos-liquido',
                totalDescontos: 'resumo-total-descontos',
                totalGeral: 'valor-geral-os'
            };

            const valores = {
                pecasBruto: dados.produto.bruto,
                pecasDesconto: dados.produto.desconto,
                pecasLiquido: dados.produto.liquido,
                servicosBruto: dados.os.bruto,
                servicosDesconto: dados.os.desconto,
                servicosLiquido: dados.os.liquido,
                totalDescontos: dados.desconto,
                totalGeral: dados.total
            };

            Object.entries(elementos).forEach(function (entrada) {
                const chave = entrada[0];
                const elemento = document.getElementById(entrada[1]);

                if (elemento) elemento.textContent = formatarMoeda(valores[chave]);
            });

            return dados;
        }

        window.NotaItens = {
            recalcular: atualizarResumoFinanceiro,
            obterDadosFinanceiros: obterDadosFinanceiros,
            obterSubtotalCategoria: function (tipo) {
                const dados = obterDadosFinanceiros();

                if (tipo === 'produto') return dados.produto.bruto;
                if (tipo === 'os') return dados.os.bruto;

                return 0;
            }
        };

        const limparResultados = function () {
            resultadosContainer.innerHTML = '';
        };

        function limparSelecao() {
            itemSelecionado = { tipo: '', id: '', descricao: '', valor: 0 };
            if (itemIdInput) itemIdInput.value = '';
        }

        function atualizarEstadoBusca() {
            const tipo = obterTipoSelecionado();

            if (!tipo) {
                buscaInput.disabled = true;
                buscaInput.value = '';
                limparResultados();
                limparSelecao();

                if (buscaStatus) buscaStatus.textContent = 'Selecione o tipo de item.';
                return;
            }

            buscaInput.disabled = false;
            if (buscaStatus) buscaStatus.textContent = 'Digite para pesquisar.';
        }

        function selecionarItem(item) {
            const tipo = obterTipoSelecionado();

            if (!tipo || !item) return;

            if (tipo === 'os' && item.disponivel === false) {
                const notaId = item.nota && item.nota.id ? item.nota.id : null;
                const mensagem = notaId
                    ? 'Esta O.S. já está vinculada à Nota #' + notaId + '.'
                    : 'Esta O.S. já está vinculada a uma Nota.';

                if (buscaStatus) buscaStatus.textContent = mensagem;
                return;
            }

            const descricao = tipo === 'produto'
                ? item.nome || ''
                : item.descricao || '';

            const valor = converterNumero(
                tipo === 'produto' ? item.preco : item.valor
            );

            itemSelecionado = {
                tipo: tipo,
                id: item.id,
                descricao: descricao,
                valor: valor
            };

            if (itemIdInput) itemIdInput.value = item.id;
            if (descricaoInput) descricaoInput.value = descricao;
            if (valorInput) valorInput.value = valor.toFixed(2);
            if (descontoInput) descontoInput.value = '0.00';

            if (buscaInput) {
                buscaInput.value = tipo === 'produto'
                    ? item.nome || ''
                    : 'O.S. #' + item.id + ' - ' + descricao;
            }

            limparResultados();

            if (buscaStatus) buscaStatus.textContent = 'Item selecionado.';

            atualizarPreviaItem();
        }

        function atualizarPreviaItem() {
            const totalElemento = document.getElementById('builder_total');
            if (!totalElemento) return;

            const quantidade = quantidadeInput ? converterNumero(quantidadeInput.value) : 0;
            const valor = valorInput ? converterNumero(valorInput.value) : 0;
            const desconto = descontoInput ? converterNumero(descontoInput.value) : 0;

            totalElemento.textContent = formatarMoeda(
                calcularValorTotalItem(quantidade, valor, desconto)
            );
        }

        function validarItem() {
            if (!obterTipoSelecionado()) {
                alert('Selecione o tipo do item.');
                return false;
            }

            if (!itemIdInput || !itemIdInput.value) {
                alert('Selecione um item da pesquisa.');
                return false;
            }

            if (!descricaoInput || !descricaoInput.value.trim()) {
                alert('Informe a descrição do item.');
                return false;
            }

            const quantidade = quantidadeInput ? converterNumero(quantidadeInput.value) : 0;

            if (!Number.isInteger(quantidade) || quantidade < 1) {
                alert('A quantidade deve ser um número inteiro maior ou igual a 1.');
                if (quantidadeInput) quantidadeInput.focus();
                return false;
            }

            const valor = valorInput ? converterNumero(valorInput.value) : 0;

            if (valor < 0) {
                alert('O valor do item não pode ser negativo.');
                if (valorInput) valorInput.focus();
                return false;
            }

            const desconto = descontoInput
                ? Math.max(0, converterNumero(descontoInput.value))
                : 0;

            if (desconto > quantidade * valor) {
                alert('O desconto não pode ser maior que o valor do item.');
                if (descontoInput) descontoInput.focus();
                return false;
            }

            return true;
        }

        function adicionarItem() {
            if (!validarItem()) return;

            const tipo = obterTipoSelecionado();
            const itemId = itemIdInput.value;
            const descricao = descricaoInput.value.trim();
            const quantidade = Math.trunc(converterNumero(quantidadeInput.value));
            const valorUnitario = converterNumero(valorInput.value);
            const desconto = descontoInput
                ? Math.max(0, converterNumero(descontoInput.value))
                : 0;
            const garantiaDias = garantiaInput
                ? parseInt(garantiaInput.value, 10) || 0
                : 0;
            const index = indiceItem++;
            const valorTotal = calcularValorTotalItem(quantidade, valorUnitario, desconto);
            const linha = document.createElement('tr');

            linha.dataset.itemIndex = index;

            linha.innerHTML = `
                <td>
                    <input type="hidden" name="itens[${index}][itemable_type]" value="${escaparHtml(obterItemableType(tipo))}">
                    <input type="hidden" name="itens[${index}][itemable_id]" value="${escaparHtml(itemId)}">
                    <strong>${escaparHtml(tipo === 'produto' ? 'Produto' : 'O.S.')}</strong>
                </td>
                <td>
                    <input type="text" name="itens[${index}][descricao]" value="${escaparHtml(descricao)}" class="form-control input-descricao">
                </td>
                <td>
                    <input type="number" name="itens[${index}][quantidade]" value="${quantidade}" min="1" step="1" class="form-control input-qtd">
                </td>
                <td>
                    <input type="text" name="itens[${index}][valor_unitario]" value="${formatarNumero(valorUnitario)}" class="form-control input-valor">
                </td>
                <td>
                    <input type="text" name="itens[${index}][desconto]" value="${formatarNumero(desconto)}" class="form-control input-desconto">
                </td>
                <td>
                    <input type="number" name="itens[${index}][garantia_dias]" value="${garantiaDias > 0 ? garantiaDias : ''}" min="0" step="1" class="form-control input-garantia">
                </td>
                <td class="valor-total-item">${formatarMoeda(valorTotal)}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-remover-item" title="Remover item">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;

            if (linhaVazia) linhaVazia.style.display = 'none';

            tabelaItens.appendChild(linha);
            atualizarResumoFinanceiro();
            limparEditorItem();
            limparBusca();
            fecharModalAdicionarItem();
        }

        function removerItem(linha) {
            if (!linha) return;

            linha.remove();
            verificarTabelaVazia();
            atualizarResumoFinanceiro();
        }

        function verificarTabelaVazia() {
            if (!linhaVazia) return;

            linhaVazia.style.display =
                tabelaItens.querySelectorAll('tr[data-item-index]').length
                    ? 'none'
                    : '';
        }

        function limparEditorItem() {
            itemSelecionado = { tipo: '', id: '', descricao: '', valor: 0 };

            if (itemIdInput) itemIdInput.value = '';
            if (descricaoInput) descricaoInput.value = '';
            if (valorInput) valorInput.value = '';
            if (descontoInput) descontoInput.value = '0.00';

            if (quantidadeInput) {
                quantidadeInput.value = quantidadeInput.defaultValue || '1';
            }

            if (garantiaInput) garantiaInput.value = '';
            if (buscaInput) buscaInput.value = '';

            atualizarPreviaItem();
        }

        function limparBusca() {
            if (buscaItens) {
                buscaItens.limpar();
            } else {
                limparResultados();
                if (buscaInput) buscaInput.value = '';
                if (buscaStatus) buscaStatus.textContent = 'Digite para pesquisar.';
            }
        }

        function fecharModalAdicionarItem() {
            const modalElement = document.getElementById('modalAdicionarItem');

            if (!modalElement || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                return;
            }

            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            modal.hide();
        }

        const buscaItens = new BuscaItens({
            input: buscaInput,
            resultados: resultadosContainer,
            status: buscaStatus,
            endpoint: '/api/notas-itens/buscar',
            debounce: 350,
            minimoCaracteres: 1,

            obterParametros: function (busca) {
                const tipo = obterTipoSelecionado();

                if (!tipo) return null;

                const parametros = { tipo: tipo, q: busca };

                if (tipo === 'os') {
                    const veiculoClienteId = obterVeiculoClienteId();

                    if (veiculoClienteId) {
                        parametros.veiculo_cliente_id = veiculoClienteId;
                    }
                }

                return parametros;
            },

            renderizarItem: function (item) {
                const tipo = obterTipoSelecionado();
                const container = document.createElement('div');

                if (tipo === 'produto') {
                    const nome = item.nome || 'Produto sem nome';
                    const descricao = item.descricao || '';
                    let marca = '';

                    if (item.marca && typeof item.marca === 'object') {
                        marca = item.marca.nome || '';
                    } else {
                        marca = item.marca || item.marca_nome || '';
                    }

                    const codigoFabricante = item.codigo_fabricante || '';
                    const codigoBarras = item.codigo_barras || '';
                    const preco = converterNumero(item.preco);

                    container.innerHTML = `
                        <div class="d-flex align-items-start gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded bg-light text-primary flex-shrink-0" style="width:42px;height:42px;">
                                <i class="bi bi-box-seam fs-5"></i>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <strong class="d-block text-dark">${escaparHtml(nome)}</strong>
                                    <strong class="text-primary text-nowrap">${formatarMoeda(preco)}</strong>
                                </div>
                                ${marca ? `<div class="small text-muted mt-1"><i class="bi bi-bookmark"></i> <span class="fw-semibold">Marca:</span> ${escaparHtml(marca)}</div>` : ''}
                                ${codigoFabricante ? `<div class="small text-muted mt-1"><i class="bi bi-tag"></i> <span class="fw-semibold">Fabricante:</span> ${escaparHtml(codigoFabricante)}</div>` : ''}
                                ${codigoBarras ? `<div class="small text-muted mt-1"><i class="bi bi-upc-scan"></i> <span class="fw-semibold">Código de barras:</span> ${escaparHtml(codigoBarras)}</div>` : ''}
                                ${descricao ? `<div class="small text-muted mt-2" style="display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;overflow:hidden;line-height:1.4;">${escaparHtml(descricao)}</div>` : ''}
                            </div>
                            <div class="d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width:20px;">
                                <i class="bi bi-chevron-right"></i>
                            </div>
                        </div>
                    `;
                } else {
                    const descricao = item.descricao || 'Ordem de Serviço';
                    const valor = converterNumero(item.valor);
                    const disponivel = item.disponivel !== false;
                    const notaId = item.nota && item.nota.id ? item.nota.id : null;
                    const notaStatus = item.nota && item.nota.status ? item.nota.status : '';

                    if (!disponivel) {
                        container.className = 'opacity-75';
                        container.innerHTML = `
                            <div class="d-flex align-items-start gap-3">
                                <div class="d-flex align-items-center justify-content-center rounded bg-light text-secondary flex-shrink-0" style="width:42px;height:42px;">
                                    <i class="bi bi-link-45deg fs-5"></i>
                                </div>
                                <div class="flex-grow-1 min-width-0">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <strong class="d-block text-dark">O.S. #${escaparHtml(item.id)}</strong>
                                        <strong class="text-muted text-nowrap">${formatarMoeda(valor)}</strong>
                                    </div>
                                    <div class="small text-muted mt-2" style="display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;overflow:hidden;line-height:1.4;">
                                        ${escaparHtml(descricao)}
                                    </div>
                                    <div class="mt-2">
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-link-45deg"></i>
                                            Já vinculada${notaId ? ' à Nota #' + escaparHtml(notaId) : ' a uma Nota'}
                                        </span>
                                    </div>
                                    ${notaStatus ? `<div class="small text-muted mt-1">Status da Nota: ${escaparHtml(notaStatus)}</div>` : ''}
                                </div>
                                <div class="d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width:20px;">
                                    <i class="bi bi-lock"></i>
                                </div>
                            </div>
                        `;

                        return container;
                    }

                    container.innerHTML = `
                        <div class="d-flex align-items-start gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded bg-light text-warning flex-shrink-0" style="width:42px;height:42px;">
                                <i class="bi bi-tools fs-5"></i>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <strong class="d-block text-dark">O.S. #${escaparHtml(item.id)}</strong>
                                    <strong class="text-primary text-nowrap">${formatarMoeda(valor)}</strong>
                                </div>
                                <div class="small text-muted mt-2" style="display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;overflow:hidden;line-height:1.4;">
                                    ${escaparHtml(descricao)}
                                </div>
                                ${item.status ? `<div class="mt-2"><span class="badge bg-secondary">${escaparHtml(item.status)}</span></div>` : ''}
                            </div>
                            <div class="d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width:20px;">
                                <i class="bi bi-chevron-right"></i>
                            </div>
                        </div>
                    `;

                    return container;
                }

                return container;
            },

            aoSelecionar: selecionarItem
        });

        tipoInput.addEventListener('change', function () {
            limparSelecao();
            buscaItens.limpar();

            if (descricaoInput) descricaoInput.value = '';
            if (valorInput) valorInput.value = '';
            if (descontoInput) descontoInput.value = '0.00';

            atualizarEstadoBusca();
            atualizarPreviaItem();
        });

        buscaInput.addEventListener('input', limparSelecao);

        buscaInput.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') limparResultados();
        });

        [quantidadeInput, valorInput, descontoInput].forEach(function (input) {
            if (input) input.addEventListener('input', atualizarPreviaItem);
        });

        if (botaoAdicionar) {
            botaoAdicionar.addEventListener('click', adicionarItem);
        }

        const camposCalculaveis =
            '.input-qtd, .input-valor, .input-desconto, .item-quantidade, .item-valor-unitario, .item-desconto';

        ['input', 'change'].forEach(function (evento) {
            tabelaItens.addEventListener(evento, function (event) {
                if (!event.target.matches(camposCalculaveis)) return;

                const linha = event.target.closest('tr[data-item-index]');

                if (linha) atualizarTotalLinha(linha);

                atualizarResumoFinanceiro();
            });
        });

        tabelaItens.addEventListener('click', function (event) {
            const botao = event.target.closest('.btn-remover-item');

            if (botao) removerItem(botao.closest('tr[data-item-index]'));
        });

        const linhasExistentes = tabelaItens.querySelectorAll('tr[data-item-index]');

        if (linhasExistentes.length) {
            indiceItem = Math.max.apply(
                null,
                Array.from(linhasExistentes)
                    .map(function (linha) {
                        return parseInt(linha.dataset.itemIndex, 10);
                    })
                    .filter(Number.isFinite)
            ) + 1;
        }

        const modalAdicionarItem = document.getElementById('modalAdicionarItem');

        if (modalAdicionarItem) {
            modalAdicionarItem.addEventListener('shown.bs.modal', function () {
                atualizarEstadoBusca();

                if (!buscaInput.disabled) buscaInput.focus();
            });

            modalAdicionarItem.addEventListener('hidden.bs.modal', function () {
                limparBusca();
                limparEditorItem();
            });
        }

        verificarTabelaVazia();
        atualizarEstadoBusca();
        atualizarPreviaItem();
        atualizarResumoFinanceiro();

        console.log('[SOS Mecânica] itens.js inicializado com sucesso.');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciarItens, { once: true });
    } else {
        iniciarItens();
    }
})();
