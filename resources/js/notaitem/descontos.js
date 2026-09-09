(function () {
    'use strict';

    function iniciarDescontos() {
        // ============================================================
        // ELEMENTOS PRINCIPAIS
        // ============================================================

        const tabelaItens = document.getElementById(
            'container-itens-dinamicos'
        );

        const modalDescontos = document.getElementById(
            'modalDescontos'
        );

        const botaoAplicar = document.getElementById(
            'btn-aplicar-descontos-modal'
        );

        if (!tabelaItens) {
            console.warn(
                '[SOS Mecânica] Tabela de itens não encontrada para descontos.'
            );

            return;
        }

        // ============================================================
        // ELEMENTOS DO MODAL
        // ============================================================

        const pecasValorInput =
            document.getElementById(
                'modal-desc-pecas-valor'
            );

        const pecasPorcentInput =
            document.getElementById(
                'modal-desc-pecas-porcent'
            );

        const servicosValorInput =
            document.getElementById(
                'modal-desc-servicos-valor'
            );

        const servicosPorcentInput =
            document.getElementById(
                'modal-desc-servicos-porcent'
            );

        const resumoPecas =
            document.getElementById(
                'modal-resumo-desc-pecas'
            );

        const resumoServicos =
            document.getElementById(
                'modal-resumo-desc-servicos'
            );

        const resumoTotal =
            document.getElementById(
                'modal-resumo-desc-total'
            );

        // ============================================================
        // AUXILIARES
        // ============================================================

        function converterNumero(valor) {
            if (
                valor === null ||
                typeof valor === 'undefined'
            ) {
                return 0;
            }

            let texto = String(valor).trim();

            if (texto === '') {
                return 0;
            }

            if (texto.indexOf(',') !== -1) {
                texto = texto
                    .replace(/\./g, '')
                    .replace(',', '.');
            } else if (
                (texto.match(/\./g) || []).length > 1
            ) {
                texto = texto.replace(/\./g, '');
            }

            const numero =
                parseFloat(texto);

            if (!Number.isFinite(numero)) {
                return 0;
            }

            return numero;
        }

        function formatarMoeda(valor) {
            const numero = Number(valor);

            if (!Number.isFinite(numero)) {
                return 'R$ 0,00';
            }

            return numero.toLocaleString(
                'pt-BR',
                {
                    style: 'currency',
                    currency: 'BRL'
                }
            );
        }

        function formatarNumero(valor) {
            const numero = Number(valor);

            if (!Number.isFinite(numero)) {
                return '0,00';
            }

            return numero
                .toFixed(2)
                .replace('.', ',');
        }

        function limitarPercentual(valor) {
            const numero =
                converterNumero(valor);

            return Math.min(
                100,
                Math.max(0, numero)
            );
        }

        // ============================================================
        // ACESSO AO MÓDULO DE ITENS
        // ============================================================

        function obterModuloItens() {
            if (
                !window.NotaItens ||
                typeof window.NotaItens.obterDadosFinanceiros !==
                    'function'
            ) {
                console.warn(
                    '[SOS Mecânica] NotaItens ainda não está disponível.'
                );

                return null;
            }

            return window.NotaItens;
        }

        function obterDadosFinanceiros() {
            const moduloItens =
                obterModuloItens();

            if (!moduloItens) {
                return {
                    produto: {
                        bruto: 0,
                        desconto: 0,
                        liquido: 0
                    },

                    os: {
                        bruto: 0,
                        desconto: 0,
                        liquido: 0
                    },

                    subtotal: 0,
                    desconto: 0,
                    total: 0
                };
            }

            return moduloItens.obterDadosFinanceiros();
        }

        function obterSubtotalCategoria(tipo) {
            const dados =
                obterDadosFinanceiros();

            if (tipo === 'produto') {
                return dados.produto.bruto;
            }

            if (tipo === 'os') {
                return dados.os.bruto;
            }

            return 0;
        }

        // ============================================================
        // LINHAS
        // ============================================================

        function obterLinhas() {
            return tabelaItens.querySelectorAll(
                'tr[data-item-index]'
            );
        }

        function obterTipoLinha(linha) {
            const tipoInput =
                linha.querySelector(
                    'input[name*="[itemable_type]"], input[name*="[tipo]"]'
                );

            if (!tipoInput) {
                return '';
            }

            const tipo =
                String(
                    tipoInput.value || ''
                );

            if (
                tipo === 'App\\Models\\Produto' ||
                tipo === 'Produto' ||
                tipo === 'produto'
            ) {
                return 'produto';
            }

            if (
                tipo === 'App\\Models\\OrdemServico' ||
                tipo === 'OrdemServico' ||
                tipo === 'os'
            ) {
                return 'os';
            }

            return '';
        }

        function obterDadosLinha(linha) {
            const quantidadeInput =
                linha.querySelector(
                    '.input-qtd, .item-quantidade'
                );

            const valorInput =
                linha.querySelector(
                    '.input-valor, .item-valor-unitario'
                );

            const descontoInput =
                linha.querySelector(
                    '.input-desconto, .item-desconto'
                );

            if (
                !quantidadeInput ||
                !valorInput ||
                !descontoInput
            ) {
                return null;
            }

            const quantidade =
                converterNumero(
                    quantidadeInput.value
                );

            const valorUnitario =
                converterNumero(
                    valorInput.value
                );

            const desconto =
                Math.max(
                    0,
                    converterNumero(
                        descontoInput.value
                    )
                );

            const subtotalBruto =
                quantidade *
                valorUnitario;

            return {
                tipo: obterTipoLinha(linha),
                quantidade,
                valorUnitario,
                desconto,
                subtotalBruto
            };
        }

        // ============================================================
        // MÁSCARA DE PERCENTUAL
        // ============================================================

        function aplicarMascaraPercentual(input) {
            if (!input) {
                return;
            }

            input.addEventListener(
                'input',
                function () {
                    let valor =
                        input.value;

                    // ------------------------------------------------
                    // Mantém somente números e vírgula.
                    // ------------------------------------------------

                    valor = valor.replace(
                        /[^\d,]/g,
                        ''
                    );

                    // ------------------------------------------------
                    // Mantém somente a primeira vírgula.
                    // ------------------------------------------------

                    const primeiraVirgula =
                        valor.indexOf(',');

                    if (
                        primeiraVirgula !== -1
                    ) {
                        valor =
                            valor.substring(
                                0,
                                primeiraVirgula + 1
                            ) +
                            valor
                                .substring(
                                    primeiraVirgula + 1
                                )
                                .replace(
                                    /,/g,
                                    ''
                                );
                    }

                    // ------------------------------------------------
                    // Limita a duas casas decimais.
                    // ------------------------------------------------

                    if (
                        valor.indexOf(',') !== -1
                    ) {
                        const partes =
                            valor.split(',');

                        const inteiro =
                            partes[0] || '0';

                        const decimal =
                            (
                                partes[1] ||
                                ''
                            ).substring(
                                0,
                                2
                            );

                        valor =
                            `${inteiro},${decimal}`;
                    }

                    // ------------------------------------------------
                    // Remove zeros desnecessários.
                    // ------------------------------------------------

                    if (
                        valor.indexOf(',') !== -1
                    ) {
                        const partes =
                            valor.split(',');

                        let inteiro =
                            partes[0].replace(
                                /^0+(?=\d)/,
                                ''
                            );

                        if (inteiro === '') {
                            inteiro = '0';
                        }

                        valor =
                            `${inteiro},${partes[1]}`;
                    } else {
                        valor =
                            valor.replace(
                                /^0+(?=\d)/,
                                ''
                            );

                        if (valor === '') {
                            valor = '0';
                        }
                    }

                    // ------------------------------------------------
                    // Limita a 100%.
                    //
                    // IMPORTANTE:
                    // não interromper aqui.
                    // O valor corrigido precisa sincronizar
                    // o campo em R$.
                    // ------------------------------------------------

                    const numero =
                        converterNumero(valor);

                    if (numero > 100) {
                        valor = '100';
                    }

                    input.value = valor;

                    const tipo =
                        input === pecasPorcentInput
                            ? 'produto'
                            : 'os';

                    const subtotal =
                        obterSubtotalCategoria(
                            tipo
                        );

                    const campoValor =
                        input === pecasPorcentInput
                            ? pecasValorInput
                            : servicosValorInput;

                    sincronizarPercentualParaValor(
                        input,
                        campoValor,
                        subtotal
                    );

                    atualizarResumoModal();
                }
            );

            input.addEventListener(
                'blur',
                function () {
                    if (
                        input.value.trim() === ''
                    ) {
                        input.value = '0,00';
                    } else {
                        input.value =
                            formatarNumero(
                                limitarPercentual(
                                    input.value
                                )
                            );
                    }

                    const tipo =
                        input === pecasPorcentInput
                            ? 'produto'
                            : 'os';

                    const subtotal =
                        obterSubtotalCategoria(
                            tipo
                        );

                    const campoValor =
                        input === pecasPorcentInput
                            ? pecasValorInput
                            : servicosValorInput;

                    sincronizarPercentualParaValor(
                        input,
                        campoValor,
                        subtotal
                    );

                    atualizarResumoModal();
                }
            );
        }

        // ============================================================
        // R$ -> %
        // ============================================================

        function sincronizarValorParaPercentual(
            valorInput,
            porcentInput,
            subtotal
        ) {
            if (
                !valorInput ||
                !porcentInput
            ) {
                return;
            }

            const valor =
                Math.max(
                    0,
                    converterNumero(
                        valorInput.value
                    )
                );

            if (subtotal <= 0) {
                porcentInput.value =
                    '0,00';

                return;
            }

            const percentual =
                (valor / subtotal) *
                100;

            porcentInput.value =
                formatarNumero(
                    limitarPercentual(
                        percentual
                    )
                );
        }

        // ============================================================
        // % -> R$
        // ============================================================

        function sincronizarPercentualParaValor(
            porcentInput,
            valorInput,
            subtotal
        ) {
            if (
                !porcentInput ||
                !valorInput
            ) {
                return;
            }

            const percentual =
                limitarPercentual(
                    porcentInput.value
                );

            if (subtotal <= 0) {
                valorInput.value =
                    '0,00';

                return;
            }

            const valor =
                (
                    subtotal *
                    percentual
                ) / 100;

            valorInput.value =
                formatarNumero(valor);
        }

        // ============================================================
        // RESUMO DO MODAL
        // ============================================================

        function atualizarResumoModal() {
            const dados =
                obterDadosFinanceiros();

            const descontoPecas =
                Math.max(
                    0,
                    converterNumero(
                        pecasValorInput
                            ? pecasValorInput.value
                            : 0
                    )
                );

            const descontoServicos =
                Math.max(
                    0,
                    converterNumero(
                        servicosValorInput
                            ? servicosValorInput.value
                            : 0
                    )
                );

            const descontoPecasLimitado =
                Math.min(
                    descontoPecas,
                    dados.produto.bruto
                );

            const descontoServicosLimitado =
                Math.min(
                    descontoServicos,
                    dados.os.bruto
                );

            if (resumoPecas) {
                resumoPecas.textContent =
                    formatarMoeda(
                        descontoPecasLimitado
                    );
            }

            if (resumoServicos) {
                resumoServicos.textContent =
                    formatarMoeda(
                        descontoServicosLimitado
                    );
            }

            if (resumoTotal) {
                resumoTotal.textContent =
                    formatarMoeda(
                        descontoPecasLimitado +
                        descontoServicosLimitado
                    );
            }
        }

        // ============================================================
        // PREPARAR MODAL
        // ============================================================

        function prepararModal() {
            const dados =
                obterDadosFinanceiros();

            const pecasPercentual =
                dados.produto.bruto > 0
                    ? (
                        dados.produto.desconto /
                        dados.produto.bruto
                    ) * 100
                    : 0;

            const servicosPercentual =
                dados.os.bruto > 0
                    ? (
                        dados.os.desconto /
                        dados.os.bruto
                    ) * 100
                    : 0;

            if (pecasValorInput) {
                pecasValorInput.value =
                    formatarNumero(
                        dados.produto.desconto
                    );
            }

            if (pecasPorcentInput) {
                pecasPorcentInput.value =
                    formatarNumero(
                        limitarPercentual(
                            pecasPercentual
                        )
                    );
            }

            if (servicosValorInput) {
                servicosValorInput.value =
                    formatarNumero(
                        dados.os.desconto
                    );
            }

            if (servicosPorcentInput) {
                servicosPorcentInput.value =
                    formatarNumero(
                        limitarPercentual(
                            servicosPercentual
                        )
                    );
            }

            atualizarResumoModal();
        }

        // ============================================================
        // APLICAR DESCONTO EM UMA CATEGORIA
        //
        // REGRA ANTIGA:
        //
        // desconto = subtotal BRUTO do item * percentual / 100
        //
        // O desconto existente é substituído pelo resultado.
        // ============================================================

        function aplicarDescontoCategoria(
            tipo,
            percentual
        ) {
            const percentualAplicado =
                limitarPercentual(
                    percentual
                );

            obterLinhas().forEach(
                function (linha) {
                    if (
                        obterTipoLinha(linha) !==
                        tipo
                    ) {
                        return;
                    }

                    const dados =
                        obterDadosLinha(linha);

                    if (!dados) {
                        return;
                    }

                    const descontoProporcional =
                        (
                            dados.subtotalBruto *
                            percentualAplicado
                        ) / 100;

                    const descontoFinal =
                        Math.min(
                            dados.subtotalBruto,
                            descontoProporcional
                        );

                    const descontoInput =
                        linha.querySelector(
                            '.input-desconto, .item-desconto'
                        );

                    if (descontoInput) {
                        descontoInput.value =
                            formatarNumero(
                                descontoFinal
                            );
                    }
                }
            );
        }

        // ============================================================
        // APLICAR DESCONTOS
        // ============================================================

        function aplicarDescontos() {
            const dados =
                obterDadosFinanceiros();

            const percentualPecas =
                pecasPorcentInput
                    ? limitarPercentual(
                        pecasPorcentInput.value
                    )
                    : 0;

            const percentualServicos =
                servicosPorcentInput
                    ? limitarPercentual(
                        servicosPorcentInput.value
                    )
                    : 0;

            // --------------------------------------------------------
            // PEÇAS
            // --------------------------------------------------------

            if (dados.produto.bruto > 0) {
                aplicarDescontoCategoria(
                    'produto',
                    percentualPecas
                );
            }

            // --------------------------------------------------------
            // SERVIÇOS
            // --------------------------------------------------------

            if (dados.os.bruto > 0) {
                aplicarDescontoCategoria(
                    'os',
                    percentualServicos
                );
            }

            // --------------------------------------------------------
            // O cálculo financeiro pertence ao itens.js.
            // --------------------------------------------------------

            if (
                window.NotaItens &&
                typeof window.NotaItens.recalcular ===
                    'function'
            ) {
                window.NotaItens.recalcular();
            }

            // --------------------------------------------------------
            // Atualiza o modal com os valores realmente aplicados.
            // --------------------------------------------------------

            prepararModal();

            // --------------------------------------------------------
            // Fecha o modal.
            // --------------------------------------------------------

            if (
                modalDescontos &&
                typeof bootstrap !== 'undefined'
            ) {
                const modal =
                    bootstrap.Modal.getInstance(
                        modalDescontos
                    );

                if (modal) {
                    modal.hide();
                }
            }
        }

        // ============================================================
        // EVENTOS DO MODAL
        // ============================================================

        if (modalDescontos) {
            modalDescontos.addEventListener(
                'show.bs.modal',
                prepararModal
            );
        }

        if (botaoAplicar) {
            botaoAplicar.addEventListener(
                'click',
                aplicarDescontos
            );
        }

        // ============================================================
        // MÁSCARAS DOS PERCENTUAIS
        // ============================================================

        aplicarMascaraPercentual(
            pecasPorcentInput
        );

        aplicarMascaraPercentual(
            servicosPorcentInput
        );

        // ============================================================
        // PEÇAS — R$ -> %
        // ============================================================

        if (pecasValorInput) {
            pecasValorInput.addEventListener(
                'input',
                function () {
                    const subtotal =
                        obterSubtotalCategoria(
                            'produto'
                        );

                    sincronizarValorParaPercentual(
                        pecasValorInput,
                        pecasPorcentInput,
                        subtotal
                    );

                    atualizarResumoModal();
                }
            );
        }

        // ============================================================
        // SERVIÇOS — R$ -> %
        // ============================================================

        if (servicosValorInput) {
            servicosValorInput.addEventListener(
                'input',
                function () {
                    const subtotal =
                        obterSubtotalCategoria(
                            'os'
                        );

                    sincronizarValorParaPercentual(
                        servicosValorInput,
                        servicosPorcentInput,
                        subtotal
                    );

                    atualizarResumoModal();
                }
            );
        }

        // ============================================================
        // INICIALIZAÇÃO
        // ============================================================

        atualizarResumoModal();

        console.log(
            '[SOS Mecânica] descontos.js inicializado com sucesso.'
        );
    }

    // ================================================================
    // INICIALIZAÇÃO ROBUSTA
    // ================================================================

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            iniciarDescontos,
            {
                once: true
            }
        );
    } else {
        iniciarDescontos();
    }
})();
