const LIMITE_MAXIMO_PARCELAS = 120;

function totalEmCentavos(valor) {
    const numero = Number(valor);
    return Number.isFinite(numero) ? Math.round(numero * 100) : 0;
}

function formatarMoeda(valor) {
    return Number(valor).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });
}

function formatarData(valor) {
    if (!valor) {
        return '';
    }

    const partes = valor.split('-');
    return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : '';
}

function obterData(valor) {
    if (!valor) {
        return null;
    }

    const data = new Date(`${valor}T00:00:00`);
    return Number.isNaN(data.getTime()) ? null : data;
}

function formatarDataInput(data) {
    if (!(data instanceof Date) || Number.isNaN(data.getTime())) {
        return '';
    }

    const ano = data.getFullYear();
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const dia = String(data.getDate()).padStart(2, '0');

    return `${ano}-${mes}-${dia}`;
}

function adicionarDias(valor, dias) {
    const data = obterData(valor);

    if (!data) {
        return '';
    }

    data.setDate(data.getDate() + dias);

    return formatarDataInput(data);
}

function obterQuantidade(quantidadeInput) {
    if (!quantidadeInput) {
        return 1;
    }

    const valor = Number.parseInt(quantidadeInput.value || '1', 10);

    if (!Number.isFinite(valor)) {
        return 1;
    }

    return Math.min(LIMITE_MAXIMO_PARCELAS, Math.max(1, valor));
}

function obterIntervalo(intervaloInput) {
    if (!intervaloInput) {
        return 30;
    }

    const valor = Number.parseInt(intervaloInput.value || '30', 10);

    if (!Number.isFinite(valor)) {
        return 30;
    }

    return Math.max(1, valor);
}

function calcularValores(total, quantidade) {
    const totalCentavos = totalEmCentavos(total);

    if (quantidade <= 0) {
        return [];
    }

    const valorBase = Math.floor(totalCentavos / quantidade);
    const restante = totalCentavos - (valorBase * quantidade);
    const valores = [];

    for (let indice = 0; indice < quantidade; indice += 1) {
        const valorCentavos = valorBase + (indice < restante ? 1 : 0);
        valores.push(valorCentavos / 100);
    }

    return valores;
}

function calcularDatasAutomaticas(quantidade, primeiraData, intervalo) {
    const datas = [];

    if (!primeiraData) {
        for (let indice = 0; indice < quantidade; indice += 1) {
            datas.push('');
        }

        return datas;
    }

    for (let indice = 0; indice < quantidade; indice += 1) {
        datas.push(adicionarDias(primeiraData, indice * intervalo));
    }

    return datas;
}

function ajustarQuantidadePersonalizada(datas, quantidade) {
    const resultado = Array.isArray(datas) ? datas.slice(0, quantidade) : [];

    while (resultado.length < quantidade) {
        resultado.push('');
    }

    return resultado;
}

function inicializarParcelas({
    quantidadeInput,
    primeiraDataInput,
    intervaloInput,
    previewElement,
    totalElement,
    valorTotal,
}) {
    if (!previewElement) {
        return {
            atualizar: function () {},
            calcularParcelas: function () {
                return [];
            },
            obterParcelas: function () {
                return [];
            },
            atualizarDataPersonalizada: function () {},
            definirValorTotal: function () {},
            reset: function () {},
        };
    }

    let valorTotalAtual = Number(valorTotal || 0);
    let personalizarVencimentos = false;
    let datasPersonalizadas = [];
    let quantidadeAnterior = obterQuantidade(quantidadeInput);
    let controlePersonalizacao = null;
    let checkboxPersonalizacao = null;

    function obterQuantidadeAtual() {
        return obterQuantidade(quantidadeInput);
    }

    function obterIntervaloAtual() {
        return obterIntervalo(intervaloInput);
    }

    function obterPrimeiraDataAtual() {
        return primeiraDataInput ? primeiraDataInput.value || '' : '';
    }

    function obterDatasAutomaticasAtuais(quantidade) {
        return calcularDatasAutomaticas(
            quantidade,
            obterPrimeiraDataAtual(),
            obterIntervaloAtual()
        );
    }

    function garantirQuantidadePersonalizada(quantidade) {
        datasPersonalizadas = ajustarQuantidadePersonalizada(
            datasPersonalizadas,
            quantidade
        );
    }

    function atualizarEstadoCamposAutomaticos() {
        const desabilitado = personalizarVencimentos;

        if (primeiraDataInput) {
            primeiraDataInput.disabled = desabilitado;
        }

        if (intervaloInput) {
            intervaloInput.disabled = desabilitado;
        }
    }

    function validarDatasPersonalizadas(exibirMensagem) {
        let primeiraDataInvalida = null;

        for (let indice = 0; indice < datasPersonalizadas.length; indice += 1) {
            const valorAtual = datasPersonalizadas[indice];

            if (!valorAtual) {
                primeiraDataInvalida = indice;
                break;
            }

            const dataAtual = obterData(valorAtual);

            if (!dataAtual) {
                primeiraDataInvalida = indice;
                break;
            }

            if (indice > 0) {
                const valorAnterior = datasPersonalizadas[indice - 1];

                if (!valorAnterior) {
                    primeiraDataInvalida = indice;
                    break;
                }

                const dataAnterior = obterData(valorAnterior);

                if (dataAnterior && dataAtual.getTime() < dataAnterior.getTime()) {
                    primeiraDataInvalida = indice;
                    break;
                }
            }
        }

        const campos = previewElement.querySelectorAll('[data-parcela-data-index]');

        campos.forEach(function (campo) {
            campo.classList.remove('is-invalid');
        });

        const mensagem = previewElement.querySelector('[data-parcelas-validacao]');

        if (primeiraDataInvalida !== null) {
            const campo = previewElement.querySelector(
                `[data-parcela-data-index="${primeiraDataInvalida}"]`
            );

            if (campo) {
                campo.classList.add('is-invalid');
            }

            if (mensagem && exibirMensagem) {
                mensagem.textContent =
                    'Preencha todas as datas e mantenha as parcelas em ordem cronológica.';
                mensagem.classList.remove('d-none');
            }

            return false;
        }

        if (mensagem) {
            mensagem.classList.add('d-none');
            mensagem.textContent = '';
        }

        return true;
    }

    function criarMensagemValidacao() {
        const mensagem = document.createElement('div');

        mensagem.setAttribute('data-parcelas-validacao', '');
        mensagem.className = 'alert alert-danger py-2 mb-3 d-none';
        mensagem.textContent =
            'Preencha todas as datas e mantenha as parcelas em ordem cronológica.';

        return mensagem;
    }

    function criarControlePersonalizacao() {
        if (controlePersonalizacao) {
            return;
        }

        controlePersonalizacao = document.createElement('div');
        controlePersonalizacao.setAttribute('data-parcelas-personalizacao', '');
        controlePersonalizacao.className = 'mb-3';

        const divSwitch = document.createElement('div');
        divSwitch.className = 'form-check form-switch d-flex align-items-center gap-2';

        checkboxPersonalizacao = document.createElement('input');
        checkboxPersonalizacao.type = 'checkbox';
        checkboxPersonalizacao.className = 'form-check-input';
        checkboxPersonalizacao.id =
            `personalizar-vencimentos-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
        checkboxPersonalizacao.checked = personalizarVencimentos;

        const label = document.createElement('label');
        label.className = 'form-check-label';
        label.setAttribute('for', checkboxPersonalizacao.id);
        label.textContent = 'Personalizar data de vencimento de cada parcela';

        checkboxPersonalizacao.addEventListener('change', function () {
            const quantidade = obterQuantidadeAtual();

            if (checkboxPersonalizacao.checked) {
                if (datasPersonalizadas.length === 0) {
                    datasPersonalizadas = obterDatasAutomaticasAtuais(quantidade);
                } else {
                    garantirQuantidadePersonalizada(quantidade);
                }

                personalizarVencimentos = true;
            } else {
                personalizarVencimentos = false;
                datasPersonalizadas = obterDatasAutomaticasAtuais(quantidade);
            }

            atualizar();
        });

        divSwitch.appendChild(checkboxPersonalizacao);
        divSwitch.appendChild(label);
        controlePersonalizacao.appendChild(divSwitch);

        const parentElement = previewElement.parentElement;

        if (!parentElement) {
            return;
        }

        const referencia = parentElement.querySelector('.card');

        if (referencia) {
            parentElement.insertBefore(controlePersonalizacao, referencia);
        } else {
            parentElement.insertBefore(controlePersonalizacao, previewElement);
        }
    }

    function sincronizarControlePersonalizacao() {
        criarControlePersonalizacao();

        if (checkboxPersonalizacao) {
            checkboxPersonalizacao.checked = personalizarVencimentos;
        }

        atualizarEstadoCamposAutomaticos();
    }

    function renderizarAutomatico(parcelas) {
        const tabela = document.createElement('div');
        tabela.className = 'table-responsive';

        const table = document.createElement('table');
        table.className = 'table table-sm table-bordered align-middle mb-0';

        const thead = document.createElement('thead');
        thead.innerHTML = `
            <tr>
                <th>Parcela</th>
                <th>Vencimento</th>
                <th class="text-end">Valor</th>
            </tr>
        `;

        const tbody = document.createElement('tbody');

        parcelas.forEach(function (parcela) {
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>${parcela.numero}</td>
                <td>${formatarData(parcela.data_vencimento)}</td>
                <td class="text-end">${formatarMoeda(parcela.valor)}</td>
            `;

            tbody.appendChild(tr);
        });

        table.appendChild(thead);
        table.appendChild(tbody);
        tabela.appendChild(table);
        previewElement.appendChild(tabela);
    }

    function renderizarPersonalizado(parcelas) {
        const mensagem = criarMensagemValidacao();
        previewElement.appendChild(mensagem);

        const tabela = document.createElement('div');
        tabela.className = 'table-responsive';

        const table = document.createElement('table');
        table.className = 'table table-sm table-bordered align-middle mb-0';

        const thead = document.createElement('thead');
        thead.innerHTML = `
            <tr>
                <th style="width: 90px;">Parcela</th>
                <th>Data de vencimento</th>
                <th class="text-end">Valor</th>
            </tr>
        `;

        const tbody = document.createElement('tbody');

        parcelas.forEach(function (parcela, indice) {
            const tr = document.createElement('tr');

            const tdNumero = document.createElement('td');
            tdNumero.textContent = parcela.numero;

            const tdData = document.createElement('td');

            const input = document.createElement('input');
            input.type = 'date';
            input.className = 'form-control form-control-sm';
            input.value = datasPersonalizadas[indice] || '';
            input.setAttribute('data-parcela-data-index', String(indice));

            input.addEventListener('change', function () {
                datasPersonalizadas[indice] = input.value || '';
                validarDatasPersonalizadas(true);
            });

            input.addEventListener('input', function () {
                datasPersonalizadas[indice] = input.value || '';
                validarDatasPersonalizadas(false);
            });

            tdData.appendChild(input);

            const tdValor = document.createElement('td');
            tdValor.className = 'text-end';
            tdValor.textContent = formatarMoeda(parcela.valor);

            tr.appendChild(tdNumero);
            tr.appendChild(tdData);
            tr.appendChild(tdValor);

            tbody.appendChild(tr);
        });

        table.appendChild(thead);
        table.appendChild(tbody);
        tabela.appendChild(table);
        previewElement.appendChild(tabela);

        validarDatasPersonalizadas(false);
    }

    function calcularParcelas() {
        const quantidade = obterQuantidadeAtual();

        if (quantidade <= 0) {
            return [];
        }

        const valores = calcularValores(valorTotalAtual, quantidade);

        let datas;

        if (personalizarVencimentos) {
            garantirQuantidadePersonalizada(quantidade);
            datas = datasPersonalizadas.slice();
        } else {
            datas = obterDatasAutomaticasAtuais(quantidade);
        }

        const parcelas = [];

        for (let indice = 0; indice < quantidade; indice += 1) {
            parcelas.push({
                numero: indice + 1,
                valor: valores[indice],
                data_vencimento: datas[indice] || '',
            });
        }

        return parcelas;
    }

    function renderizar() {
        const quantidade = obterQuantidadeAtual();

        if (
            quantidade !== quantidadeAnterior &&
            personalizarVencimentos
        ) {
            garantirQuantidadePersonalizada(quantidade);
        }

        quantidadeAnterior = quantidade;

        sincronizarControlePersonalizacao();

        previewElement.innerHTML = '';

        const parcelas = calcularParcelas();

        if (!parcelas.length) {
            previewElement.innerHTML =
                '<div class="alert alert-warning mb-0">Nenhuma parcela configurada.</div>';
            return;
        }

        if (personalizarVencimentos) {
            renderizarPersonalizado(parcelas);
        } else {
            renderizarAutomatico(parcelas);
        }
    }

    function atualizar() {
        renderizar();

        if (totalElement) {
            totalElement.textContent = formatarMoeda(valorTotalAtual);
        }
    }

    function atualizarDataPersonalizada(indice, valor) {
        if (!personalizarVencimentos) {
            return;
        }

        if (indice < 0 || indice >= datasPersonalizadas.length) {
            return;
        }

        datasPersonalizadas[indice] = valor || '';
        renderizar();
    }

    function definirValorTotal(valor) {
        const numero = Number(valor);

        valorTotalAtual = Number.isFinite(numero)
            ? Math.max(0, numero)
            : 0;

        atualizar();
    }

    function reset() {
        personalizarVencimentos = false;
        datasPersonalizadas = [];
        quantidadeAnterior = obterQuantidade(quantidadeInput);

        if (quantidadeInput) {
            quantidadeInput.value = quantidadeAnterior;
        }

        if (checkboxPersonalizacao) {
            checkboxPersonalizacao.checked = false;
        }

        atualizar();
    }

    if (quantidadeInput) {
        quantidadeInput.addEventListener('input', function () {
            const quantidade = obterQuantidadeAtual();
            quantidadeInput.value = quantidade;
            atualizar();
        });

        quantidadeInput.addEventListener('change', function () {
            const quantidade = obterQuantidadeAtual();
            quantidadeInput.value = quantidade;
            atualizar();
        });
    }

    if (primeiraDataInput) {
        primeiraDataInput.addEventListener('change', function () {
            if (!personalizarVencimentos) {
                atualizar();
            }
        });
    }

    if (intervaloInput) {
        intervaloInput.addEventListener('change', function () {
            if (!personalizarVencimentos) {
                atualizar();
            }
        });
    }

    atualizar();

    return {
        atualizar,
        calcularParcelas,
        obterParcelas: calcularParcelas,
        atualizarDataPersonalizada,
        definirValorTotal,
        reset,
    };
}

export default inicializarParcelas;
