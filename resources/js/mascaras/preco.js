export function formatarPreco(elemento) {
    if (!elemento) {
        return;
    }

    let valor = elemento.value.replace(/\D/g, '');

    if (!valor) {
        elemento.value = '';
        return;
    }

    valor = valor.padStart(3, '0');

    const centavos = valor.slice(-2);

    let inteiro = valor
        .slice(0, -2)
        .replace(/^0+/, '') || '0';

    inteiro = inteiro.replace(
        /\B(?=(\d{3})+(?!\d))/g,
        '.'
    );

    elemento.value = `${inteiro},${centavos}`;
}

export function obterValorNumerico(elemento) {
    if (!elemento || !elemento.value) {
        return 0;
    }

    const valor = elemento.value
        .replace(/\./g, '')
        .replace(',', '.');

    return parseFloat(valor) || 0;
}

export function aplicarMascaraPreco(elemento) {
    if (!elemento) {
        return;
    }

    elemento.addEventListener('input', () => {
        formatarPreco(elemento);
    });
}

export function inicializarMascaraPreco(elemento = null) {
    if (elemento) {
        aplicarMascaraPreco(elemento);
        return;
    }

    const precoEl = document.getElementById('preco_uni');

    if (!precoEl) {
        return;
    }

    aplicarMascaraPreco(precoEl);
}
