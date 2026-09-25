import BuscaItens from './busca/busca-itens.js';
import inicializarParcelas from './compras/aprovacao/parcelas.js';

function moeda(valor) {
    return Number(valor || 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });
}

function escapeHtml(valor) {
    const div = document.createElement('div');
    div.textContent = String(valor ?? '');
    return div.innerHTML;
}

function iniciarContaReceber() {
    const container = document.getElementById('cadastro-conta-receber');
    const form = document.getElementById('form-conta-receber');

    if (!container || !form) {
        return;
    }

    const clienteBusca = document.getElementById('cliente_busca');
    const clienteId = document.getElementById('cliente_id');
    const clienteSelecionado = document.getElementById('cliente-selecionado');
    const btnLimparCliente = document.getElementById('btn-limpar-cliente');

    const notaBusca = document.getElementById('nota_busca');
    const notaId = document.getElementById('nota_id');
    const notaSelecionada = document.getElementById('nota-selecionada');
    const btnLimparNota = document.getElementById('btn-limpar-nota');

    const descricao = document.getElementById('descricao');
    const valorOriginal = document.getElementById('valor_original');

    const quantidadeInput = document.getElementById('parcelas_quantidade');
    const primeiraDataInput = document.getElementById('primeira_data_vencimento');
    const intervaloInput = document.getElementById('intervalo_parcelas');
    const previewElement = document.getElementById('parcelas-preview');
    const totalElement = document.getElementById('parcelas-total');
    const parcelasHidden = document.getElementById('parcelas-hidden');
    const btnCadastrar = document.getElementById('btn-cadastrar-conta');

    let clienteAtual = null;
    let notaAtual = null;
    let alterandoProgramaticamente = false;

    const parcelas = inicializarParcelas({
        quantidadeInput,
        primeiraDataInput,
        intervaloInput,
        previewElement,
        totalElement,
        valorTotal: Number(valorOriginal?.value || 0),
    });

    function renderizarCliente(cliente) {
        if (!clienteSelecionado) {
            return;
        }

        if (!cliente) {
            clienteSelecionado.classList.add('d-none');
            clienteSelecionado.innerHTML = '';
            return;
        }

        clienteSelecionado.innerHTML = `
            <div class="alert alert-success py-2 mb-0">
                <i class="bi bi-person-check"></i>
                <strong>Cliente selecionado:</strong>
                ${escapeHtml(cliente.nome)}
            </div>
        `;

        clienteSelecionado.classList.remove('d-none');
    }

    function renderizarNota(nota) {
        if (!notaSelecionada) {
            return;
        }

        if (!nota) {
            notaSelecionada.classList.add('d-none');
            notaSelecionada.innerHTML = '';
            return;
        }

        notaSelecionada.innerHTML = `
            <div class="alert alert-primary py-2 mb-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <i class="bi bi-receipt"></i>
                        <strong>Nota #${escapeHtml(nota.numero)}</strong>
                        - ${escapeHtml(nota.cliente_nome)}
                    </div>
                    <strong>${moeda(nota.total)}</strong>
                </div>
            </div>
        `;

        notaSelecionada.classList.remove('d-none');
    }

    function limparNota({ limparBusca = true } = {}) {
        notaAtual = null;

        if (notaId) {
            notaId.value = '';
        }

        if (limparBusca && notaBusca) {
            alterandoProgramaticamente = true;
            notaBusca.value = '';
            alterandoProgramaticamente = false;
        }

        renderizarNota(null);

        if (valorOriginal) {
            valorOriginal.readOnly = false;
        }
    }

    function limparCliente() {
        clienteAtual = null;

        if (clienteId) {
            clienteId.value = '';
        }

        if (clienteBusca) {
            alterandoProgramaticamente = true;
            clienteBusca.value = '';
            alterandoProgramaticamente = false;
        }

        renderizarCliente(null);
        limparNota();
    }

    function selecionarCliente(cliente, { manterNota = false } = {}) {
        clienteAtual = cliente;

        if (clienteId) {
            clienteId.value = cliente.id;
        }

        if (clienteBusca) {
            alterandoProgramaticamente = true;
            clienteBusca.value = cliente.nome;
            alterandoProgramaticamente = false;
        }

        renderizarCliente(cliente);

        if (!manterNota) {
            limparNota();

            if (notaBusca) {
                notaBusca.value = '';
            }

            buscaNotas.buscar();
        }
    }

    function selecionarNota(nota) {
        notaAtual = nota;

        if (notaId) {
            notaId.value = nota.id;
        }

        if (notaBusca) {
            alterandoProgramaticamente = true;
            notaBusca.value = `#${nota.numero}`;
            alterandoProgramaticamente = false;
        }

        const cliente = {
            id: nota.cliente_id,
            nome: nota.cliente_nome,
        };

        selecionarCliente(cliente, {
            manterNota: true,
        });

        if (valorOriginal) {
            valorOriginal.value = Number(nota.total).toFixed(2);
            valorOriginal.readOnly = true;
            parcelas.definirValorTotal(Number(nota.total));
        }

        if (descricao && descricao.value.trim() === '') {
            descricao.value = `Nota #${nota.numero} - ${nota.cliente_nome}`;
        }

        renderizarNota(nota);
    }

    const buscaClientes = new BuscaItens({
        input: clienteBusca,
        resultados: '#cliente-resultados',
        status: '#cliente-status',
        endpoint: container.dataset.clientesEndpoint,
        minimoCaracteres: 2,
        obterParametros(busca) {
            return {
                q: busca,
            };
        },
        renderizarItem(cliente) {
            return `
                <div>
                    <i class="bi bi-person"></i>
                    <strong>${escapeHtml(cliente.nome)}</strong>
                </div>
            `;
        },
        aoSelecionar(cliente) {
            selecionarCliente(cliente);
        },
    });

    const buscaNotas = new BuscaItens({
        input: notaBusca,
        resultados: '#nota-resultados',
        status: '#nota-status',
        endpoint: container.dataset.notasEndpoint,
        minimoCaracteres: 0,
        obterParametros(busca) {
            return {
                q: busca.replace('#', '').trim(),
                cliente_id: clienteId?.value || '',
            };
        },
        renderizarItem(nota) {
            return `
                <div class="d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <strong>Nota #${escapeHtml(nota.numero)}</strong>
                        <div class="small text-muted">
                            ${escapeHtml(nota.cliente_nome)}
                        </div>
                    </div>
                    <strong>${moeda(nota.total)}</strong>
                </div>
            `;
        },
        aoSelecionar(nota) {
            selecionarNota(nota);
        },
    });

    async function carregarJson(url) {
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Erro HTTP ${response.status}`);
        }

        return response.json();
    }

    async function restaurarSelecaoAnterior() {
        const clienteAnterior = clienteId?.value || '';
        const notaAnterior = notaId?.value || '';

        try {
            if (notaAnterior) {
                const url = new URL(
                    container.dataset.notasEndpoint,
                    window.location.origin
                );

                url.searchParams.set('q', notaAnterior);

                const resposta = await carregarJson(url.toString());
                const nota = resposta.data?.find(
                    item => String(item.id) === String(notaAnterior)
                );

                if (nota) {
                    selecionarNota(nota);
                    return;
                }
            }

            if (clienteAnterior) {
                const url = new URL(
                    container.dataset.clientesEndpoint,
                    window.location.origin
                );

                url.searchParams.set('id', clienteAnterior);

                const resposta = await carregarJson(url.toString());
                const cliente = resposta.data?.[0];

                if (cliente) {
                    selecionarCliente(cliente);
                }
            }
        } catch (error) {
            console.error(
                '[SOS Mecânica] Não foi possível restaurar cliente/nota:',
                error
            );
        }
    }

    function limparParcelasHidden() {
        if (parcelasHidden) {
            parcelasHidden.innerHTML = '';
        }
    }

    function preencherParcelasHidden(listaParcelas) {
        limparParcelasHidden();

        listaParcelas.forEach((parcela, indice) => {
            const numero = document.createElement('input');
            numero.type = 'hidden';
            numero.name = `parcelas[${indice}][numero]`;
            numero.value = parcela.numero;

            const valor = document.createElement('input');
            valor.type = 'hidden';
            valor.name = `parcelas[${indice}][valor]`;
            valor.value = Number(parcela.valor).toFixed(2);

            const data = document.createElement('input');
            data.type = 'hidden';
            data.name = `parcelas[${indice}][data_vencimento]`;
            data.value = parcela.data_vencimento;

            parcelasHidden.appendChild(numero);
            parcelasHidden.appendChild(valor);
            parcelasHidden.appendChild(data);
        });
    }

    if (clienteBusca) {
        clienteBusca.addEventListener('input', function () {
            if (alterandoProgramaticamente) {
                return;
            }

            if (
                clienteAtual &&
                clienteBusca.value !== clienteAtual.nome
            ) {
                clienteAtual = null;
                clienteId.value = '';
                renderizarCliente(null);
                limparNota();
            }
        });
    }

    if (notaBusca) {
        notaBusca.addEventListener('input', function () {
            if (alterandoProgramaticamente) {
                return;
            }

            if (notaAtual) {
                limparNota({
                    limparBusca: false,
                });
            }
        });

        notaBusca.addEventListener('focus', function () {
            if (
                clienteId?.value &&
                notaBusca.value.trim() === ''
            ) {
                buscaNotas.buscar();
            }
        });
    }

    if (btnLimparCliente) {
        btnLimparCliente.addEventListener('click', function () {
            limparCliente();
            buscaClientes.limpar();
            buscaNotas.limpar();
            clienteBusca?.focus();
        });
    }

    if (btnLimparNota) {
        btnLimparNota.addEventListener('click', function () {
            limparNota();
            buscaNotas.limpar();

            if (clienteId?.value) {
                buscaNotas.buscar();
            }

            notaBusca?.focus();
        });
    }

    if (valorOriginal) {
        valorOriginal.addEventListener('input', function () {
            if (notaAtual) {
                return;
            }

            parcelas.definirValorTotal(
                Number(valorOriginal.value || 0)
            );
        });

        valorOriginal.addEventListener('change', function () {
            if (notaAtual) {
                return;
            }

            parcelas.definirValorTotal(
                Number(valorOriginal.value || 0)
            );
        });
    }

    form.addEventListener('submit', function (event) {
        const listaParcelas = parcelas.obterParcelas();

        if (!listaParcelas.length) {
            event.preventDefault();
            window.alert('Informe ao menos uma parcela.');
            return;
        }

        if (!clienteId?.value && !notaId?.value) {
            event.preventDefault();
            window.alert('Selecione um cliente ou uma Nota.');
            return;
        }

        const possuiDataInvalida = listaParcelas.some(
            parcela => !parcela.data_vencimento
        );

        if (possuiDataInvalida) {
            event.preventDefault();
            window.alert(
                'Preencha a data de vencimento de todas as parcelas.'
            );
            return;
        }

        for (
            let indice = 1;
            indice < listaParcelas.length;
            indice += 1
        ) {
            if (
                listaParcelas[indice].data_vencimento <
                listaParcelas[indice - 1].data_vencimento
            ) {
                event.preventDefault();
                window.alert(
                    'As datas de vencimento devem estar em ordem cronológica.'
                );
                return;
            }
        }

        preencherParcelasHidden(listaParcelas);

        if (btnCadastrar) {
            btnCadastrar.disabled = true;
            btnCadastrar.innerHTML = `
                <span
                    class="spinner-border spinner-border-sm me-1"
                    aria-hidden="true"
                ></span>
                Cadastrando...
            `;
        }
    });

    parcelas.definirValorTotal(
        Number(valorOriginal?.value || 0)
    );

    restaurarSelecaoAnterior();
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        iniciarContaReceber,
        { once: true }
    );
} else {
    iniciarContaReceber();
}
