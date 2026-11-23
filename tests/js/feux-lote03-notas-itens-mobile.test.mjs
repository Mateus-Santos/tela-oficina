import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';

const BASE = '7b4bba06e8390324c4ae626f5abfd4cc1a52fd1c';
const files = [
  'resources/views/notas_item/_form.blade.php',
  'resources/views/notas_item/show_notas_itens.blade.php',
  'resources/views/notas_item/modal/_modal_adicionar_item.blade.php',
  'resources/views/notas_item/modal/_modal_descontos.blade.php',
];
const read = path => readFileSync(path, 'utf8');
const old = path => execFileSync('git', ['show', `${BASE}:${path}`], { encoding: 'utf8' });
const attrs = text => [...text.matchAll(/\b(?:name|id|action|method|data-bs-target|data-bs-toggle|type)=(?:"[^"]*"|'[^']*')/g)].map(x => x[0]).sort();
const routes = text => [...text.matchAll(/route\(\s*'([^']+)'/g)].map(x => x[1]).sort();

for (const file of files) {
  test(`Notas L03: preservar atributos, rotas e formulários em ${file}`, () => {
    assert.deepEqual(attrs(read(file)), attrs(old(file)));
    assert.deepEqual(routes(read(file)), routes(old(file)));
    for (const pattern of [/@csrf/g, /@method\(/g, /@vite\(/g]) {
      assert.equal((read(file).match(pattern) || []).length, (old(file).match(pattern) || []).length);
    }
  });
}

test('Notas L03: formulario mantém o tbody dinâmico e oito colunas', () => {
  const text = read(files[0]);
  assert.match(text, /class="table-responsive feux-notas-itens-mobile"/);
  assert.match(text, /id="tabela-itens-os"/);
  assert.match(text, /id="container-itens-dinamicos"/);
  assert.match(text, /id="linha-vazia"/);
  assert.equal((text.match(/name="itens\[/g) || []).length, (old(files[0]).match(/name="itens\[/g) || []).length);
});

test('Notas L03: detalhe preserva tabela e aviso de estoque independente', () => {
  const text = read(files[1]);
  assert.match(text, /class="table-responsive feux-notas-itens-leitura"/);
  assert.match(text, /@foreach\(\$itensExibicao as \$item\)/);
  assert.equal((text.match(/<table\b/g) || []).length, (old(files[1]).match(/<table\b/g) || []).length);
});

test('Notas L03: busca usa identificadores existentes e mensagem acessível', () => {
  const text = read(files[2]);
  for (const id of ['builder_type','builder_item_busca','builder_busca_status','builder_resultados','builder_item_id','btn-adicionar-item']) {
    assert.ok(text.includes(`id="${id}"`), `Identificador perdido: ${id}`);
  }
  assert.match(text, /aria-describedby="builder_busca_status"/);
  assert.match(text, /feux-notas-modal-itens/);
  assert.match(read(files[3]), /feux-notas-modal-descontos/);
});

test('Notas L03: layout mobile distingue as 8 colunas de edição e as 7 de leitura', () => {
  const css = read('resources/scss/_feux_notas_lote03.scss');
  const scssApp = read('resources/scss/_app.scss');
  assert.match(scssApp, /@use '\.\/_feux_notas_lote03\.scss';/);
  for (const c of ['feux-notas-itens-mobile','feux-notas-itens-leitura','feux-notas-modal-itens','feux-notas-modal-descontos']) assert.ok(css.includes(c));
  assert.match(css, /max-width: 767\.98px/);
  assert.match(css, /#linha-vazia/);
  assert.match(css, /td:nth-child\(8\)::before/);
  assert.match(css, /td:nth-child\(7\)::before/);
  assert.doesNotMatch(css, /(^|\n)\s*(table|td|th)\s*\{/);
});
