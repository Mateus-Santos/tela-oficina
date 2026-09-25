import inicializarParcelas from '../compras/aprovacao/parcelas.js';

export default function inicializarFinalizacaoNota() {
    const modalElement = document.getElementById(
        'modalFinalizarNota'
    );

    if (!modalElement) {
        return;
    }

    const formulario = document.getElementById(
        'formFinalizarNota'
    );

    if (!formulario) {
        return;
    }

    const parcelasHidden = document.getElementById(
        'finalizacao-nota-parcelas-hidden'
    );

    const quantidadeInput = document.getElementById(
        'finalizacao_nota_parcelas_quantidade'
    );

    const primeiraDataInput = document.getElementById(
        'finalizacao_nota_primeira_data_vencimento'
    );

    const intervaloInput = document.getElementById(
        'finalizacao_nota_intervalo_parcelas'
    );

    const previewElement = document.getElementById(
        'finalizacao-nota-preview-parcelas'
    );

    const totalElement = document.getElementById(
        'finalizacao-nota-total'
    );

    const botaoFinalizar = document.getElementById(
        'botaoFinalizarNota'
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
            const numero =
                document.createElement('input');

            numero.type = 'hidden';
            numero.name =
                `parcelas[${indice}][numero]`;
            numero.value = parcela.numero;

            const valor =
                document.createElement('input');

            valor.type = 'hidden';
            valor.name =
                `parcelas[${indice}][valor]`;
            valor.value =
                parcela.valor.toFixed(2);

            const dataVencimento =
                document.createElement('input');

            dataVencimento.type = 'hidden';
            dataVencimento.name =
                `parcelas[${indice}][data_vencimento]`;
            dataVencimento.value =
                parcela.data_vencimento;

            parcelasHidden.appendChild(numero);
            parcelasHidden.appendChild(valor);
            parcelasHidden.appendChild(
                dataVencimento
            );
        });
    }

    function obterParcelasValidas() {
        const parcelas =
            configuracaoParcelas.obterParcelas();

        if (!parcelas.length) {
            return null;
        }

        const possuiDataIncompleta =
            parcelas.some(function (parcela) {
                return !parcela.data_vencimento;
            });

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
                parcelas[indice - 1]
                    .data_vencimento;

            const dataAtual =
                parcelas[indice]
                    .data_vencimento;

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

            if (botaoFinalizar) {
                botaoFinalizar.disabled = true;

                botaoFinalizar.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Finalizando...';
            }
        }
    );

    modalElement.addEventListener(
        'hidden.bs.modal',
        function () {
            limparParcelasHidden();

            if (botaoFinalizar) {
                botaoFinalizar.disabled = false;

                botaoFinalizar.innerHTML =
                    '<i class="bi bi-check-circle"></i> Finalizar nota';
            }

            configuracaoParcelas.reset();
        }
    );

    configuracaoParcelas.atualizar();

    if (
        modalElement.dataset.reabrir === '1'
        && window.bootstrap
    ) {
        window.bootstrap.Modal
            .getOrCreateInstance(modalElement)
            .show();
    }
}
