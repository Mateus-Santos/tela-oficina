(function () {
    'use strict';

    function iniciarForm() {
        // ============================================================
        // DADOS GERAIS DA NOTA
        // ============================================================

        const placaInput = document.getElementById('placa_input');
        const clienteNome = document.getElementById('cliente_nome');
        const clienteId = document.getElementById('cliente_id');
        const idVeiculo = document.getElementById('veiculo_cliente_id');

        // ============================================================
        // CAMPOS DE KM / TROCA DE ÓLEO
        // ============================================================

        const kmAtual = document.getElementById('km');
        const diferenca = document.getElementById(
            'km_diferenca_troca_oleo'
        );
        const kmProximaTroca = document.getElementById(
            'km_proxima_troca_oleo'
        );
        const mensagem = document.getElementById(
            'km_diferenca_troca_oleo_mensagem'
        );

        // ============================================================
        // BUSCA DE PLACA
        // ============================================================

        let buscaPlacaController = null;

        // ============================================================
        // UTILITÁRIOS
        // ============================================================

        function limparDadosVeiculo() {
            if (clienteNome) {
                clienteNome.value = '';
            }

            if (clienteId) {
                clienteId.value = '';
            }

            if (idVeiculo) {
                idVeiculo.value = '';
            }
        }

        function normalizarPlaca(valor) {
            return String(valor || '')
                .toUpperCase()
                .replace(/[-\s]/g, '');
        }

        // ============================================================
        // CÁLCULO DA PRÓXIMA TROCA DE ÓLEO
        //
        // KM ATUAL + DISTÂNCIA = KM DA PRÓXIMA TROCA
        // ============================================================

        function atualizarTrocaOleo() {
            if (
                !kmAtual ||
                !diferenca ||
                !kmProximaTroca ||
                !mensagem
            ) {
                return;
            }

            const atual = parseInt(kmAtual.value, 10);
            const distancia = parseInt(diferenca.value, 10);

            // --------------------------------------------------------
            // KM ATUAL NÃO INFORMADO
            // --------------------------------------------------------

            if (Number.isNaN(atual)) {
                kmProximaTroca.value = '';

                mensagem.textContent =
                    'Informe o KM atual e a distância para a próxima troca.';

                mensagem.className = 'text-muted';

                return;
            }

            // --------------------------------------------------------
            // DISTÂNCIA NÃO INFORMADA
            // --------------------------------------------------------

            if (Number.isNaN(distancia)) {
                kmProximaTroca.value = '';

                mensagem.textContent =
                    'Informe quantos quilômetros até a próxima troca.';

                mensagem.className = 'text-muted';

                return;
            }

            // --------------------------------------------------------
            // CALCULA A PRÓXIMA TROCA
            // --------------------------------------------------------

            const proxima = atual + distancia;

            kmProximaTroca.value = proxima;

            // --------------------------------------------------------
            // MENSAGEM
            // --------------------------------------------------------

            if (distancia > 0) {
                mensagem.textContent =
                    'Próxima troca calculada para o KM ' +
                    proxima.toLocaleString('pt-BR') +
                    '.';

                mensagem.className = 'text-success';
            } else {
                mensagem.textContent =
                    'A troca de óleo está prevista para o KM atual.';

                mensagem.className = 'text-warning';
            }
        }

        // ============================================================
        // BUSCA OS DADOS DO VEÍCULO PELA PLACA
        // ============================================================

        async function buscarDadosPlaca() {
            if (!placaInput) {
                return;
            }

            const placa = normalizarPlaca(placaInput.value);

            placaInput.value = placa;

            if (!placa) {
                if (buscaPlacaController) {
                    buscaPlacaController.abort();
                    buscaPlacaController = null;
                }

                limparDadosVeiculo();

                return;
            }

            // Cancela uma busca anterior ainda em andamento.
            if (buscaPlacaController) {
                buscaPlacaController.abort();
            }

            buscaPlacaController = new AbortController();

            try {
                const response = await fetch(
                    '/api/veiculo/placa/' +
                    encodeURIComponent(placa),
                    {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json'
                        },
                        signal: buscaPlacaController.signal
                    }
                );

                if (!response.ok) {
                    limparDadosVeiculo();
                    return;
                }

                const data = await response.json();

                // ----------------------------------------------------
                // CLIENTE
                // ----------------------------------------------------

                if (clienteNome) {
                    clienteNome.value = data.cliente_nome || '';
                }

                if (clienteId) {
                    clienteId.value = data.cliente_id || '';
                }

                // ----------------------------------------------------
                // VEÍCULO
                // ----------------------------------------------------

                if (idVeiculo) {
                    idVeiculo.value = data.veiculo_id || '';
                }
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }

                console.error(
                    '[SOS Mecânica] Erro ao buscar placa:',
                    error
                );

                limparDadosVeiculo();
            } finally {
                buscaPlacaController = null;
            }
        }

        // ============================================================
        // EVENTOS DOS CAMPOS DE KM
        // ============================================================

        if (kmAtual) {
            kmAtual.addEventListener(
                'input',
                atualizarTrocaOleo
            );

            kmAtual.addEventListener(
                'change',
                atualizarTrocaOleo
            );
        }

        if (diferenca) {
            diferenca.addEventListener(
                'input',
                atualizarTrocaOleo
            );

            diferenca.addEventListener(
                'change',
                atualizarTrocaOleo
            );
        }

        // ============================================================
        // BUSCAR PLACA AO SAIR DO CAMPO
        // ============================================================

        if (placaInput) {
            placaInput.addEventListener(
                'blur',
                buscarDadosPlaca
            );

            // --------------------------------------------------------
            // BUSCAR PLACA AO PRESSIONAR ENTER
            // --------------------------------------------------------

            placaInput.addEventListener(
                'keydown',
                function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        buscarDadosPlaca();
                    }
                }
            );
        }

        // ============================================================
        // INICIALIZAÇÃO
        // ============================================================

        atualizarTrocaOleo();

        console.log(
            '[SOS Mecânica] form.js inicializado com sucesso.'
        );
    }

    // ================================================================
    // INICIALIZAÇÃO ROBUSTA
    // ================================================================

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            iniciarForm,
            {
                once: true
            }
        );
    } else {
        iniciarForm();
    }
})();
