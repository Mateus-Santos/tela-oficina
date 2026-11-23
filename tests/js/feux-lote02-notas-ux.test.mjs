import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';

const base='0f21990d98eb73fb2808e26539339894a85e5f9d';
const dir='resources/views/notas_item/';
const read = p => readFileSync(p, 'utf8');
const before = p => execFileSync('git',['show',`${base}:${p}`],{encoding:'utf8'});
const names = s => [...s.matchAll(/\bname="([^"\n]+)"/g)].map(m=>m[1]).sort();
const routes = s => [...s.matchAll(/route\(\s*'([^']+)'/g)].map(m=>m[1]).sort();

for (const p of ['listar_notas_itens.blade.php','show_notas_itens.blade.php','cadastro_notas_itens.blade.php','editar_notas_itens.blade.php','_form.blade.php']) {
    test(`Notas: preservar campos e chamadas existentes em ${p}`, () => {
        const path=dir+p;
        assert.deepEqual(names(read(path)), names(before(path)));
        const wire = s => [...s.matchAll(/<livewire:[^>]+>/gs)].map(m=>m[0]);
        assert.deepEqual(wire(read(path)),wire(before(path)));
        // Nenhuma rota antiga pode ser removida. Formularios recebem apenas link de retorno.
        for (const route of new Set(routes(before(path)))) assert.ok(routes(read(path)).includes(route),route);
    });
}

test('Notas: lista preserva um unico card por registro, filtros e PDFs', () => {
    const s=read(dir+'listar_notas_itens.blade.php');
    assert.match(s,/feux-notas-lista/);
    assert.match(s, /id="feux-notas-resultados"/);
    assert.equal((s.match(/@foreach\(\$notas as \$nota\)/g)||[]).length,1);
    assert.equal((s.match(/'status-nota-selector'/g)||[]).length,1);
    assert.equal((s.match(/'etapa-nota-selector'/g)||[]).length,1);
    for (const route of ['notas.pdf','notas.pdf.download','notas.pdf.interno.download','notas.show','notas.destroy']) assert.ok(s.includes(route));
});

test('Notas: navegacao direta nas quatro secoes e form nativo intacto', () => {
    const form=read(dir+'_form.blade.php');
    for (const id of ['identificacao','resumo','itens','descontos','salvar']) assert.ok(form.includes(`id="feux-notas-${id}"`),id);
    for (const p of ['cadastro_notas_itens.blade.php','editar_notas_itens.blade.php']) {
        const s=read(dir+p);
        assert.ok(s.includes('feux-notas-formulario'));
        assert.match(s,/id="form-os-itens"/);
        assert.match(s,/@csrf/);
        assert.match(s,/feux-notas-lote02\.js/);
    }
    assert.match(read(dir+'editar_notas_itens.blade.php'),/@method\('PUT'\)/);
});

test('Notas: detalhe tem ancora de itens e preserva fluxos de status, cancelar e finalizar', () => {
    const s=read(dir+'show_notas_itens.blade.php');
    assert.ok(s.includes('feux-notas-detalhe'));
    assert.match(s,/id="feux-notas-itens"/);
    for (const needle of ['notas.cancelar','notasitem.edit','modalFinalizarNota','notas.pdf']) assert.ok(s.includes(needle), needle);
});

test('Notas: CSS e JS isolados; sem novos endpoints ou persistencia', () => {
    const style=read('resources/scss/_feux_notas_lote02.scss');
    const app=read('resources/scss/_app.scss');
    const js=read('resources/js/notaitem/feux-notas-lote02.js');
    assert.match(style,/\.cadastro\.feux-notas-lote02/);
    assert.match(app,/_feux_notas_lote02/);
    assert.match(js,/MutationObserver/);
    assert.doesNotMatch(js,/fetch\(|localStorage|sessionStorage|preventDefault\(|form\.submit\(/);
    assert.doesNotMatch(style,/https?:\/\//);
});

// Contrato FEUX LOTE01: a classe feux-moderno deve fechar a lista de classes.
test('Notas: compatibilidade com seletor do LOTE01', () => {
    for (const nome of ['listar_notas_itens','cadastro_notas_itens','editar_notas_itens']) {
        const html=read(dir+`${nome}.blade.php`);
        assert.match(html,/class="container cadastro[^"\n]* feux-moderno"/,nome);
    }
});
