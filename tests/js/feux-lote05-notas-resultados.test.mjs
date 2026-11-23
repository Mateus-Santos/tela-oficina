import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { runInNewContext } from 'node:vm';

const base = '67c99b096dadc142b6e8cabab09287db9eed7eb0';
const pathModal = 'resources/views/notas_item/modal/_modal_adicionar_item.blade.php';
const pages = ['resources/views/notas_item/cadastro_notas_itens.blade.php','resources/views/notas_item/editar_notas_itens.blade.php'];
const read = path => readFileSync(path, 'utf8');
const before = path => execFileSync('git', ['show', `${base}:${path}`], { encoding: 'utf8' });
const attrs = s => [...s.matchAll(/\b(?:id|name|action|method|data-bs-target|data-bs-toggle|type)=(?:"[^"]*"|'[^']*')/g)].map(x=>x[0]).sort();
const routes = s => [...s.matchAll(/route\(\s*'([^']+)'/g)].map(x=>x[1]).sort();

test('L05: manter nomes, IDs, submit e rotas do modal', () => {
    assert.deepEqual(attrs(read(pathModal)),attrs(before(pathModal)));
    assert.deepEqual(routes(read(pathModal)),routes(before(pathModal)));
    for (const id of ['builder_item_busca','builder_resultados','builder_type']) {
        assert.equal((read(pathModal).match(new RegExp(`id="${id}"`, 'g'))||[]).length,1);
    }
    assert.match(read(pathModal), /feux-notas-resultados-lote05/);
});

test('L05: scripts novos só nas duas telas originais de formulário', () => {
    for (const path of pages) {
        assert.deepEqual(attrs(read(path)),attrs(before(path)));
        assert.deepEqual(routes(read(path)),routes(before(path)));
        assert.match(read(path), /feux-notas-lote04\.js/);
        assert.match(read(path), /feux-notas-lote05\.js/);
        assert.equal((read(path).match(/@vite\(/g)||[]).length,(before(path).match(/@vite\(/g)||[]).length);
    }
});

test('L05: CSS isolado no modal, sem afetar tabela ou estilos globais', () => {
    const css=read('resources/scss/_feux_notas_lote05.scss');
    assert.match(read('resources/scss/_app.scss'), /@use '\.\/_feux_notas_lote05\.scss';/);
    assert.match(css,/\.modal\.feux-notas-modal-itens/);
    assert.match(css,/data-feux-notas-tipo='os'/);
    assert.match(css,/:focus-visible/);
    assert.doesNotMatch(css,/(^|\n)\s*(body|table|button|\.modal)\s*\{/);
});

test('L05: orientação acompanha Produto/O.S. e preserva selecao atual', () => {
    const listener = {};
    const modal = {
        dataset:{},
        addEventListener(event,fn){listener[`modal:${event}`]=fn;},
        querySelector(selector){assert.equal(selector,'.feux-notas-tipo-contexto__texto');return label;},
    };
    const label={textContent:''};
    const seletor={value:'produto',addEventListener(event,fn){listener[`seletor:${event}`]=fn;}};
    const doc={readyState:'complete',getElementById(id){return ({modalAdicionarItem:modal,builder_type:seletor})[id]||null;}};
    const source=read('resources/js/notaitem/feux-notas-lote05.js');
    assert.doesNotMatch(source, /\b(fetch|XMLHttpRequest|submit|\.click\(|\.remove\()/);
    runInNewContext(source,{document:doc});
    assert.equal(modal.dataset.feuxNotasTipo,'produto');
    assert.match(label.textContent,/Produtos:/);
    seletor.value='os';listener['seletor:change']();
    assert.equal(modal.dataset.feuxNotasTipo,'os');
    assert.match(label.textContent,/Ordens de serviço:/);
    seletor.value='produto';listener['modal:shown.bs.modal']();
    assert.equal(modal.dataset.feuxNotasTipo,'produto');
});
