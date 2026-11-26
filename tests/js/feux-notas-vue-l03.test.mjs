import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync, existsSync } from 'node:fs';
const read = f => readFileSync(f, 'utf8');
const controller = read('app/Http/Controllers/NotasVueController.php');
const app = read('resources/js/notas/vue/NotasApp.vue');
const form = read('resources/js/notas/vue/components/NotaForm.vue');

test('Vue 3 monta na Central e nao recarrega a pagina', () => {
    assert.match(read('resources/js/notas/vue/main.js'), /createApp\(NotasApp/);
    assert.match(read('resources/views/notas_item/vue/index.blade.php'), /id="sos-notas-vue"/);
    assert.doesNotMatch(app, /location\.reload\s*\(/);
    assert.match(app, /history\.replaceState/);
    assert.match(app, /AbortController/);
});
test('API protegida por grupo administrativo e Action de Notas', () => {
    for (const token of ['StoreNotaRequest', 'UpdateNotaRequest', 'FinalizarNotaRequest',
        'CriarNota $acao', 'AtualizarNota $acao', 'FinalizarNota $acao', 'CancelarNota $acao',
        'AlterarEtapaNota $acao', 'DB::transaction']) assert.ok(controller.includes(token), token);
    const routes=read('routes/web.php');
    assert.match(routes,/NotasVueController::class/);
    assert.match(routes,/notas\.vue\.excluir/);
    assert.match(routes,/notas\.workspace/);
});
test('Kanban Vue possui arraste, toque e feedback verdadeiro', () => {
    const card = read('resources/js/notas/vue/components/NotaCard.vue');
    assert.match(card,/draggable/);
    assert.match(card,/dataTransfer/);
    assert.match(card,/@change="emit\('mover'/);
    assert.match(app,/@drop\.prevent/);
    assert.match(app,/notePending/);
    assert.match(app,/await mutate/);
});
test('Criacao e edicao de Notas via rascunhos de OS preservados', () => {
    assert.match(form,/os_nova_setor_servico_id/);
    assert.match(form,/const id = draft--/);
    assert.match(form,/tipoItem(?:\.value)?\s*===\s*'nova_os'/);
    assert.doesNotMatch(form, /fetch\(['"]\/api\/notas-itens\/ordens-servico/);
    assert.match(form,/emit\('salvar', payload\)/);
    assert.match(read('app/Services/Nota/OsRascunhoNaNota.php'), /DB::transactionLevel/);
});
test('O workspace puro L01 foi excluido e o legado operacional continua', () => {
    for (const f of ['resources/js/notas/workspace.js',
        'resources/views/notas_item/workspace/index.blade.php',
        'app/Http/Controllers/NotasWorkspaceController.php']) assert.equal(existsSync(f),false,f);
    assert.equal(existsSync('app/Http/Controllers/NotaController.php'),true);
    assert.equal(existsSync('app/Http/Controllers/NotasItemController.php'),true);
    assert.equal(existsSync('app/Actions/Notas/FinalizarNota.php'),true);
});
