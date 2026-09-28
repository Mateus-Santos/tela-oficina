import { inicializarMascaraPreco } from '../mascaras/preco.js';
import { inicializarImportacaoAplicacoes } from './importarAplicacoes.js';
import { inicializarDadosAdicionaisProduto } from './dadosAdicionaisProduto.js';
import { inicializarExclusaoImagemProduto } from './excluirImagem.js';
import { inicializarOrdenacaoImagensProduto } from './ordenarImagens.js';

document.addEventListener('DOMContentLoaded', () => {
    inicializarMascaraPreco();
    inicializarImportacaoAplicacoes();
    inicializarDadosAdicionaisProduto();
    inicializarExclusaoImagemProduto();
    inicializarOrdenacaoImagensProduto();
});
