import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
const vue = readFileSync('resources/js/notas/vue/NotasApp.vue', 'utf8');
test('detalhes mostram caminho para operacoes legadas sem substituir regras', () => {
  assert.match(vue, /Abrir atendimento completo/);
  assert.match(vue, /detalhes\.urls\.show_legado/);
  assert.match(vue, /Anexos e operações avançadas/);
  assert.match(vue, /Formulário completo/);
});
test('estoque e parcelas existentes aparecem como leitura no detalhe Vue', () => {
  assert.match(vue, /i\.estoque_atual/);
  assert.match(vue, /detalhes\.conta_receber\?\.parcelas/);
  assert.match(vue, /Consulta somente leitura/);
});
