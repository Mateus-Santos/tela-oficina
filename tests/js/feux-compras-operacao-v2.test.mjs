import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { resumirDevolucaoOperacional } from '../../resources/js/compras/devolucao-operacional.js';
const get = p => readFileSync(p, 'utf8');
const linha = (quantidade, limite = '2.500') => ({ id: '1', quantidade, limite });
test('D01A aceita exatamente o saldo em milesimos', () => {
    const r = resumirDevolucaoOperacional([linha('2.500')]);
    assert.equal(r.valido, true);
    assert.equal(r.milessimos, 2500);
});
test('D01A recusa ultrapassar saldo fisico e valores invalidos', () => {
    assert.equal(resumirDevolucaoOperacional([linha('2.501')]).valido, false);
    assert.equal(resumirDevolucaoOperacional([linha('1.2345')]).valido, false);
    assert.equal(resumirDevolucaoOperacional([linha('-1')]).valido, false);
});
test('D01A exige ao menos um item positivo, e suporta soma decimal exata', () => {
    assert.equal(resumirDevolucaoOperacional([linha('0')]).valido, false);
    const r = resumirDevolucaoOperacional([linha('0.001'), { id: '2', quantidade: '0.002', limite: '1.000' }]);
    assert.deepEqual([r.itens, r.milessimos, r.valido], [2, 3, true]);
});
test('D01A mantem action, token UUID, CSRF e validacao nativa', () => {
    const v = get('resources/views/compra/devolucoes/create.blade.php');
    assert.match(v, /data-feux-devolucao-operacional/);
    assert.match(v, /compras\.devolucoes\.store/);
    assert.match(v, /operacao_uuid/);
    assert.match(v, /@csrf/);
    assert.match(v, /confirmacao_fisica/);
    assert.match(v, /@vite\(\['resources\/js\/compras\/devolucao-operacional\.js'\]\)/);
});
test('Compras: atalhos preservam filtros existentes, sem rotas novas', () => {
    const v = get('resources/views/compra/index.blade.php');
    assert.match(v, /feux-compras-v2__filtros/);
    assert.match(v, /route\('compras\.index'/);
    assert.match(v, /request\(\)->except\(\['page', 'status'\]\)/);
    assert.match(v, /@foreach\(\$comprasDoDia as \$compra\)/);
});
test('Compra: navegacao local preserva resumo D04 e integracoes', () => {
    const v = get('resources/views/compra/show.blade.php');
    assert.match(v, /feux-compra-situacao/);
    assert.match(v, /devolucoes-compra-titulo/);
    assert.match(v, /feux-compra-integracoes/);
    assert.match(v, /@include\('compra\.devolucoes\.resumo'\)/);
});
test('SCSS do lote isolado, sem tocar CSS global ou backend', () => {
    const scss = get('resources/scss/_feux_compras_operacao_v2.scss');
    assert.match(scss, /feux-compras-v2__/);
    assert.doesNotMatch(scss, /table\s*\{/);
    assert.match(get('resources/scss/_app.scss'), /_feux_compras_operacao_v2\.scss/);
});
