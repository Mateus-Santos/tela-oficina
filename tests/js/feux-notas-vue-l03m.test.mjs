import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
const app=readFileSync('resources/js/notas/vue/NotasApp.vue','utf8');
const css=readFileSync('resources/css/notas/vue.css','utf8');
const api=readFileSync('app/Http/Controllers/NotasVueController.php','utf8');
test('sem feedback circular entre barras de scroll; uma superficie horizontal',()=>{
  assert.doesNotMatch(app,/railRef|syncTopScroll|syncBoardScroll|scroll-snap-type/);
  assert.match(app, /const boardRef = ref\(null\)/);
  assert.match(app, /function setBoardOffset\(target\)/);
  assert.match(app, /board\.scrollLeft =/);
  assert.match(app, /@scroll\.passive="updateBoardMetrics"/);
  assert.doesNotMatch(app, /scrollBy\(/);
  assert.match(css, /scroll-snap-type:none !important/);
  assert.match(css, /overflow-x:auto !important; overflow-y:hidden !important/);
});
test('o wheel vertical usa pagina, sem container max-height ou overscroll contain',()=>{
  assert.match(css, /max-height:none !important; min-height:0 !important; height:auto !important/);
  assert.match(css, /overscroll-behavior-x:contain !important; overscroll-behavior-y:auto !important/);
  assert.match(css, /touch-action:pan-x pan-y/);
  assert.match(app, /INITIAL_CARDS_PER_LANE = 2/);
  assert.match(app, /visibleInLane\(grupo\)/);
  assert.match(app, /hiddenInLane\(grupo\)/);
  assert.match(app, /@click="showMoreInLane\(grupo\)"/);
});
test('etapas navegaveis por botao, seletor e slider real sem sincronizacao recursiva',()=>{
  assert.match(app, /@click="scrollBoard\(-1\)"/);
  assert.match(app, /@click="scrollBoard\(1\)"/);
  assert.match(app, /@change="jumpToStage"/);
  assert.match(app, /type="range" min="0" :max="scrollMax"/);
  assert.match(app, /@input="slideBoard"/);
  assert.match(app, /:data-stage-id="grupo.id"/);
});
test('card financeiro apresenta Peças, Serviços e Total dos itens sem misturar faturamento',()=>{
  assert.match(app, /<dt>Peças<\/dt><dd>\{\{ money\(metricas\?\.pecas\) \}\}/);
  assert.match(app, /<dt>Serviços<\/dt><dd>\{\{ money\(metricas\?\.servicos\) \}\}/);
  assert.match(app, /<dt>Total dos itens<\/dt>/);
  assert.match(app, /money\(Number\(metricas\?\.pecas \|\| 0\) \+ Number\(metricas\?\.servicos \|\| 0\)\)/);
  assert.match(app, /Valores em itens não representam faturamento realizado/);
  assert.match(api, /'pecas' => \(float\) \(\$tipos->pecas \?\? 0\)/);
  assert.match(api, /'servicos' => \(float\) \(\$tipos->servicos \?\? 0\)/);
  for(const token of ['--sos-text-metric-label','--sos-text-metric-value','--sos-border-metric-divider']) assert.ok(css.includes(token+':'));
});
test('movimento continua reativo e canceladas permanecem imoveis',()=>{
  const move=app.split('async function moveNote(n, id) {')[1].split('function dropNote')[0];
  assert.doesNotMatch(move,/await loadBoard\(true\);/);
  assert.match(move,/reconciliarEtapaNota\(/);
  assert.match(move,/laneVisible\[String\(id\)\]/);
  assert.match(api, /\$f\['status'\] === 'Todos' && ! \$request->boolean\('incluir_canceladas'\)/);
});
