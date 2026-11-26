#!/usr/bin/env python3
"""L03: migrar Central FEUX para Vue e retirar somente arquivos experimentais substituidos."""
from pathlib import Path
import sys
root=Path(sys.argv[1]).resolve()
def replacement(filename,old,new):
    file=root/filename
    content=file.read_text()
    count=content.count(old)
    if count!=1:
        raise SystemExit('BLOQUEADO: ancora divergiu '+filename+': '+str(count))
    file.write_text(content.replace(old,new,1))
    print('PATCH_OK='+filename)

old=r"""        Route::get('/notas/painel', [\App\Http\Controllers\NotasWorkspaceController::class, 'index'])
            ->name('notas.workspace');
        Route::get('/notas/painel/{nota}', [\App\Http\Controllers\NotasWorkspaceController::class, 'show'])
            ->whereNumber('nota')->name('notas.workspace.show');
        Route::patch('/notas/painel/{nota}/etapa', [\App\Http\Controllers\NotasWorkspaceController::class, 'move'])
            ->whereNumber('nota')->name('notas.workspace.move');"""
new=r"""        // FEUX L03: Central Vue 3. Rotas autenticadas/admin do grupo existente.
        // Legacy Nota/OS permanece para PDF e contingencia ate aceite funcional.
        Route::get('/notas/painel', [\App\Http\Controllers\NotasVueController::class, 'shell'])
            ->name('notas.workspace');
        Route::get('/notas/painel/api/dados', [\App\Http\Controllers\NotasVueController::class, 'dados'])
            ->name('notas.vue.dados');
        Route::get('/notas/painel/api/opcoes', [\App\Http\Controllers\NotasVueController::class, 'opcoes'])
            ->name('notas.vue.opcoes');
        Route::get('/notas/painel/api/{nota}', [\App\Http\Controllers\NotasVueController::class, 'detalhe'])
            ->whereNumber('nota')->name('notas.vue.detalhe');
        Route::post('/notas/painel/api', [\App\Http\Controllers\NotasVueController::class, 'criar'])
            ->name('notas.vue.criar');
        Route::put('/notas/painel/api/{nota}', [\App\Http\Controllers\NotasVueController::class, 'editar'])
            ->whereNumber('nota')->name('notas.vue.editar');
        Route::patch('/notas/painel/api/{nota}/etapa', [\App\Http\Controllers\NotasVueController::class, 'mover'])
            ->whereNumber('nota')->name('notas.workspace.move');
        Route::post('/notas/painel/api/{nota}/finalizar', [\App\Http\Controllers\NotasVueController::class, 'finalizar'])
            ->whereNumber('nota')->name('notas.vue.finalizar');
        Route::post('/notas/painel/api/{nota}/cancelar', [\App\Http\Controllers\NotasVueController::class, 'cancelar'])
            ->whereNumber('nota')->name('notas.vue.cancelar');
        Route::delete('/notas/painel/api/{nota}', [\App\Http\Controllers\NotasVueController::class, 'excluir'])
            ->whereNumber('nota')->name('notas.vue.excluir');"""
replacement('routes/web.php',old,new)

replacement('vite.config.js',"import laravel from 'laravel-vite-plugin';", "import laravel from 'laravel-vite-plugin';\nimport vue from '@vitejs/plugin-vue';")
replacement('vite.config.js',"    plugins: [\n        laravel({", "    plugins: [\n        vue(),\n        laravel({")
replacement('vite.config.js',"                'resources/css/notas/workspace.css',\n                'resources/js/notas/workspace.js',", "                'resources/css/notas/vue.css',\n                'resources/js/notas/vue/main.js',")

# Somente o workspace experimental L01 foi inteiramente substituido. O restante
# do modulo (PDF, financeiro, portal, formularios antigos) permanece funcional.
obsolete=[
 'app/Http/Controllers/NotasWorkspaceController.php',
 'resources/views/notas_item/workspace/index.blade.php',
 'resources/views/notas_item/workspace/card.blade.php',
 'resources/views/notas_item/workspace/show.blade.php',
 'resources/css/notas/workspace.css',
 'resources/js/notas/workspace.js',
 'resources/views/components/ui/filter-drawer.blade.php',
 'resources/views/components/ui/metric-card.blade.php',
 'tests/js/feux-notas-workspace-l01.test.mjs',
]
for name in obsolete:
    path=root/name
    if not path.is_file():
        raise SystemExit('BLOQUEADO: legado L01 esperado ausente '+name)
# Evita exclusao se componente for utilizado em outros modulos.
for component in ['x-ui.filter-drawer', 'x-ui.metric-card']:
    matches=[]
    for p in (root/'resources/views').rglob('*.blade.php'):
        if 'resources/views/notas_item/workspace/' in str(p.relative_to(root)):
            continue
        if component in p.read_text(errors='replace'):
            matches.append(str(p.relative_to(root)))
    if matches:
        raise SystemExit('BLOQUEADO: componente L01 ainda usado: '+component+': '+str(matches))
for name in obsolete:
    (root/name).unlink()
    print('LEGADO_EXPERIMENTAL_REMOVIDO='+name)
print('ROTA_CENTRAL_VUE=OK; LEGADO_OPERACIONAL=CONSERVADO')
