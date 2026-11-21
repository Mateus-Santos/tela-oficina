import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
const view = readFileSync('resources/views/compra/index.blade.php', 'utf8');
const status = readFileSync('resources/views/compra/partials/status-compra.blade.php', 'utf8');
const indicadores = readFileSync('resources/views/compra/partials/indicadores-integracao.blade.php', 'utf8');
const css = readFileSync('resources/scss/_compra_mobile.scss', 'utf8');
const app = readFileSync('resources/scss/_app.scss', 'utf8');

test('cards mobile e tabela desktop coexistem sem esconder informacoes', () => {
    assert.match(view, /class="feux-compra-mobile d-lg-none"/);
    assert.match(view, /class="table-responsive d-none d-lg-block"/);
    for (const campo of ['numero_nf', 'serie_nf', 'fornecedor', 'valor_total']) {
        assert.ok(view.includes(campo), `Falta ${campo}`);
    }
    assert.equal((view.match(/@foreach\(\$comprasDoDia as \$compra\)/g) || []).length, 2);
});

test('status e indicadores sao reutilizados no mobile e desktop', () => {
    assert.equal((view.match(/@include\('compra.partials.status-compra'/g) || []).length, 2);
    assert.equal((view.match(/@include\('compra.partials.indicadores-integracao'/g) || []).length, 2);
    for (const s of ['pendente', 'conferindo', 'aprovada', 'cancelada']) assert.ok(status.includes(s));
    assert.match(indicadores, /integracao_estoque_status/);
    assert.match(indicadores, /integracao_financeiro_status/);
});

test('acoes de excluir e editar permanecem condicionadas ao status', () => {
    assert.equal((view.match(/@if\(in_array\(\$compra->status, \['pendente', 'conferindo'\], true\)\)/g) || []).length, 2);
    assert.equal((view.match(/route\('compras\.destroy', \$compra\)/g) || []).length, 2);
    assert.equal((view.match(/@method\('DELETE'\)/g) || []).length, 2);
    assert.equal((view.match(/route\('compras\.show', \$compra\)/g) || []).length, 2);
    assert.equal((view.match(/route\('compras\.edit', \$compra\)/g) || []).length, 2);
    assert.match(view, /Tem certeza que deseja excluir esta compra\?/);
});

test('estilo de listagem e isolado e nao inclui bibliotecas novas', () => {
    assert.match(view, /feux-listagem-compras/);
    assert.match(css, /\.feux-listagem-compras/);
    assert.match(css, /max-width: 575\.98px/);
    assert.match(app, /@use '\.\/_compra_mobile\.scss';/);
});
