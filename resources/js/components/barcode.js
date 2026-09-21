const EAN_L = {
    0: '0001101',
    1: '0011001',
    2: '0010011',
    3: '0111101',
    4: '0100011',
    5: '0110001',
    6: '0101111',
    7: '0111011',
    8: '0110111',
    9: '0001011'
};

const EAN_G = {
    0: '0100111',
    1: '0110011',
    2: '0011011',
    3: '0100001',
    4: '0011101',
    5: '0111001',
    6: '0000101',
    7: '0010001',
    8: '0001001',
    9: '0010111'
};

const EAN_R = {
    0: '1110010',
    1: '1100110',
    2: '1101100',
    3: '1000010',
    4: '1011100',
    5: '1001110',
    6: '1010000',
    7: '1000100',
    8: '1001000',
    9: '1110100'
};

const PARITY = {
    0: 'LLLLLL',
    1: 'LLGLGG',
    2: 'LLGGLG',
    3: 'LLGGGL',
    4: 'LGLLGG',
    5: 'LGGLLG',
    6: 'LGGGLL',
    7: 'LGLGLG',
    8: 'LGLGGL',
    9: 'LGGLGL'
};

function calcularDigitoVerificador(codigo) {
    const digitos = codigo.split('').map(Number);
    let soma = 0;

    for (let i = 0; i < 12; i++) {
        soma += digitos[i] * (i % 2 === 0 ? 1 : 3);
    }

    return (10 - (soma % 10)) % 10;
}

function normalizarCodigo(codigo) {
    const valor = String(codigo || '').replace(/\D/g, '');

    if (valor.length !== 13) {
        return null;
    }

    const digitoInformado = Number(valor[12]);
    const digitoCalculado = calcularDigitoVerificador(
        valor.substring(0, 12)
    );

    if (digitoInformado !== digitoCalculado) {
        return null;
    }

    return valor;
}

function gerarPadraoEan13(codigo) {
    const primeiroDigito = Number(codigo[0]);
    const paridade = PARITY[primeiroDigito];

    let padrao = '101';

    for (let i = 1; i <= 6; i++) {
        const digito = Number(codigo[i]);

        padrao += paridade[i - 1] === 'L'
            ? EAN_L[digito]
            : EAN_G[digito];
    }

    padrao += '01010';

    for (let i = 7; i <= 12; i++) {
        padrao += EAN_R[Number(codigo[i])];
    }

    padrao += '101';

    return padrao;
}

function criarElementoTexto(svg, texto, x, y, tamanho = 8) {
    const elemento = document.createElementNS(
        'http://www.w3.org/2000/svg',
        'text'
    );

    elemento.setAttribute('x', x);
    elemento.setAttribute('y', y);
    elemento.setAttribute('text-anchor', 'middle');
    elemento.setAttribute('font-family', 'Arial, Helvetica, sans-serif');
    elemento.setAttribute('font-size', tamanho);
    elemento.setAttribute('font-weight', '400');
    elemento.setAttribute('fill', '#000');
    elemento.textContent = texto;

    svg.appendChild(elemento);

    return elemento;
}

function criarSvg(codigo) {
    const padrao = gerarPadraoEan13(codigo);

    /*
     * O SVG mantém a proporção real do EAN-13.
     *
     * 113 x 42 evita a deformação horizontal.
     */
    const largura = 113;
    const altura = 42;

    const margem = 9;
    const inicioBarras = margem + 3;

    const alturaBarras = 27;
    const alturaGuardas = 30;

    const svg = document.createElementNS(
        'http://www.w3.org/2000/svg',
        'svg'
    );

    svg.setAttribute(
        'viewBox',
        `0 0 ${largura} ${altura}`
    );

    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '100%');

    /*
     * Mantém a proporção original.
     * O navegador pode deixar pequenas margens laterais,
     * mas o código não será deformado.
     */
    svg.setAttribute(
        'preserveAspectRatio',
        'xMidYMid meet'
    );

    svg.setAttribute('role', 'img');

    svg.setAttribute(
        'aria-label',
        `Código de barras ${codigo}`
    );

    svg.setAttribute(
        'shape-rendering',
        'crispEdges'
    );

    const grupoBarras = document.createElementNS(
        'http://www.w3.org/2000/svg',
        'g'
    );

    grupoBarras.setAttribute('fill', '#000');

    for (let i = 0; i < padrao.length; i++) {
        if (padrao[i] !== '1') {
            continue;
        }

        const barra = document.createElementNS(
            'http://www.w3.org/2000/svg',
            'rect'
        );

        const ehGuardaInicial = i <= 2;
        const ehGuardaCentral = i >= 45 && i <= 49;
        const ehGuardaFinal = i >= 92 && i <= 94;

        barra.setAttribute(
            'x',
            inicioBarras + i
        );

        barra.setAttribute('y', 2);

        barra.setAttribute('width', 1);

        barra.setAttribute(
            'height',
            ehGuardaInicial ||
            ehGuardaCentral ||
            ehGuardaFinal
                ? alturaGuardas
                : alturaBarras
        );

        grupoBarras.appendChild(barra);
    }

    svg.appendChild(grupoBarras);

    /*
     * Primeiro dígito.
     */
    criarElementoTexto(
        svg,
        codigo[0],
        3.5,
        39,
        8
    );

    /*
     * Dígitos 1-6.
     */
    for (let i = 1; i <= 6; i++) {
        const centro =
            inicioBarras +
            3.5 +
            ((i - 1) * 7);

        criarElementoTexto(
            svg,
            codigo[i],
            centro,
            39,
            8
        );
    }

    /*
     * Dígitos 7-12.
     */
    const inicioDireita =
        inicioBarras +
        3 +
        42 +
        5;

    for (let i = 7; i <= 12; i++) {
        const indice = i - 7;

        const centro =
            inicioDireita +
            3.5 +
            (indice * 7);

        criarElementoTexto(
            svg,
            codigo[i],
            centro,
            39,
            8
        );
    }

    return svg;
}

function renderizarBarcode(elemento) {
    const codigo = normalizarCodigo(
        elemento.dataset.barcode
    );

    elemento.innerHTML = '';

    if (!codigo) {
        elemento.textContent = 'Código EAN-13 inválido';
        elemento.classList.add('text-muted');
        return;
    }

    elemento.appendChild(
        criarSvg(codigo)
    );
}

function inicializarBarcodes() {
    document
        .querySelectorAll('[data-barcode]')
        .forEach(renderizarBarcode);
}

document.addEventListener(
    'DOMContentLoaded',
    inicializarBarcodes
);

export {
    inicializarBarcodes,
    renderizarBarcode
};
