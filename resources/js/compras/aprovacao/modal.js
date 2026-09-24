import inicializarParcelas from './parcelas';

export default function inicializarAprovacaoCompra() {
    const modalElement = document.getElementById(
        'modalAprovarCompra'
    );

    if (!modalElement) {
        return;
    }

    const formulario = document.getElementById(
        'formAprovarCompra'
    );

    if (!formulario) {
        return;
    }

    const etapaInicial = modalElement.querySelector(
        '[data-aprovacao-etapa="inicial"]'
    );

    const etapaParcelas = modalElement.querySelector(
        '[data-aprovacao-etapa="parcelas"]'
    );

    const botaoNaoGerar = modalElement.querySelector(
        '[data-aprovacao="nao-gerar"]'
    );

    const botaoGerar = modalElement.querySelector(
        '[data-aprovacao="gerar"]'
    );

    const botaoVoltar = modalElement.querySelector(
        '[data-aprovacao="voltar"]'
    );

    const botaoConfirmar = modalElement.querySelector(
        '[data-aprovacao="confirmar-geracao"]'
    );

    const gerarContaInput = document.getElementById(
        'aprovacao-compra-gerar-conta'
    );

    const parcelasHidden = document.getElementById(
        'aprovacao-compra-parcelas-hidden'
    );

    const quantidadeInput = document.getElementById(
        'parcelas_quantidade'
    );

    const primeiraDataInput = document.getElementById(
        'primeira_data_vencimento'
    );

    const intervaloInput = document.getElementById(
        'intervalo_parcelas'
    );

    const previewElement = document.getElementById(
        'aprovacao-compra-preview-parcelas'
    );

    const totalElement = document.getElementById(
        'aprovacao-compra-total'
    );

    const valorTotal = Number(
        modalElement.dataset.valorTotal || 0
    );

    const configuracaoParcelas = inicializarParcelas({
        quantidadeInput,
        primeiraDataInput,
        intervaloInput,
        previewElement,
        totalElement,
        valorTotal,
    });

    function limparParcelasHidden() {
        if (parcelasHidden) {
            parcelasHidden.innerHTML = '';
        }
    }

    function preencherParcelasHidden(parcelas) {
        limparParcelasHidden();

        if (!parcelasHidden) {
            return;
        }

        parcelas.forEach(function (parcela, indice) {
            const numero = document.createElement('input');

            numero.type = 'hidden';
            numero.name = `parcelas[${indice}][numero]`;
            numero.value = parcela.numero;

            const valor = document.createElement('input');

            valor.type = 'hidden';
            valor.name = `parcelas[${indice}][valor]`;
            valor.value = parcela.valor.toFixed(2);

            const dataVencimento =
                document.createElement('input');

            dataVencimento.type = 'hidden';
            dataVencimento.name =
                `parcelas[${indice}][data_vencimento]`;
            dataVencimento.value =
                parcela.data_vencimento;

            parcelasHidden.appendChild(numero);
            parcelasHidden.appendChild(valor);
            parcelasHidden.appendChild(dataVencimento);
        });
    }

    function mostrarEtapaInicial() {
        if (etapaInicial) {
            etapaInicial.classList.remove('d-none');
        }

        if (etapaParcelas) {
            etapaParcelas.classList.add('d-none');
        }

        if (gerarContaInput) {
            gerarContaInput.value = '0';
        }

        limparParcelasHidden();

        if (botaoNaoGerar) {
            botaoNaoGerar.classList.remove('d-none');
        }

        if (botaoGerar) {
            botaoGerar.classList.remove('d-none');
        }

        if (botaoVoltar) {
            botaoVoltar.classList.add('d-none');
        }

        if (botaoConfirmar) {
            botaoConfirmar.classList.add('d-none');
            botaoConfirmar.disabled = false;
        }

        configuracaoParcelas.reset();
    }

    function mostrarEtapaParcelas() {
        if (etapaInicial) {
            etapaInicial.classList.add('d-none');
        }

        if (etapaParcelas) {
            etapaParcelas.classList.remove('d-none');
        }

        if (gerarContaInput) {
            gerarContaInput.value = '1';
        }

        if (botaoNaoGerar) {
            botaoNaoGerar.classList.add('d-none');
        }

        if (botaoGerar) {
            botaoGerar.classList.add('d-none');
        }

        if (botaoVoltar) {
            botaoVoltar.classList.remove('d-none');
        }

        if (botaoConfirmar) {
            botaoConfirmar.classList.remove('d-none');
            botaoConfirmar.disabled = false;
        }

        configuracaoParcelas.atualizar();
    }

    function obterParcelasValidas() {
        const parcelas =
            configuracaoParcelas.obterParcelas();

        if (!parcelas.length) {
            return null;
        }

        const possuiDataIncompleta = parcelas.some(
            function (parcela) {
                return !parcela.data_vencimento;
            }
        );

        if (possuiDataIncompleta) {
            window.alert(
                'Preencha a data de vencimento de todas as parcelas.'
            );

            return null;
        }

        for (
            let indice = 1;
            indice < parcelas.length;
            indice += 1
        ) {
            const dataAnterior =
                parcelas[indice - 1].data_vencimento;

            const dataAtual =
                parcelas[indice].data_vencimento;

            if (dataAtual < dataAnterior) {
                window.alert(
                    'As datas de vencimento devem estar em ordem cronológica.'
                );

                return null;
            }
        }

        return parcelas;
    }

    if (botaoGerar) {
        botaoGerar.addEventListener(
            'click',
            function () {
                mostrarEtapaParcelas();
            }
        );
    }

    if (botaoVoltar) {
        botaoVoltar.addEventListener(
            'click',
            function () {
                mostrarEtapaInicial();
            }
        );
    }

    if (botaoNaoGerar) {
        botaoNaoGerar.addEventListener(
            'click',
            function () {
                if (gerarContaInput) {
                    gerarContaInput.value = '0';
                }

                limparParcelasHidden();
            }
        );
    }

    formulario.addEventListener(
        'submit',
        function (evento) {
            const gerarConta =
                gerarContaInput &&
                gerarContaInput.value === '1';

            if (!gerarConta) {
                limparParcelasHidden();
                return;
            }

            const parcelas =
                obterParcelasValidas();

            if (!parcelas) {
                evento.preventDefault();
                return;
            }

            preencherParcelasHidden(parcelas);

            if (botaoConfirmar) {
                botaoConfirmar.disabled = true;
            }
        }
    );

    modalElement.addEventListener(
        'hidden.bs.modal',
        function () {
            mostrarEtapaInicial();
        }
    );

    mostrarEtapaInicial();
}
