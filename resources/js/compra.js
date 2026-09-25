import inicializarChaveNfe from './compras/chave-nfe.js';
import inicializarItensCompra from './compras/itens.js';
import inicializarAnexosCompra from './compras/anexos.js';

document.addEventListener('DOMContentLoaded', function () {
    inicializarChaveNfe();
    inicializarItensCompra();
    inicializarAnexosCompra();
});
