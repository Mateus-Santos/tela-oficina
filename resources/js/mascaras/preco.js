export function inicializarMascaraPreco() {
    const precoEl = document.getElementById('preco_uni');

    if (!precoEl) {
        return;
    }

    precoEl.addEventListener('input', () => {
        let valor = precoEl.value.replace(/\D/g, '');

        if (!valor) {
            precoEl.value = '';
            return;
        }

        valor = valor.padStart(3, '0');

        const centavos = valor.slice(-2);
        let inteiro = valor.slice(0, -2).replace(/^0+/, '') || '0';

        inteiro = inteiro.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        precoEl.value = `${inteiro},${centavos}`;
    });
}
