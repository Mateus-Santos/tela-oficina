document.addEventListener('DOMContentLoaded', () => {

    const placaInput = document.getElementById('placa_input');
    const clienteNome = document.getElementById('cliente_nome');
    const idVeiculo = document.getElementById('veiculo_cliente_id');

    if (!placaInput) return;

    placaInput.addEventListener('blur', async () => {

        let placa = placaInput.value.toUpperCase().replace(/[-\s]/g, '');

        if (!placa) return;

        try {

            const response = await fetch(`/api/veiculo/placa/${placa}`);

            if (!response.ok) {
                clienteNome.value = '';
                idVeiculo.value = '';
                alert('Veículo não encontrado');
                return;
            }

            const data = await response.json();

            clienteNome.value = data.cliente_nome;
            idVeiculo.value = data.veiculo_id;

        } catch (error) {
            console.error(error);
        }

    });

});