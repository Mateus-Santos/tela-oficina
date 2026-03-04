document.addEventListener('DOMContentLoaded', () => {

    const placaInput = document.getElementById('placa_input');
    const clienteNome = document.getElementById('cliente_nome');
    const idVeiculo = document.getElementById('veiculo_cliente_id');
    const osSelect = document.getElementById('ordem_servico_select');

    if (!placaInput) return;

    placaInput.addEventListener('blur', async () => {

        let placa = placaInput.value.toUpperCase().replace(/[-\s]/g, '');

        if (!placa) return;

        osSelect.innerHTML = `<option>Carregando...</option>`;

        try {

            const response = await fetch(`/api/veiculo/placa/${placa}`);

            if (!response.ok) {
                clienteNome.value = '';
                idVeiculo.value = '';
                osSelect.innerHTML = `<option value="">Veículo não encontrado</option>`;
                return;
            }

            const data = await response.json();

            // Preenche cliente e id veículo
            clienteNome.value = data.cliente_nome;
            idVeiculo.value = data.veiculo_id;

            // Limpa select
            osSelect.innerHTML = `<option value="">Selecione uma O.S</option>`;

            if (data.ordens_servico.length === 0) {
                osSelect.innerHTML = `<option value="">Nenhuma O.S encontrada</option>`;
                return;
            }

            // Preenche as OS
            data.ordens_servico.forEach(os => {
                const option = document.createElement('option');
                option.value = os.id;
                option.textContent = os.descricao;
                osSelect.appendChild(option);
            });

        } catch (error) {
            console.error(error);
            osSelect.innerHTML = `<option value="">Erro ao carregar O.S</option>`;
        }

    });

});