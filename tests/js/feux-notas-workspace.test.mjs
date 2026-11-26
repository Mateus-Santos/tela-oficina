import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { runInNewContext } from 'node:vm';

const paths = ['cadastro_notas_itens.blade.php', 'editar_notas_itens.blade.php']
    .map(x => `resources/views/notas_item/${x}`);
const get = path => readFileSync(path, 'utf8');
const before = path => execFileSync('git', ['show', `origin/development:${path}`], { encoding: 'utf8' });
const contract = text => [...text.matchAll(/\b(?:id|name|action|method|data-bs-target|data-bs-toggle|type)=(?:"[^"]*"|'[^']*')/g)]
    .map(x => x[0]).sort();
const routes = text => [...text.matchAll(/route\(\s*'([^']+)'/g)].map(x => x[1]).sort();

for (const path of paths) {
    test(`Notas Workspace: contratos preservados em ${path}`, () => {
        const a = before(path), b = get(path);
        assert.deepEqual(contract(b), contract(a));
        assert.deepEqual(routes(b), routes(a));
        assert.equal((b.match(/@vite\(/g)||[]).length, (a.match(/@vite\(/g)||[]).length);
        assert.equal((b.match(/<form /g)||[]).length, (a.match(/<form /g)||[]).length);
        assert.match(b, /feux-workspace-redesign feux-moderno/);
        assert.match(b, /@include\('notas_item.partials._workspace_sidebar'\)/);
        assert.match(b, /feux-notas-workspace\.js/);
    });
}

test('Notas Workspace: painel é só informativo e acessa âncoras existentes', () => {
    const p = get('resources/views/notas_item/partials/_workspace_sidebar.blade.php');
    assert.match(p, /data-feux-workspace-items/);
    assert.match(p, /data-feux-workspace-total/);
    for (const id of ['identificacao', 'itens', 'descontos', 'salvar']) {
        assert.match(p, new RegExp(`href="#feux-notas-${id}"`));
    }
    assert.doesNotMatch(p, /<form|type="submit"|wire:click|route\(/);
});

test('Notas Workspace: CSS restrito ao novo contêiner com layout responsivo', () => {
    const css = get('resources/scss/_feux_notas_workspace.scss');
    assert.match(css, /^\.cadastro\.feux-notas-lote02\.feux-notas-formulario\.feux-workspace-redesign \{/m);
    assert.match(css, /grid-template-columns: minmax\(0, 1fr\) minmax\(18rem, 20rem\)/);
    assert.match(css, /position: sticky/);
    assert.match(css, /@media \(max-width: 575\.98px\)/);
    assert.match(css, /prefers-reduced-motion/);
    assert.match(get('resources/scss/_app.scss'), /@use '\.\/_feux_notas_workspace\.scss';/);
    assert.doesNotMatch(css, /^\s*(body|\.cadastro|\.modal)\s*\{/m);
});

test('Notas Workspace: entradas Vite incluem todos os scripts FEUX do modulo', () => {
    const config = get('vite.config.js');
    for (const s of ['lote02','lote04','lote05','lote06','workspace']) {
        const file = s === 'workspace' ? 'feux-notas-workspace.js' : `feux-notas-${s}.js`;
        assert.match(config, new RegExp(`resources/js/notaitem/${file.replaceAll('.', '\\.')}`));
    }
});

test('Notas Workspace: contagem, valor e contexto sem gravar dados', () => {
    const fields = { cliente_id: {value:'42'}, veiculo_cliente_id: {value:''} };
    const outputs = Object.fromEntries(['items','total','cliente','veiculo'].map(k=>[k,{textContent:''}]));
    const lines={quantity:2, querySelectorAll(sel){assert.equal(sel,'tr[data-item-index]');return Array(this.quantity).fill(0);}};
    const original={textContent:'R$ 150,00'};
    const form={querySelector(sel){const match=sel.match(/^input\[name="(.*?)"\]$/);return fields[match[1]];}};
    const nodes={ '#form-os-itens':form, '#container-itens-dinamicos':lines, '#valor-geral-os':original,
        ...Object.fromEntries(Object.entries(outputs).map(([k,v])=>[`[data-feux-workspace-${k}]`,v])) };
    const listeners={};
    const root={dataset:{},querySelector(sel){return nodes[sel]??null;},addEventListener(event,fn){listeners[event]=fn;}};
    const doc={readyState:'complete',querySelector(){return root;},addEventListener(){}};
    const code=get('resources/js/notaitem/feux-notas-workspace.js');
    assert.doesNotMatch(code, /\bfetch\b|XMLHttpRequest|\.submit\(|\.click\(|localStorage|sessionStorage|innerHTML|\.remove\(/);
    runInNewContext(code,{document:doc});
    assert.equal(outputs.items.textContent,'2 itens');
    assert.equal(outputs.total.textContent,'R$ 150,00');
    assert.equal(outputs.cliente.textContent,'Selecionado');
    assert.equal(outputs.veiculo.textContent,'Não informado');
    lines.quantity=1;original.textContent='R$ 75,00';fields.veiculo_cliente_id.value='9';
    listeners.change();
    assert.equal(outputs.items.textContent,'1 item');
    assert.equal(outputs.total.textContent,'R$ 75,00');
    assert.equal(outputs.veiculo.textContent,'Selecionado');
});
