import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { milessimosPositivos, avaliarLinhaConferencia } from '../../resources/js/compras/conferencia-ux-v3.js';
const avaliar = (dados = {}) => avaliarLinhaConferencia({
  nf: '5.000', recebida: '5.000', avariada: '0', aceita: '', observacao: '', ...dados,
});
test('D02 preserva precisao de milésimos e rejeita valores fora do dominio', () => {
  assert.equal(milessimosPositivos('1.001'), 1001);
  assert.equal(milessimosPositivos('0'), 0);
  assert.equal(milessimosPositivos('999999999.999'), 999999999999);
  for (const v of ['-1', '1.0001', '2e3', '1,2', '1000000000', '']) assert.ok(Number.isNaN(milessimosPositivos(v)));
});
test('D02 nao insere excedente e limita aceite ao recebido sem avarias', () => {
  assert.equal(avaliar({ recebida:'7.000', observacao:'Excesso físico' }).aceite, 5000);
  assert.equal(avaliar({ recebida:'4.000', avariada:'1.000', observacao:'Falta e avaria' }).aceite, 3000);
  assert.equal(avaliar({ recebida:'4.000', avariada:'1.000', aceita:'4.000', observacao:'Falta e avaria' }).valido, false);
});
test('D02 aceita zero e exige motivo de divergencia', () => {
  assert.equal(avaliar({ recebida:'0', aceita:'0', observacao:'Peça ausente' }).valido, true);
  assert.equal(avaliar({ recebida:'0', aceita:'0', observacao:'não' }).valido, false);
  assert.equal(avaliar({ recebida:'5.000', avariada:'6.000', observacao:'Avaria incorreta' }).valido, false);
});
test('D02 distingue quantidade não informada de zero fisicamente contado', () => {
  assert.equal(avaliar({ recebida:'' }).valido, false);
  assert.equal(avaliar({ recebida:'5.000', avariada:'0', aceita:'' }).valido, true);
  assert.equal(avaliar({ recebida:'5.000', avariada:'0', aceita:'4.000' }).valido, false);
  assert.equal(avaliar({ recebida:'5.000', avariada:'0', aceita:'4.000', observacao:'Recusa parcial' }).valido, true);
});
test('D02 preserva formulario Laravel, nome dos campos, CSRF e envio nativo', () => {
  const view = readFileSync('resources/views/compra/show.blade.php','utf8');
  assert.match(view, /route\('compras\.conferir', \$compra\)/);
  for (const name of ['quantidade_conferida','quantidade_avariada','quantidade_aceita','observacao_divergencia'])
    assert.match(view, new RegExp(`name="itens\\[\\{\\{ \\$item->id \\}\\}\\]\\[${name}\\]"`));
  assert.match(view, /data-feux-conferencia-v3/);
  assert.match(view, /data-feux-quantidade-nf/);
  assert.match(view, /@csrf/);
  assert.match(view, /Salvar conferência/);
});
test('D02 integra somente na ficha de compra sem endpoints novos ou backend', () => {
  const entry=readFileSync('resources/js/compras/show.js','utf8');
  const style=readFileSync('resources/scss/_app.scss','utf8');
  const action=readFileSync('app/Actions/Compra/ConferirCompra.php','utf8');
  assert.match(entry,/inicializarConferenciaCompra\(\);/);
  assert.match(style,/_feux_compras_conferencia_v3\.scss/);
  assert.match(action,/DB::transaction/);
  assert.match(action,/Quantidade::milessimos/);
});
