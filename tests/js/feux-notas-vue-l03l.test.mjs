import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
const app = readFileSync('resources/js/notas/vue/NotasApp.vue','utf8');
const card = readFileSync('resources/js/notas/vue/components/NotaCard.vue','utf8');
const css = readFileSync('resources/css/notas/vue.css','utf8');
const api = readFileSync('app/Http/Controllers/NotasVueController.php','utf8');

// A query é filtrada no servidor ANTES de contar, somar e paginar.
test('Canceladas: todas exclui por padrao; switch inclui; aba Canceladas independe do switch', () => {
  assert.match(api, /'incluir_canceladas'\s*=>\s*\['nullable',\s*'boolean'\]/);
  assert.match(api, /\$f\['status'\] === 'Todos' && ! \$request->boolean\('incluir_canceladas'\)/);
  assert.match(api, /\$base->where\('notas\.status', '!=', 'Cancelado'\)/);
  assert.ok(api.indexOf("$base->where('notas.status', '!=', 'Cancelado')") < api.indexOf('$total = (clone $base)->count()'));
  assert.match(app, /incluir_canceladas: initial\.get\('incluir_canceladas'\) === '1'/);
  assert.match(app, /@change="toggleCancelled"/);
  assert.match(app, /filtros\.status === 'Todos'/);
  assert.match(app, /filtros\.status === 'Cancelado'/);
  assert.match(app, /incluir_canceladas:'0'/);
});
test('Notas canceladas: card tem estilo semantico, sem drag ou troca de etapa', () => {
  assert.match(card, /'sos-note--cancelled': nota\.status === 'Cancelado'/);
  assert.match(card, /sos-tag--\$\{nota\.status === 'Aberto' \? 'open' : nota\.status === 'Cancelado' \? 'cancelled'/);
  assert.match(card, /const podeMover = computed\(\(\) => props\.nota\.status === 'Aberto'\)/);
  assert.match(card, /:draggable="podeMover && !loading"/);
  assert.match(card, /v-if="podeMover"/);
  for (const token of ['--sos-bg-card-cancelled','--sos-border-card-cancelled','--sos-text-card-cancelled','--sos-bg-tag-cancelled','--sos-text-tag-cancelled']) {
    assert.ok(css.includes(token + ':'), token);
  }
  assert.match(css, /\.sos-note\.sos-note--cancelled \{/);
});
test('Kanban acessivel com um unico scroller horizontal e sem prender rolagem vertical', () => {
  assert.match(app, /const boardRef = ref\(null\)/);
  assert.match(app, /@scroll\.passive="updateBoardMetrics"/);
  assert.doesNotMatch(app, /syncTopScroll|syncBoardScroll|railRef/);
  assert.match(app, /@click="scrollBoard\(-1\)"/);
  assert.match(app, /@click="scrollBoard\(1\)"/);
  assert.match(css, /overscroll-behavior-y:auto !important/);
  assert.match(css, /max-height:none !important/);
  assert.match(app, /@change="jumpToStage"/);
});
test('Painel superior compacto sem padding global de section', () => {
  assert.match(css, /\.sos-vue-app > section\.sos-metrics\s*\{[^}]*padding:0 !important/);
  assert.match(css, /\.sos-vue-app > section\.sos-toolbar\s*\{[^}]*padding:0 !important/);
  assert.match(css, /\.sos-vue-head \{ margin-bottom: 10px/);
  assert.match(css, /\.sos-metric \{ padding:10px 13px/);
  assert.match(app, /class="sos-cancel-rule" role="note"/);
});
test('Movimentacao da L03K permanece reativa e sem recarga de quadro', () => {
  const move=app.split('async function moveNote(n, id) {')[1].split('\nfunction dropNote')[0];
  assert.ok(move);
  assert.doesNotMatch(move,/await loadBoard\(true\);/);
  assert.match(move,/reconciliarEtapaNota\(/);
  assert.match(move,/updateBoardMetrics/);
  assert.match(app,/function setView\(v\) \{ view\.value = v; updateUrl\(\); \}/);
});
