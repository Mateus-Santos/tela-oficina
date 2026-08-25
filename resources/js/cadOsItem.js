document.addEventListener('DOMContentLoaded', () => {

    // Guarda as O.S retornadas pela API para o gerenciador de itens
    window.ordensServicoVeiculo = [];

    const placaInput = document.getElementById('placa_input');
    const clienteNome = document.getElementById('cliente_nome');
    const clienteId = document.getElementById('cliente_id');
    const idVeiculo = document.getElementById('veiculo_cliente_id');

    if (!placaInput) {
        console.warn('[SOS Mecânica] Elemento #placa_input não encontrado no DOM.');
        return;
    }

    const buscarDadosPlaca = async () => {

        let placa = placaInput.value
            .toUpperCase()
            .replace(/[-\s]/g, '');

        if (!placa) return;

        try {

            const response = await fetch(`/api/veiculo/placa/${placa}`);

            if (!response.ok) {

                if (clienteNome) clienteNome.value = '';
                if (clienteId) clienteId.value = '';
                if (idVeiculo) idVeiculo.value = '';

                window.ordensServicoVeiculo = [];

                return;
            }

            const data = await response.json();

            // Preenche os dados do cliente e veículo
            if (clienteNome) {
                clienteNome.value = data.cliente_nome || '';
            }

            if (clienteId) {
                clienteId.value = data.cliente_id || '';
            }

            if (idVeiculo) {
                idVeiculo.value = data.veiculo_id || '';
            }

            // Guarda as O.S encontradas para o gerenciador de itens
            window.ordensServicoVeiculo = data.ordens_servico || [];

            console.log(
                '[SOS Mecânica] O.S encontradas:',
                window.ordensServicoVeiculo
            );

        } catch (error) {

            console.error('Erro ao buscar placa:', error);

            window.ordensServicoVeiculo = [];

            if (clienteNome) clienteNome.value = '';
            if (clienteId) clienteId.value = '';
            if (idVeiculo) idVeiculo.value = '';
        }
    };

    // Busca ao sair do campo
    placaInput.addEventListener('blur', buscarDadosPlaca);

    // Busca ao pressionar Enter
    placaInput.addEventListener('keypress', (e) => {

        if (e.key === 'Enter') {

            e.preventDefault();

            buscarDadosPlaca();
        }
    });

});
