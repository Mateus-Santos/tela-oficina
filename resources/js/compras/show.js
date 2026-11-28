import inicializarAprovacaoCompra from './aprovacao/modal';
import inicializarGeracaoContaCompra from './gerar-conta/modal';
import inicializarConferenciaCompra from './conferencia-ux-v3.js';
import inicializarResumoAprovacao from './aprovacao/resumo-v4.js';

document.addEventListener('DOMContentLoaded', () => {
    inicializarAprovacaoCompra();
    inicializarGeracaoContaCompra();
    inicializarConferenciaCompra();
    inicializarResumoAprovacao();
});
