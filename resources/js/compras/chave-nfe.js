function sanitizarChaveNfe(valor) {
    return String(valor ?? '')
        .replace(/\D/g, '')
        .slice(0, 44);
}

export default function inicializarChaveNfe() {
    const input =
        document.getElementById('chave_nf');

    if (!input) {
        return;
    }

    function sanitizar() {
        input.value =
            sanitizarChaveNfe(input.value);
    }

    input.addEventListener(
        'input',
        sanitizar
    );

    sanitizar();
}
