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

    /*
     * =========================================================
     * MODO DA FINALIZAÇÃO
     * =========================================================
     *
     * Se esses campos existem, a Nota ainda NÃO possui
     * Conta a Receber e precisamos criar as parcelas.
     *
     * Se não existem, significa que a Nota JÁ possui
     * Conta a Receber e o financeiro já foi configurado.
     */
    const precisaCriarParcelas = Boolean(
        parcelasHidden
        && quantidadeInput
        && primeiraDataInput
        && intervaloInput
        && previewElement
        && totalElement
    );

    let configuracaoParcelas = null;

    /*
     * =========================================================
     * INICIALIZAR PARCELAMENTO
     * =========================================================
     *
     * Só inicializamos o componente quando realmente
     * precisamos criar uma nova Conta a Receber.
     */
    if (precisaCriarParcelas) {
        configuracaoParcelas =
            inicializarParcelas({
                quantidadeInput,
                primeiraDataInput,
                intervaloInput,
                previewElement,
                totalElement,
                valorTotal,
            });
    }

    /*
     * =========================================================
     * ESTADO DO BOTÃO
     * =========================================================
     */
    function definirBotaoProcessando() {
        if (!botaoFinalizar) {
            return;
        }

        botaoFinalizar.disabled = true;

        botaoFinalizar.innerHTML =
            '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Finalizando...';
    }

    function restaurarBotaoFinalizar() {
        if (!botaoFinalizar) {
            return;
        }

        botaoFinalizar.disabled = false;

        botaoFinalizar.innerHTML =
            '<i class="bi bi-check-circle"></i> Finalizar nota';
    }

    /*
     * =========================================================
     * INPUTS HIDDEN DAS PARCELAS
     * =========================================================
     */
    function limparParcelasHidden() {
        if (!parcelasHidden) {
            return;
        }

        parcelasHidden.innerHTML = '';
    }

    function preencherParcelasHidden(parcelas) {
        limparParcelasHidden();

        if (
            !parcelasHidden
            || !Array.isArray(parcelas)
        ) {
            return;
        }

        parcelas.forEach(function (parcela, indice) {
            const numero =
                document.createElement('input');

            numero.type = 'hidden';

            numero.name =
                `parcelas[${indice}][numero]`;

            numero.value =
                parcela.numero;

            const valor =
                document.createElement('input');

            valor.type = 'hidden';

            valor.name =
                `parcelas[${indice}][valor]`;

            valor.value =
                Number(parcela.valor).toFixed(2);

            const dataVencimento =
                document.createElement('input');

            dataVencimento.type = 'hidden';

            dataVencimento.name =
                `parcelas[${indice}][data_vencimento]`;

            dataVencimento.value =
                parcela.data_vencimento;

            parcelasHidden.appendChild(
                numero
            );

            parcelasHidden.appendChild(
                valor
            );

            parcelasHidden.appendChild(
                dataVencimento
            );
        });
    }

    /*
     * =========================================================
     * VALIDAR PARCELAS NOVAS
     * =========================================================
     */
    function obterParcelasValidas() {
        if (
            !precisaCriarParcelas
            || !configuracaoParcelas
        ) {
            return [];
        }

        const parcelas =
            configuracaoParcelas.obterParcelas();

        if (
            !Array.isArray(parcelas)
            || !parcelas.length
        ) {
            window.alert(
                'Informe ao menos uma parcela para finalizar a Nota.'
            );

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

    /*
     * =========================================================
     * SUBMIT
     * =========================================================
     */
    formulario.addEventListener(
        'submit',
        function (evento) {
            /*
             * =================================================
             * CONTA A RECEBER JÁ EXISTENTE
             * =================================================
             *
             * Não precisamos gerar parcelas.
             *
             * O backend vai validar:
             * - Conta vinculada;
             * - valor da Conta;
             * - parcelas existentes;
             * - status financeiro;
             * - estoque.
             */
            if (!precisaCriarParcelas) {
                definirBotaoProcessando();

                return;
            }

            /*
             * =================================================
             * NOVA CONTA A RECEBER
             * =================================================
             */
            const parcelas =
                obterParcelasValidas();

            if (!parcelas) {
                evento.preventDefault();

                return;
            }

            preencherParcelasHidden(
                parcelas
            );

            definirBotaoProcessando();
        }
    );

    /*
     * =========================================================
     * RESET DO MODAL
     * =========================================================
     */
    modalElement.addEventListener(
        'hidden.bs.modal',
        function () {
            limparParcelasHidden();

            restaurarBotaoFinalizar();

            if (configuracaoParcelas) {
                configuracaoParcelas.reset();
            }
        }
    );

    /*
     * =========================================================
     * INICIALIZAÇÃO DO PARCELAMENTO
     * =========================================================
     */
    if (configuracaoParcelas) {
        configuracaoParcelas.atualizar();
    }

    /*
     * =========================================================
     * REABRIR MODAL APÓS VALIDAÇÃO
     * =========================================================
     */
    if (
        modalElement.dataset.reabrir === '1'
        && window.bootstrap
    ) {
        window.bootstrap.Modal
            .getOrCreateInstance(
                modalElement
            )
            .show();
    }

    console.log(
        '[SOS Mecânica] Finalização de Nota inicializada.',
        {
            precisaCriarParcelas,
        }
    );
}