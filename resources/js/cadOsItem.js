document.addEventListener('DOMContentLoaded', () => {
    // ============================================================
    // DADOS GERAIS
    // ============================================================

    window.ordensServicoVeiculo = [];

    const placaInput = document.getElementById('placa_input');
    const clienteNome = document.getElementById('cliente_nome');
    const clienteId = document.getElementById('cliente_id');
    const idVeiculo = document.getElementById('veiculo_cliente_id');

    // ============================================================
    // CAMPOS DE KM / TROCA DE ÓLEO
    // ============================================================

    const kmAtual = document.getElementById('km');
    const diferenca = document.getElementById('km_diferenca_troca_oleo');
    const kmProximaTroca = document.getElementById('km_proxima_troca_oleo');
    const mensagem = document.getElementById(
        'km_diferenca_troca_oleo_mensagem'
    );

    // ============================================================
    // CÁLCULO DA PRÓXIMA TROCA DE ÓLEO
    //
    // KM ATUAL + DISTÂNCIA = KM DA PRÓXIMA TROCA
    // ============================================================

    function atualizarTrocaOleo() {
        // Verifica se os elementos existem
        if (!kmAtual || !diferenca || !kmProximaTroca || !mensagem) {
            console.warn(
                '[SOS Mecânica] Campos da troca de óleo não encontrados.'
            );

            return;
        }

        // Converte os valores para número
        const atual = parseInt(kmAtual.value, 10);
        const distancia = parseInt(diferenca.value, 10);

        // ========================================================
        // KM ATUAL NÃO INFORMADO
        // ========================================================

        if (Number.isNaN(atual)) {
            kmProximaTroca.value = '';

            mensagem.textContent =
                'Informe o KM atual e a distância para a próxima troca.';

            mensagem.className = 'text-muted';

            return;
        }

        // ========================================================
        // DISTÂNCIA NÃO INFORMADA
        // ========================================================

        if (Number.isNaN(distancia)) {
            kmProximaTroca.value = '';

            mensagem.textContent =
                'Informe quantos quilômetros até a próxima troca.';

            mensagem.className = 'text-muted';

            return;
        }

        // ========================================================
        // CALCULA A PRÓXIMA TROCA
        // ========================================================

        const proxima = atual + distancia;

        kmProximaTroca.value = proxima;

        // ========================================================
        // MENSAGEM
        // ========================================================

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
    // EVENTOS DOS CAMPOS
    // ============================================================

    if (kmAtual) {
        kmAtual.addEventListener('input', atualizarTrocaOleo);
        kmAtual.addEventListener('change', atualizarTrocaOleo);
    }

    if (diferenca) {
        diferenca.addEventListener('input', atualizarTrocaOleo);
        diferenca.addEventListener('change', atualizarTrocaOleo);
    }

    // ============================================================
    // CALCULA AO CARREGAR A PÁGINA
    // ============================================================

    atualizarTrocaOleo();

    // ============================================================
    // BUSCA OS DADOS PELA PLACA
    // ============================================================

    if (!placaInput) {
        console.warn(
            '[SOS Mecânica] Elemento #placa_input não encontrado no DOM.'
        );

        return;
    }

    async function buscarDadosPlaca() {
        let placa = placaInput.value
            .toUpperCase()
            .replace(/[-\s]/g, '');

        if (!placa) {
            return;
        }

        try {
            const response = await fetch(
                `/api/veiculo/placa/${encodeURIComponent(placa)}`
            );

            if (!response.ok) {
                if (clienteNome) {
                    clienteNome.value = '';
                }

                if (clienteId) {
                    clienteId.value = '';
                }

                if (idVeiculo) {
                    idVeiculo.value = '';
                }

                window.ordensServicoVeiculo = [];

                return;
            }

            const data = await response.json();

            // ====================================================
            // CLIENTE
            // ====================================================

            if (clienteNome) {
                clienteNome.value = data.cliente_nome || '';
            }

            if (clienteId) {
                clienteId.value = data.cliente_id || '';
            }

            // ====================================================
            // VEÍCULO
            // ====================================================

            if (idVeiculo) {
                idVeiculo.value = data.veiculo_id || '';
            }

            // ====================================================
            // O.S DO VEÍCULO
            // ====================================================

            window.ordensServicoVeiculo = data.ordens_servico || [];

            console.log(
                '[SOS Mecânica] O.S encontradas:',
                window.ordensServicoVeiculo
            );

        } catch (error) {
            console.error(
                '[SOS Mecânica] Erro ao buscar placa:',
                error
            );

            window.ordensServicoVeiculo = [];

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
    }

    // ============================================================
    // BUSCAR PLACA AO SAIR DO CAMPO
    // ============================================================

    placaInput.addEventListener('blur', buscarDadosPlaca);

    // ============================================================
    // BUSCAR PLACA AO PRESSIONAR ENTER
    // ============================================================

    placaInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();

            buscarDadosPlaca();
        }
    });
});
