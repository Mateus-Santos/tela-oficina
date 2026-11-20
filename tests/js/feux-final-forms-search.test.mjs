import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';

const files = [
  'resources/views/cliente/cadastrocliente.blade.php',
  'resources/views/cliente/editarcliente.blade.php',
  'resources/views/veiculosclientes/_form.blade.php',
  'resources/views/veiculosclientes/cadastroveiculosclientes.blade.php',
  'resources/views/veiculosclientes/editarveiculosclientes.blade.php',
  'resources/views/ordemservico/cadastro_os.blade.php',
  'resources/views/notas_item/_form.blade.php',
  'resources/views/compra/_form.blade.php',
];
const fieldNames = text => [...text.matchAll(/<(?:input|select|textarea)\b[^>]*\bname="([^"]+)"/gs)]
  .map(match => match[1]).sort();
const wires = text => [...text.matchAll(/\bwire:[a-zA-Z0-9_.:-]+\s*=\s*"[^"]*"/g)]
  .map(match => match[0]).sort();

for (const file of files) {
  test(`FE-UX: campos, metodos e Livewire preservados em ${file}`, () => {
    const before = execFileSync('git', ['show', `HEAD:${file}`], { encoding: 'utf8' });
    const after = readFileSync(file, 'utf8');
    assert.deepEqual(fieldNames(after), fieldNames(before));
    assert.deepEqual(wires(after), wires(before));
    for (const marker of ['<form', '@csrf', '@method(', '@vite(']) {
      assert.equal(after.split(marker).length, before.split(marker).length);
    }
  });
}

test('FE-UX: container principal nao corta dropdowns e preserva rolagem das tabelas', () => {
  const css = readFileSync('resources/scss/_style_cad.scss', 'utf8');
  const cadastro = css.slice(css.indexOf('.cadastro {'), css.indexOf('\n}\n\n.cadastro h1'));
  assert.match(cadastro, /overflow: visible;/);
  assert.doesNotMatch(cadastro, /overflow-x: auto;/);
  assert.match(css, /\.item-produto-resultados\s*\{/);
  assert.match(css, /overscroll-behavior: contain;/);
  assert.match(css, /\.table-responsive/);
  assert.match(css, /\.busca-selecao\s*\{/);
  assert.match(css, /@media \(max-width: 575\.98px\)/);
});

test('FE-UX: filtros ocupam a coluna sem maximo artificial, mantendo teclado e botao', () => {
  const css = readFileSync('resources/scss/_filtros.scss', 'utf8');
  assert.doesNotMatch(css, /max-width:\s*20rem/);
  assert.match(css, /\.cadastro \.filtros-container/);
  assert.match(css, /max-width:\s*100%/);
  assert.match(css, /min-height:\s*2\.75rem/);
});

test('FE-UX: auxilia teclado movel e retorno em formularios sem alterar backend', () => {
  const filesContent = Object.fromEntries(files.map(file => [file, readFileSync(file, 'utf8')]));
  assert.match(filesContent['resources/views/cliente/cadastrocliente.blade.php'], /id="cliente-pessoa-existente-ajuda"/);
  assert.match(filesContent['resources/views/veiculosclientes/_form.blade.php'], /autocapitalize="characters"/);
  assert.match(filesContent['resources/views/ordemservico/cadastro_os.blade.php'], /inputmode="decimal"/);
  assert.match(filesContent['resources/views/notas_item/_form.blade.php'], /inputmode="numeric"/);
  assert.match(filesContent['resources/views/compra/_form.blade.php'], /inputmode="numeric"/);
  for(const file of ['resources/views/veiculosclientes/cadastroveiculosclientes.blade.php',
                     'resources/views/veiculosclientes/editarveiculosclientes.blade.php']) {
    assert.match(filesContent[file], /route\('veiculosclientes\.index'\)/);
  }
});
