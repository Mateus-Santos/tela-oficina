import { inicializarMascaraPreco } from './mascaras/preco.js';
import { inicializarPreviewImagem } from './components/previewImagem.js';
import { inicializarImportacaoAplicacoes } from './components/importarAplicacoes.js';

document.addEventListener('DOMContentLoaded', () => {
    inicializarMascaraPreco();
    inicializarPreviewImagem();
    inicializarImportacaoAplicacoes();
});
