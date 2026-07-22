document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // MÁSCARA DE PREÇO
    // ============================================================

    const precoEl = document.getElementById('valor');
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

    if (precoEl) {
        precoEl.addEventListener('input', () => {
            let v = precoEl.value.replace(/\D/g, '');
            if (!v) return precoEl.value = '';

            v = v.padStart(3, '0');
            const cents = v.slice(-2);
            let int = v.slice(0, -2).replace(/^0+/, '') || '0';
            int = int.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

            precoEl.value = `${int},${cents}`;
        });
    }

});