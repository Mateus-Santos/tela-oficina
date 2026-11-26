import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
const read = p => readFileSync(p,'utf8');
const app=read('resources/js/notas/vue/NotasApp.vue');
const card=read('resources/js/notas/vue/components/NotaCard.vue');
const css=read('resources/css/notas/vue.css');
const api=read('app/Http/Controllers/NotasVueController.php');
const service=read('app/Services/Nota/MoverEtapaEmLote.php');
const action=read('app/Actions/Notas/AlterarEtapaNota.php');
const finalize=read('app/Actions/Notas/FinalizarNota.php');
const model=read('app/Models/Nota.php');
const routes=read('routes/web.php');
const audit=read('app/Console/Commands/AuditarEtapasFinalizadas.php');

test('drop na etapa final NAO executa PATCH simples, abre FinalizarNota e solicita dados financeiros',()=>{
 assert.match(app,/if \(target\.tipo === 'final'\) \{ await startFinalizationFromStage\(n\); return; \}/);
 assert.match(app,/await openFinalizar\(\);/);
 assert.match(app,/const r = await mutate\(`\$\{props\.api\}\/\$\{id\}\/finalizar`, 'POST', body\)/);
 assert.match(app,/Confirmar finalização real/);
 assert.match(finalize,/true \/\/ único acesso autorizado à etapa final/);
});
test('bypass do endpoint de mudar etapa final bloqueado no servidor',()=>{
 assert.match(action,/bool \$finalizando = false/);
 assert.match(action,/\$etapa->tipo === 'final' && ! \$finalizando/);
 assert.match(action,/throw new InvalidArgumentException/);
 assert.match(action,/\$finalizando/);
 assert.match(api,/function mover\(/);
});
test('modelo finalizado so aceita ultima etapa em alteracoes via Eloquent',()=>{
 assert.match(model,/static::saving/);
 assert.match(model,/\['Finalizado', 'Concluido'\]/);
 assert.match(model,/->whereKey\(\$nota->etapa_id\)->exists/);
 assert.match(audit,/notas:auditar-etapas-finalizadas/);
 assert.match(audit,/--aplicar/);
 assert.match(audit,/NotaEtapaHistorico::create/);
});
test('finalizados ganham tokens semanticos distintos dos cancelados e do tipo de etapa',()=>{
 for(const role of ['--sos-bg-card-finished','--sos-border-card-finished','--sos-text-card-finished','--sos-bg-tag-finished','--sos-text-tag-finished']) assert.ok(css.includes(role+':'));
 assert.match(card, /'sos-note--finished'/);
 assert.match(css, /\.sos-note\.sos-note--finished \{/);
 assert.match(app, /sos-detail__finished/);
});
test('area de drop estende até a maior coluna e evita dropfantasma',()=>{
 assert.match(css,/section\.sos-board \{ align-items:stretch !important; \}/);
 assert.match(css,/align-self:stretch !important; display:flex; flex-direction:column/);
 assert.match(css,/\.sos-board \.sos-lane__cards \{ flex:1 1 auto;/);
 assert.match(app, /@dragover="dragOverLane\(\$event,grupo.id\)"/);
 assert.match(app, /@dragend\.capture="dropTarget=''"/);
});
test('lote requer preview, assinatura, confirmacao e nao altera etapa final',()=>{
 assert.match(routes,/api\/etapas\/lote/);
 assert.match(api,/function previsualizarLote/);
 assert.match(api,/function moverLote/);
 assert.match(api,/MOVER_ETAPA_LOTE/);
 assert.match(app,/function previewBatch\(\)/);
 assert.match(app,/function executeBatch\(\)/);
 assert.match(service,/hash_equals/);
 assert.match(service,/DB::transaction/);
 assert.match(service,/\$destino->tipo === 'final'/);
 assert.match(service,/LIMITE = 100/);
});
test('modal evita blur de tela inteira e finalizacao conserva o quadro e o dialogo',()=>{
 assert.match(css,/\.sos-modal::backdrop \{ backdrop-filter:none !important/);
 const finish=app.split('async function finishNote() {')[1].split('function handlePopstate')[0];
 assert.match(finish,/loadBoard\(true, \{silent:true\}\)/);
 assert.doesNotMatch(finish,/await openNote\(id\)/);
});
