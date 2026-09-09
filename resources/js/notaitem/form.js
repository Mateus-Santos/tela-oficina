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
        // CONTROLE DA BUSCA DA PLACA
        // ============================================================

        let buscaPlacaController = null;
        let ultimaPlacaBuscada = '';

        // ============================================================
        // UTILITÁRIOS
        // ============================================================

        function normalizarPlaca(valor) {
            return String(valor || '')
                .toUpperCase()
                .replace(/[^A-Z0-9]/g, '');
        }

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
        // COLOCA O VEÍCULO ENCONTRADO NO SELECT
        // ============================================================

        function selecionarVeiculo(data) {
            if (
                !idVeiculo ||
                !data ||
                !data.veiculo_id
            ) {
                return;
            }

            const veiculoId = String(data.veiculo_id);

            // --------------------------------------------------------
            // Procura uma option já existente
            // --------------------------------------------------------

            let option = Array.from(idVeiculo.options).find(
                (item) => item.value === veiculoId
            );

            // --------------------------------------------------------
            // Se não existir, cria a option
            // --------------------------------------------------------

            if (!option) {
                option = document.createElement('option');

                option.value = veiculoId;

                option.textContent =
                    data.placa || `Veículo #${veiculoId}`;

                idVeiculo.appendChild(option);
            }

            // --------------------------------------------------------
            // Seleciona o veículo
            // --------------------------------------------------------

            idVeiculo.value = veiculoId;

            // --------------------------------------------------------
            // Confirma que o navegador realmente selecionou
            // --------------------------------------------------------

            if (idVeiculo.value !== veiculoId) {
                console.error(
                    '[SOS Mecânica] Não foi possível selecionar o veículo.',
                    {
                        veiculoId: veiculoId,
                        valorAtual: idVeiculo.value
                    }
                );

                return;
            }

            console.log(
                '[SOS Mecânica] Veículo selecionado:',
                {
                    id: data.veiculo_id,
                    placa: data.placa
                }
            );
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

            // --------------------------------------------------------
            // PLACA VAZIA
            // --------------------------------------------------------

            if (!placa) {
                if (buscaPlacaController) {
                    buscaPlacaController.abort();
                    buscaPlacaController = null;
                }

                ultimaPlacaBuscada = '';

                limparDadosVeiculo();

                return;
            }

            // --------------------------------------------------------
            // NÃO BUSCA PLACA INCOMPLETA
            // --------------------------------------------------------

            if (placa.length < 7) {
                return;
            }

            // --------------------------------------------------------
            // EVITA BUSCAR A MESMA PLACA NOVAMENTE
            // --------------------------------------------------------

            if (placa === ultimaPlacaBuscada) {
                return;
            }

            ultimaPlacaBuscada = placa;

            // --------------------------------------------------------
            // CANCELA BUSCA ANTERIOR
            // --------------------------------------------------------

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

                // ----------------------------------------------------
                // VEÍCULO NÃO ENCONTRADO / ERRO HTTP
                // ----------------------------------------------------

                if (!response.ok) {
                    console.warn(
                        '[SOS Mecânica] Veículo não encontrado para a placa:',
                        placa
                    );

                    limparDadosVeiculo();

                    return;
                }

                const data = await response.json();

                // ----------------------------------------------------
                // VALIDA RESPOSTA
                // ----------------------------------------------------

                if (
                    !data ||
                    !data.veiculo_id
                ) {
                    console.warn(
                        '[SOS Mecânica] API retornou dados inválidos:',
                        data
                    );

                    limparDadosVeiculo();

                    return;
                }

                // ----------------------------------------------------
                // CLIENTE
                // ----------------------------------------------------

                if (clienteNome) {
                    clienteNome.value =
                        data.cliente_nome || '';
                }

                if (clienteId) {
                    clienteId.value =
                        data.cliente_id || '';
                }

                // ----------------------------------------------------
                // VEÍCULO
                // ----------------------------------------------------

                selecionarVeiculo(data);

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
        // BUSCA DA PLACA
        // ============================================================

        if (placaInput) {
            placaInput.addEventListener(
                'blur',
                buscarDadosPlaca
            );

            placaInput.addEventListener(
                'keydown',
                function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();

                        buscarDadosPlaca();
                    }
                }
            );

            placaInput.addEventListener(
                'input',
                function () {
                    const placa = normalizarPlaca(
                        placaInput.value
                    );

                    placaInput.value = placa;

                    // Se o usuário alterou a placa,
                    // permite uma nova consulta.

                    if (placa !== ultimaPlacaBuscada) {
                        ultimaPlacaBuscada = '';
                    }

                    // Quando a placa ficou vazia,
                    // limpa os dados vinculados.

                    if (!placa) {
                        limparDadosVeiculo();
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
