import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { validarArquivoNota, urlAnexoNota, formatarDataNota } from '../../resources/js/notas/vue/validarAnexoNota.mjs';
const read = p => readFileSync(p, 'utf8');
const app = read('resources/js/notas/vue/NotasApp.vue');
const form = read('resources/js/notas/vue/components/NotaForm.vue');
const upload = read('resources/js/notas/vue/components/NotaAnexoForm.vue');
const shell = read('resources/views/notas_item/vue/index.blade.php');

test('uploads respeitam extensoes, tamanho e rota oficial', () => {
  assert.equal(validarArquivoNota({ name:'imagem.webp', size:1024 }), '');
  assert.equal(validarArquivoNota({ name:'nota.xml', size:2097152 }), '');
  assert.match(validarArquivoNota({ name:'arquivo.exe', size:1024 }), /Formato/);
  assert.match(validarArquivoNota({ name:'nota.pdf', size:2097153 }), /2 MiB/);
  assert.match(validarArquivoNota({ name:'nota.pdf', size:0 }), /vazio/);
  assert.equal(urlAnexoNota('/notas/__NOTA_ID__/anexos', 12), '/notas/12/anexos');
  assert.throws(() => urlAnexoNota('/notas/__NOTA_ID__/anexos', '../x'));
  assert.throws(() => urlAnexoNota('/notas/12/anexos', 12));
});
test('datas nao deslocam vencimento pelo fuso UTC', () => {
  assert.equal(formatarDataNota('2026-11-27'), '27/11/2026');
  assert.equal(formatarDataNota('2026-11-27T00:00:00Z'), '27/11/2026');
  assert.equal(formatarDataNota(null), 'Não informado');
  assert.equal(formatarDataNota('2026-02-30'), '2026-02-30');
});
test('anexo usa request oficial sem inventar API ou prometer sucesso do backend', () => {
  assert.match(shell, /route\('notas\.anexos\.store'/);
  assert.match(upload, /method="POST"/);
  assert.match(upload, /enctype="multipart\/form-data"/);
  assert.match(upload, /target="_blank"/);
  for (const p of ['name="arquivo"','name="tipo"','name="observacoes"','name="_token"']) assert.ok(upload.includes(p),p);
  assert.doesNotMatch(upload, /fetch\s*\(/);
  assert.match(upload, /permitido && action/);
});
test('formulario permite criar cadastros oficiais sem perder rascunho', () => {
  for(const route of ['clientes.create','veiculosclientes.create','produtos.create']) assert.ok(shell.includes(route));
  assert.match(form, /Cadastros rápidos oficiais/);
  assert.match(form, /target="_blank" rel="noopener noreferrer"/);
  assert.match(form, /Atualizar veículos/);
  assert.match(form, /emit\('sujo', true\)/);
  assert.match(app, /Descartar as alterações não salvas/);
});
test('financeiro e estoque mantem backend como fonte de verdade', () => {
  assert.match(app,/detalhes\.conta_receber\.valor_original/);
  assert.match(app,/detalhes\.conta_receber\.recebido/);
  assert.match(app,/detalhes\.urls\.conta/);
  assert.match(app,/i\.estoque_atual/);
  assert.match(app,/Anexos e operações avançadas/);
});
