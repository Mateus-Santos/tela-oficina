import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { proximoIndiceDisponivel } from '../../resources/js/compras/indices.js';
import { nomeCampoHtml } from '../../resources/js/components/form-feedback.js';
const read = p => readFileSync(p, 'utf8');

test('indices esparsos de itens nunca colidem com novos itens', () => {
    assert.equal(proximoIndiceDisponivel(['0', '2', '7']), 8);
    assert.equal(proximoIndiceDisponivel(['0', '1']), 2);
    assert.equal(proximoIndiceDisponivel([]), 0);
    assert.equal(proximoIndiceDisponivel(['bad', '-1', '3']), 4);
});
test('mensagens Laravel apontam para nomes HTML de campos aninhados', () => {
    assert.equal(nomeCampoHtml('itens.7.produto_id'), 'itens[7][produto_id]');
    assert.equal(nomeCampoHtml('anexos.1.arquivo'), 'anexos[1][arquivo]');
    assert.equal(nomeCampoHtml('fornecedor_id'), 'fornecedor_id');
});
test('Compras conserva old() e recupera cada item e metadado de anexo', () => {
    const s=read('resources/views/compra/_form.blade.php');
    for (const x of ["fornecedor_id", "old('numero_nf'", "old('itens')", "old('anexos')", "old('observacoes'", 'anexos[{{ $indiceAnexo }}][tipo]', 'anexos[{{ $indiceAnexo }}][observacoes]', 'compra-erros-json']) assert.ok(s.includes(x), x);
    assert.match(s, /@foreach \(\$anexosAnteriores as \$indiceAnexo => \$anexoAnterior\)/);
    assert.match(s, /Por segurança, arquivos enviados precisam ser selecionados novamente/);
});
test('feedback e loading sao opcionais e so ativos em Compras', () => {
    const c=read('resources/js/compra.js');
    const j=read('resources/js/components/form-feedback.js');
    assert.match(c, /inicializarFeedbackFormulario\(\)/);
    assert.match(j, /getElementById\('compra-form'\)/);
    assert.match(j, /form\.addEventListener\('submit'/);
    assert.match(j, /addEventListener\('pageshow'/);
    assert.match(j, /\.textContent = Array\.isArray\(lista\)/);
    for (const p of ['create','edit']) assert.match(read(`resources/views/compra/${p}.blade.php`), /id="compra-form"/);
});
test('cores nomeadas existem com aliases e estilo nao altera tabelas globais', () => {
    const s=read('resources/scss/_colors.scss');
    for (const c of ['preto','branco','amarelo','vermelho','verde-claro','verde-escuro']) assert.match(s, new RegExp('\\$'+c+':'));
    assert.match(read('resources/scss/_app.scss'), /_compra_feedback/);
    assert.match(read('resources/scss/_compra_feedback.scss'), /\.feux-compra/);
});
