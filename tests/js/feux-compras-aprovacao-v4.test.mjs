import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { orientacaoAprovacao } from '../../resources/js/compras/aprovacao/resumo-v4.js';
const read = path => readFileSync(path, 'utf8');

test('D03: divergencia exige orientacao de conciliacao, sem gerar conta', () => {
  for (const acao of ['gerar', 'confirmar-geracao', 'nao-gerar', 'voltar']) {
    const orientacao = orientacaoAprovacao(acao, true);
    assert.match(orientacao, /sem gerar conta/);
    assert.match(orientacao, /D03/);
  }
});
test('D03: caminhos de aprovacao comunicam opcoes sem alterar submetimento', () => {
  assert.match(orientacaoAprovacao('gerar'), /Configure as parcelas/);
  assert.match(orientacaoAprovacao('confirmar-geracao'), /Laravel/);
  assert.match(orientacaoAprovacao('nao-gerar'), /não cadastra parcelas/);
  assert.match(orientacaoAprovacao('voltar'), /Escolha aprovar/);
});
test('modal preserva rotas, CSRF e dois caminhos, com restricao na divergencia', () => {
  const m = read('resources/views/compra/show/_modal_aprovacao.blade.php');
  assert.match(m, /data-feux-aprovacao-v4/);
  assert.match(m, /route\('compras\.aprovar', \$compra\)/);
  assert.match(m, /@csrf/);
  assert.match(m, /name="gerar_conta"/);
  assert.match(m, /data-aprovacao="nao-gerar"/);
  assert.match(m, /data-aprovacao="gerar"/);
  assert.match(m, /@unless \(\$compraPossuiDivergencia\)/);
});
test('painel de leitura mostra itens e nao faz operacoes financeiras', () => {
  const m = read('resources/views/compra/show/_modal_aprovacao.blade.php');
  const js = read('resources/js/compras/aprovacao/resumo-v4.js');
  assert.match(m, /\$compra->itens->count\(\)/);
  assert.match(m, /quantidade_conferida !== null/);
  assert.match(m, /data-feux-aprovacao-orientacao/);
  assert.doesNotMatch(js, /\.submit\(|\.preventDefault\(|\.value\s*=/);
  assert.doesNotMatch(js, /fetch\(|axios\.|\.post\(/);
});
test('integracao preserva inicializadores e estilos sao isolados', () => {
  const entry = read('resources/js/compras/show.js');
  const style = read('resources/scss/_app.scss');
  const css = read('resources/scss/_feux_compras_aprovacao_v4.scss');
  assert.match(entry, /inicializarAprovacaoCompra\(\);/);
  assert.match(entry, /inicializarConferenciaCompra\(\);/);
  assert.match(entry, /inicializarResumoAprovacao\(\);/);
  assert.match(style, /_feux_compras_aprovacao_v4\.scss/);
  assert.match(css, /#modalAprovarCompra\[data-feux-aprovacao-v4\]/);
});
