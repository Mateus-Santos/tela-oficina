import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
const file = (p) => readFileSync(p, 'utf8');
const telas = {
  compra: ['create', 'edit', 'index', 'show'],
  'financeiro/contas_receber': ['create','edit','index','show'],
  contas_pagar: ['create', 'edit', 'index', 'show'],
  notas_item: ['cadastro_notas_itens','editar_notas_itens','listar_notas_itens'],
  cliente: ['cadastrocliente','editarcliente'],
  veiculosclientes: ['cadastroveiculosclientes','editarveiculosclientes'],
  ordemservico: ['cadastro_os','listar_os'],
  produto: ['cadastroproduto','editarproduto'],
  fornecedor: ['create','edit','index'],
  estoque: ['saida','index','ajuste','movimentacoes'],
};
test('visual moderno restrito a trinta telas e preserva nome da classe cadastro', () => {
  let count=0;
  for (const [modulo,nomes] of Object.entries(telas)) {
    for (const nome of nomes) {
      const view=file(`resources/views/${modulo}/${nome}.blade.php`);
      assert.match(view, /class="container cadastro[^"\n]* feux-moderno"/);
      count++;
    }
  }
  assert.equal(count,30);
});
test('CSS conserva aliases e usa nomes descritivos na base de cadastros', () => {
  const colors=file('resources/scss/_colors.scss');
  const cadastro=file('resources/scss/_style_cad.scss');
  const app=file('resources/scss/_app.scss');
  const sheet=file('resources/scss/_feux_modulos.scss');
  for (const nome of ['preto','branco','amarelo','gradiente-escuro','gradiente-amarelo'])
    assert.ok(colors.includes(`$${nome}:`), nome);
  assert.doesNotMatch(cadastro, /\$color_\d/);
  assert.match(app, /_feux_modulos/);
  assert.match(sheet, /\.cadastro\.feux-moderno/);
  assert.match(sheet, /content: none/);
  assert.doesNotMatch(sheet,/https?:\/\//);
});
test('forms selecionados preservam acao nativa e ganham feedback opt-in', () => {
  const js=file('resources/js/feux-modulos.js');
  assert.match(file('resources/js/app.js'), /import '\.\/feux-modulos\.js'/);
  assert.match(js, /form\.checkValidity\(\)/);
  assert.match(js, /addEventListener\('pageshow'/);
  assert.doesNotMatch(js, /localStorage|sessionStorage|fetch\(/);
  for (const modulo of ['contas_pagar','cliente','fornecedor']) {
    const arquivos=modulo==='cliente'?['cadastrocliente','editarcliente']:['create','edit'];
    for (const nome of arquivos) {
      const view=file(`resources/views/${modulo}/${nome}.blade.php`);
      assert.match(view, /<form data-feux-form="true"/);
      assert.match(view, /@csrf/);
    }
  }
  assert.doesNotMatch(file('resources/views/compra/create.blade.php'), /data-feux-form/);
});
