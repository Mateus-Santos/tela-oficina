import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
const read = path => readFileSync(path, 'utf8');
const app = read('resources/js/notas/vue/NotasApp.vue');
const form = read('resources/js/notas/vue/components/NotaForm.vue');
const drawer = read('resources/js/components/sos/UiFilterDrawer.vue');
const modal = read('resources/js/components/sos/UiModal.vue');
const css = read('resources/css/notas/vue.css');

test('L03J: paginas, filtros e carregar mais nao aceitam respostas obsoletas', () => {
  assert.match(app, /\+\+boardGeneration/);
  assert.match(app, /boardAbort\?\.abort\(\)/);
  assert.match(app, /generation !== boardGeneration/);
  assert.match(app, /loading\.value \|\| !pagination\.value\?\.possui_mais \|\| loadingMore\.value/);
  assert.match(app, /boardAbort\?\.signal/);
});
test('L03J: detalhes concorrentes e fechamento nao exibem outra Nota', () => {
  assert.match(app, /\+\+detailGeneration/);
  assert.match(app, /generation === detailGeneration && modalOpen\.value/);
  assert.match(app, /modalMode\.value === 'show'/);
});
test('L03J: erro de gravacao fica visivel dentro do formulario', () => {
  assert.match(app, /formError\.value = e\.message/);
  assert.match(app, /:server-error="formError"/);
  assert.match(form, /serverError: \{ type: String, default: '' \}/);
  assert.match(form, /role="alert"[^\n]*serverError \|\| erro/);
});
test('L03J: pesquisas cliente, itens e veiculos usam isolamento de resultados', () => {
  assert.match(form, /clientTimer, itemTimer, clientToken/);
  assert.match(form, /vehicleToken/);
  assert.match(form, /if \(generation === clientToken\)/);
  assert.match(form, /if \(generation === itemToken\)/);
  assert.match(form, /generation === vehicleToken && form\.value\.cliente_id === cliente/);
  assert.match(form, /function mudarTipoItem\(tipo\)/);
  assert.doesNotMatch(form, /let timer, token = 0/);
});
test('L03J: drawer e modal tratam tab, escape e foco', () => {
  assert.match(drawer, /event\.key === 'Escape'/);
  assert.match(drawer, /event\.key !== 'Tab'/);
  assert.match(drawer, /trigger\.focus\(\)/);
  assert.match(drawer, /aria-modal="true"/);
  assert.match(modal, /showModal\(\)/);
  assert.match(modal, /trigger\.focus\(\)/);
  assert.match(css, /:focus-visible/);
});
