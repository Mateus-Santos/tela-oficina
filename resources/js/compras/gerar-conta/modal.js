import inicializarParcelas from '../aprovacao/parcelas';

export default function inicializarGeracaoContaCompra() {
    const modalElement = document.getElementById(
        'modalGerarContaCompra'
    );

    if (!modalElement) {
        return;
    }

    const formulario = document.getElementById(
        'formGerarContaCompra'
    );

    if (!formulario) {
        return;
    }

    const parcelasHidden = document.getElementById(
        'gerar-conta-parcelas-hidden'
    );

    const quantidadeInput = document.getElementById(
        'gerar_conta_parcelas_quantidade'
    );

    const primeiraDataInput = document.getElementById(
        'gerar_conta_primeira_data_vencimento'
    );

    const intervaloInput = document.getElementById(
        'gerar_conta_intervalo_parcelas'
    );

    const previewElement = document.getElementById(
        'gerar-conta-preview-parcelas'
    );

    const totalElement = document.getElementById(
        'gerar-conta-total'
    );

    const botaoGerar = document.getElementById(
        'botaoGerarContaCompra'
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

    formulario.addEventListener(
        'submit',
        function (evento) {
            const parcelas =
                obterParcelasValidas();

            if (!parcelas) {
                evento.preventDefault();
                return;
            }

            preencherParcelasHidden(parcelas);

            if (botaoGerar) {
                botaoGerar.disabled = true;

                botaoGerar.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Gerando...';
            }
        }
    );

    modalElement.addEventListener(
        'hidden.bs.modal',
        function () {
            limparParcelasHidden();

            if (botaoGerar) {
                botaoGerar.disabled = false;

                botaoGerar.innerHTML =
                    '<i class="bi bi-check-circle"></i> Gerar conta a pagar';
            }

            configuracaoParcelas.reset();
        }
    );

    configuracaoParcelas.atualizar();
}
