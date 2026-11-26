import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { reconciliarEtapaNota } from '../../resources/js/notas/vue/reconciliarEtapaNota.js';
const app=readFileSync('resources/js/notas/vue/NotasApp.vue','utf8');
const card=readFileSync('resources/js/notas/vue/components/NotaCard.vue','utf8');
const css=readFileSync('resources/css/notas/vue.css','utf8');
const etapas=[{id:1,nome:'Orçamento',tipo:'inicial'}, {id:2,nome:'Diagnóstico',tipo:'intermediaria'}, {id:3,nome:'Entrega',tipo:'final'}];
function dados(){return {notas:[{id:69,etapa_id:1,etapa:'Orçamento',status:'Aberto'},{id:70,etapa_id:2,etapa:'Diagnóstico',status:'Aberto'}],etapas,metricas:{etapa_final:0,notas:2,clientes:2}};}
test('movimento atualiza apenas a nota desejada, conservando referencia dos outros cartoes',()=>{
 const d=dados(), untouched=d.notas[1];
 assert.deepEqual(reconciliarEtapaNota({...d,notaId:69,novaEtapaId:2}),{atualizada:true,revalidar:false});
 assert.equal(d.notas[0].etapa_id,2);assert.equal(d.notas[0].etapa,'Diagnóstico');
 assert.strictEqual(d.notas[1],untouched);assert.equal(d.metricas.notas,2);
});
test('indicador de etapa final incrementa e decrementa apenas em notas abertas',()=>{
 const d=dados();
 reconciliarEtapaNota({...d,notaId:69,novaEtapaId:3});assert.equal(d.metricas.etapa_final,1);
 reconciliarEtapaNota({...d,notaId:69,novaEtapaId:2});assert.equal(d.metricas.etapa_final,0);
 d.notas[0].status='Finalizado';reconciliarEtapaNota({...d,notaId:69,novaEtapaId:3});assert.equal(d.metricas.etapa_final,0);
});
test('filtro de etapa solicita reconciliacao silenciosa, sem estimar indicadores',()=>{
 const d=dados();const r=reconciliarEtapaNota({...d,notaId:69,novaEtapaId:2,filtroEtapaId:'1'});
 assert.equal(r.revalidar,true);assert.equal(d.metricas.etapa_final,0);
});
test('etapa ou nota inexistente nao altera o quadro',()=>{
 const d=dados();assert.equal(reconciliarEtapaNota({...d,notaId:69,novaEtapaId:999}).atualizada,false);
 assert.equal(reconciliarEtapaNota({...d,notaId:999,novaEtapaId:2}).atualizada,false);
 assert.equal(d.notas[0].etapa_id,1);
});
test('UI nao executa loadBoard completo ao mover e preserva foco e dialogo',()=>{
 const move=app.split('async function moveNote(n, id) {')[1].split('\nfunction dropNote')[0];
 assert.ok(move);assert.doesNotMatch(move,/await loadBoard\(true\);/);
 assert.match(move,/reconciliarEtapaNota\(/);
 assert.match(move,/loadBoard\(true, \{ silent: true \}\)/);
 assert.match(move,/restoreSelectFocus/);
 assert.match(move,/generation !== boardGeneration/);
 assert.match(move,/detalhes\.value\.etapa = destino\.nome/);
 assert.match(card,/:data-note-id="nota.id"/);
 assert.match(app,/moveFeedback/);
});
test('todas as cores de texto usam tokens de papel semantico, com contraste nos titulos',()=>{
 const withoutTokens=css.replace(/--sos-text-[\w-]+\s*:\s*#[0-9a-fA-F]+\s*;/g,'');
 assert.doesNotMatch(withoutTokens,/(?<![\w-])color\s*:\s*#[0-9a-fA-F]+/);
 for(const role of ['primary','heading','card-heading','stage-heading','secondary','muted','link','status-open','status-done','status-cancelled','success','danger']) {
  assert.ok(css.includes(`--sos-text-${role}:`),role);
 }
 assert.match(css,/\.sos-lane__head h2\s*\{\s*color:\s*var\(--sos-text-stage-heading\)\s*!important/);
 assert.match(css,/\.sos-note h3\s*\{\s*color:\s*var\(--sos-text-card-heading\)\s*!important/);
});
