export default function inicializarParcelas(configuracao) {
    const {
        valorTotal,
        quantidadeInput,
        primeiraDataInput,
        intervaloInput,
        previewContainer,
        totalElement,
    } = configuracao;

    if (
        !quantidadeInput ||
        !primeiraDataInput ||
        !intervaloInput ||
        !previewContainer
    ) {
        return null;
    }

    const LIMITE_MAXIMO_PARCELAS = 120;

    const totalEmCentavos = Math.round(Number(valorTotal) * 100);

    const formatarMoeda = function (valor) {
        return Number(valor).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    };

    const formatarData = function (data) {
        return data.toLocaleDateString('pt-BR');
    };

    const obterData = function (valor) {
        if (!valor) {
            return null;
        }

        const partes = valor.split('-');

        if (partes.length !== 3) {
            return null;
        }

        const ano = Number(partes[0]);
        const mes = Number(partes[1]);
        const dia = Number(partes[2]);

        if (
            !Number.isInteger(ano) ||
            !Number.isInteger(mes) ||
            !Number.isInteger(dia)
        ) {
            return null;
        }

        const data = new Date(ano, mes - 1, dia);

        if (
            Number.isNaN(data.getTime()) ||
            data.getFullYear() !== ano ||
            data.getMonth() !== mes - 1 ||
            data.getDate() !== dia
        ) {
            return null;
        }

        return data;
    };

    const formatarDataInput = function (data) {
        return (
            data.getFullYear() +
            '-' +
            String(data.getMonth() + 1).padStart(2, '0') +
            '-' +
            String(data.getDate()).padStart(2, '0')
        );
    };

    const adicionarDias = function (data, dias) {
        const novaData = new Date(data.getTime());

        novaData.setDate(novaData.getDate() + Number(dias));

        return novaData;
    };

    const obterQuantidade = function () {
        const quantidade = Number.parseInt(
            quantidadeInput.value,
            10
        );

        if (!Number.isInteger(quantidade) || quantidade < 1) {
            return 1;
        }

        return Math.min(
            quantidade,
            LIMITE_MAXIMO_PARCELAS
        );
    };

    const obterIntervalo = function () {
        const intervalo = Number(intervaloInput.value);

        if (!Number.isFinite(intervalo) || intervalo <= 0) {
            return null;
        }

        return intervalo;
    };

    const calcularValores = function (quantidade) {
        const valorBase = Math.floor(
            totalEmCentavos / quantidade
        );

        const restante = totalEmCentavos % quantidade;

        return Array.from(
            { length: quantidade },
            function (_, indice) {
                return (
                    valorBase +
                    (indice < restante ? 1 : 0)
                );
            }
        );
    };

    const calcularParcelas = function () {
        const quantidade = obterQuantidade();
        const primeiraData = obterData(
            primeiraDataInput.value
        );
        const intervalo = obterIntervalo();

        if (!primeiraData || intervalo === null) {
            return [];
        }

        const valores = calcularValores(quantidade);

        return valores.map(function (valorCentavos, indice) {
            const dataVencimento = adicionarDias(
                primeiraData,
                intervalo * indice
            );

            return {
                numero: indice + 1,
                valor: valorCentavos / 100,
                valorCentavos,
                dataVencimento,
                dataVencimentoInput:
                    formatarDataInput(dataVencimento),
            };
        });
    };

    const renderizar = function () {
        const parcelas = calcularParcelas();

        if (parcelas.length === 0) {
            previewContainer.innerHTML = `
                <div class="text-muted">
                    Informe uma data de vencimento e um intervalo válidos.
                </div>
            `;

            if (totalElement) {
                totalElement.textContent = 'R$ 0,00';
            }

            return [];
        }

        previewContainer.innerHTML = parcelas
            .map(function (parcela) {
                return `
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>
                            Parcela ${parcela.numero}
                            <small class="text-muted">
                                — ${formatarData(parcela.dataVencimento)}
                            </small>
                        </span>

                        <strong>
                            R$ ${formatarMoeda(parcela.valor)}
                        </strong>
                    </div>
                `;
            })
            .join('');

        if (totalElement) {
            const totalParcelasEmCentavos = parcelas.reduce(
                function (total, parcela) {
                    return total + parcela.valorCentavos;
                },
                0
            );

            totalElement.textContent =
                'R$ ' + formatarMoeda(totalParcelasEmCentavos / 100);
        }

        return parcelas;
    };

    const atualizar = function () {
        return renderizar();
    };

    quantidadeInput.setAttribute(
        'max',
        LIMITE_MAXIMO_PARCELAS
    );

    quantidadeInput.addEventListener('input', atualizar);
    quantidadeInput.addEventListener('change', atualizar);
    primeiraDataInput.addEventListener('change', atualizar);
    intervaloInput.addEventListener('change', atualizar);

    renderizar();

    return {
        atualizar,
        calcularParcelas,
        obterParcelas: calcularParcelas,
    };
}
