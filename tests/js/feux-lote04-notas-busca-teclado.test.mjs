import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { runInNewContext } from 'node:vm';

const base = '3f69477919e7798e69d117c1fbdf169e121e2bcf';
const files = [
    'resources/views/notas_item/modal/_modal_adicionar_item.blade.php',
    'resources/views/notas_item/cadastro_notas_itens.blade.php',
    'resources/views/notas_item/editar_notas_itens.blade.php',
];
const read = file => readFileSync(file, 'utf8');
const before = file => execFileSync('git', ['show', `${base}:${file}`], {encoding: 'utf8'});
const attrs = s => [...s.matchAll(/\b(?:id|name|action|method|data-bs-target|data-bs-toggle|type)=(?:"[^"]*"|'[^']*')/g)].map(m=>m[0]).sort();
const routes = s => [...s.matchAll(/route\(\s*'([^']+)'/g)].map(m=>m[1]).sort();

for (const path of files) {
    test(`Notas L04: preservar contratos em ${path}`, () => {
        assert.deepEqual(attrs(read(path)), attrs(before(path)));
        assert.deepEqual(routes(read(path)), routes(before(path)));
        assert.equal((read(path).match(/@vite\(/g)||[]).length,(before(path).match(/@vite\(/g)||[]).length);
    });
}

test('Notas L04: instruções e busca original acessíveis sem novos IDs', () => {
    const modal = read(files[0]);
    assert.match(modal, /aria-controls="builder_resultados"/);
    assert.match(modal, /class="feux-notas-busca-teclado/);
    for (const id of ['builder_item_busca','builder_resultados','builder_busca_status']) assert.equal((modal.match(new RegExp(`id="${id}"`,'g'))||[]).length,1);
    for (const path of files.slice(1)) assert.match(read(path), /feux-notas-lote04\.js/);
});

test('Notas L04: stylesheet exclusivamente local, sem estilos globais', () => {
    const s=read('resources/scss/_feux_notas_lote04.scss');
    assert.match(read('resources/scss/_app.scss'), /@use '\.\/_feux_notas_lote04\.scss';/);
    assert.match(s, /\.modal\.feux-notas-modal-itens/);
    assert.match(s, /focus-visible/);
    assert.doesNotMatch(s, /(^|\n)\s*(body|button|input|\.modal)\s*\{/);
});

test('Notas L04: ArrowDown / ArrowUp / Home / End navegam apenas os resultados', () => {
    const listeners = {};
    const state = { focused: '' };
    const makeButton = id => ({
        focus() { state.focused = id; },
        closest(s) { return s === 'button.list-group-item-action' ? this : null; },
    });
    const botoes = ['A','B','C'].map(makeButton);
    const busca = {
        disabled: false,
        focus() { state.focused = 'busca'; },
        addEventListener(type, cb) { listeners[`busca:${type}`] = cb; },
    };
    const resultados = {
        querySelectorAll() { return botoes; },
        contains(b) { return botoes.includes(b); },
        addEventListener(type, cb) { listeners[`resultados:${type}`] = cb; },
    };
    const modal = { dataset: {} };
    const elements = { modalAdicionarItem: modal, builder_item_busca: busca, builder_resultados: resultados };
    const doc = { readyState: 'complete', getElementById(id) { return elements[id] ?? null; } };
    runInNewContext(read('resources/js/notaitem/feux-notas-lote04.js'), { document: doc });
    assert.equal(modal.dataset.feuxNotasTeclado, '1');
    const event = (key, target = null) => ({key, target, prevented:false, preventDefault(){this.prevented=true;}});
    listeners['busca:keydown'](event('ArrowDown'));
    assert.equal(state.focused, 'A');
    listeners['resultados:keydown'](event('ArrowDown',botoes[0]));
    assert.equal(state.focused, 'B');
    listeners['resultados:keydown'](event('End',botoes[1]));
    assert.equal(state.focused, 'C');
    listeners['resultados:keydown'](event('Home',botoes[2]));
    assert.equal(state.focused, 'A');
    listeners['resultados:keydown'](event('ArrowUp',botoes[0]));
    assert.equal(state.focused, 'busca');
    const ignored = event('Enter',botoes[0]);
    listeners['resultados:keydown'](ignored);
    assert.equal(ignored.prevented,false, 'Enter precisa continuar ativando o botao nativo');
    const before = state.focused;
    listeners['busca:keydown'](event('Escape'));
    assert.equal(state.focused,before, 'Escape deve permanecer com o comportamento do Bootstrap');
});
