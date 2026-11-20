import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';

const paths = {
  notas: 'resources/views/notas_item/listar_notas_itens.blade.php',
  clientes: 'resources/views/cliente/listarcliente.blade.php',
};
const content = (path) => readFileSync(path, 'utf8');
const before = (path) => execFileSync('git', ['show', `${process.env.FEUX_BASE_REF || '04a3e4fa62a71867d58375bf76a8886535bd4cb3'}:${path}`], {encoding: 'utf8'});
const routeNames = (s) => new Set([...s.matchAll(/route\(\s*'([^']+)'/g)].map(x => x[1]));
const formFields = (s) => [...s.matchAll(/\bname="(cliente|veiculo|placa|status|etapa_id|nome|cpf|telefone)"/g)].map(x=>x[1]).sort();
const css = content('resources/scss/_mobile_listagens.scss');
const appScss = content('resources/scss/_app.scss');

test('FEUX mobile: novos componentes mantêm todos os nomes de rotas das duas listagens', () => {
  for (const path of Object.values(paths)) {
    assert.deepEqual(routeNames(content(path)), routeNames(before(path)));
  }
});

test('FEUX mobile: filtros e paginação mantêm os mesmos parâmetros', () => {
  for (const path of Object.values(paths)) {
    assert.deepEqual(formFields(content(path)), formFields(before(path)));
    assert.match(content(path), /->hasPages\(\)/);
    assert.match(content(path), /->links\(\)/);
  }
});

test('Notas: um único card e um único par de seletores Livewire por registro', () => {
  const s = content(paths.notas);
  assert.equal((s.match(/@foreach\(\$notas as \$nota\)/g) || []).length, 1);
  assert.equal((s.match(/'status-nota-selector'/g) || []).length, 1);
  assert.equal((s.match(/'etapa-nota-selector'/g) || []).length, 1);
  for (const tag of ['feux-nota-card__cabecalho','feux-nota-card__dados','feux-nota-card__acoes','feux-nota-card__menu-interno']) assert.ok(s.includes(tag));
});

test('Notas: cliente, valor, data, veículo, placa, estoque, status, etapa permanecem legíveis', () => {
  const s = content(paths.notas);
  for (const value of ["$nota->id", "?->pessoa", "$nota->created_at?->format('d/m/Y')", '$nota->total', 'veiculosCliente', 'placa', 'Estoque', 'status-nota-selector','etapa-nota-selector']) assert.ok(s.includes(value));
  assert.match(s, /aria-label="Nota #\{\{ \$nota->id \}\}"/);
});

test('Notas: PDF cliente/interno, visualizar e exclusão preservados com autorização original', () => {
  const s = content(paths.notas);
  for (const path of ['notas.pdf','notas.pdf.download','notas.pdf.interno.download','notas.show','notas.destroy']) assert.ok(routeNames(s).has(path));
  assert.match(s, /auth\(\)->user\(\)->isAdmin\(\)/);
  assert.match(s, /\$nota->status === 'Aberto'/);
  assert.match(s, /@method\('DELETE'\)/);
  assert.match(s, /@csrf/);
  assert.match(s, /data-bs-auto-close="outside"/);
});

test('Clientes: tabela desktop permanece e existem cards apenas no mobile', () => {
  const s = content(paths.clientes);
  assert.match(s, /table-responsive d-none d-lg-block/);
  assert.match(s, /feux-listagem-clientes__mobile d-lg-none/);
  assert.match(s, /<table class="table table-striped table-hover align-middle">/);
  assert.match(s, /@forelse \(\$clientes as \$cliente\)/);
  assert.match(s, /<article class="card feux-cliente-card/);
  assert.match(s, /<details class="feux-cliente-card__detalhes/);
});

test('Clientes: ações reais, rótulos e exclusão POST/DELETE no mobile', () => {
  const s = content(paths.clientes);
  for (const p of ['clientes.show','clientes.edit','clientes.destroy','veiculosclientes.create']) assert.ok(routeNames(s).has(p));
  for (const label of ['Ficha','Veículo','Editar','Excluir','Outras informações']) assert.ok(s.includes(label));
  assert.ok((s.match(/@csrf/g) || []).length >= 2);
  assert.ok((s.match(/@method\('DELETE'\)/g) || []).length >= 2);
  assert.match(s, /aria-label="Editar cliente/);
  assert.match(s, /\$cliente->pessoa\?->nome \?\? 'Sem nome cadastrado'/);
  assert.match(s, /\$cliente->pessoa\?->cpf \?: 'Não informado'/);
});

test('CSS: somente as listagens-alvo são modificadas, sem regra global de tabelas', () => {
  assert.match(appScss, /@use '\.\/_mobile_listagens\.scss';/);
  assert.match(css, /\.feux-listagem-notas/);
  assert.match(css, /\.feux-listagem-clientes/);
  assert.match(css, /\.feux-nota-card/);
  assert.match(css, /\.feux-cliente-card/);
  assert.doesNotMatch(css, /(^|\n)\s*(table|td|th|\.table-responsive)\s*\{/);
});

test('CSS: alvos touch >=44px, quebra de texto e PDF interno sem largura fixa 290px', () => {
  assert.match(css, /min-height:\s*2\.75rem/);
  assert.match(css, /overflow-wrap:\s*anywhere/);
  assert.match(css, /grid-template-columns:\s*repeat\(2,\s*minmax\(0,\s*1fr\)\)/);
  assert.match(css, /max-width:\s*calc\(100vw - 3rem\)/);
  assert.doesNotMatch(content(paths.notas), /style="min-width: 290px;"/);
});
