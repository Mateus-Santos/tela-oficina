import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { runInNewContext } from 'node:vm';

const base = '62a35fc550a1ece423a26b8c47e80ee9be22ba87';
const read = p => readFileSync(p, 'utf8');
const original = p => execFileSync('git', ['show', `${base}:${p}`], { encoding: 'utf8' });
const root='resources/views/notas_item/';
const paths=['cadastro_notas_itens.blade.php','editar_notas_itens.blade.php'];
const tags = (s, reg) => [...s.matchAll(reg)].map(m => m[0]).sort();

test('L06: erros do Laravel visiveis nas duas telas com links de secao', () => {
    for (const p of paths) {
        const s = read(root+p);
        assert.match(s, /class="feux-notas-avisos"/);
        assert.match(s, /\$errors->getMessages\(\)/);
        assert.match(s, /href="#feux-notas-\{\{ \$feuxSecao \}\}"/);
        assert.match(s, /role="alert" tabindex="-1"/);
        assert.equal((s.match(/class="feux-notas-avisos"/g)||[]).length,1);
        assert.equal((s.match(/class="feux-notas-envio-status visually-hidden"/g)||[]).length,1);
        assert.match(s, /feux-notas-lote06\.js/);
    }
    assert.doesNotMatch(read(root+'editar_notas_itens.blade.php'), /<div class="alert alert-danger">/);
});

test('L06: preservar campos, rotas, Livewire e metodos HTTP anteriores', () => {
    for (const p of paths) {
        const now=read(root+p); const before=original(root+p);
        for(const pattern of [/\bname="[^"]*"/g, /route\(\s*'[^']+'/g, /<livewire:[^>]*>/gs, /@method\([^)]*\)/g]) {
            assert.deepEqual(tags(now,pattern),tags(before,pattern), `${p}: contrato alterado`);
        }
        assert.equal(now.includes('@csrf'),before.includes('@csrf'));
        assert.equal(tags(now, /@vite\(/g).length,tags(before, /@vite\(/g).length);
        // L04 e L05 exigem os mesmos IDs/atributos de controle.
        // O L06 adiciona classes novas sem modificar esse contrato.
        const attrContrato = /\b(?:id|name|action|method|data-bs-target|data-bs-toggle|type)=(?:"[^"]*"|'[^']*')/g;
        assert.deepEqual(tags(now, attrContrato), tags(before, attrContrato), `${p}: contrato L04/L05 de atributos alterado`);
    }
});

test('L06: estilos escopados ao formulario, responsividade e reducao de movimento', () => {
    const s=read('resources/scss/_feux_notas_lote06.scss');
    assert.match(read('resources/scss/_app.scss'), /@use '\.\/_feux_notas_lote06\.scss';/);
    assert.match(s,/\.cadastro\.feux-notas-lote02\.feux-notas-formulario/);
    assert.match(s,/position: sticky/);
    assert.match(s,/prefers-reduced-motion/);
    assert.match(s,/:focus-visible/);
    assert.doesNotMatch(s,/https?:\/\//);
});

test('L06: indicador de envio nao altera submit, bloqueio ou persistencia', () => {
    const listeners={};
    const form={dataset:{},checkValidity(){return true;},addEventListener(k,fn){listeners[`form:${k}`]=fn;}};
    const attributes={};
    const button={setAttribute(k,v){attributes[k]=v;},removeAttribute(k){delete attributes[k];}};
    const status={textContent:''};
    const doc={readyState:'complete',getElementById(k){return ({'form-os-itens':form,'btn-salvar-nota':button})[k]||null;},querySelector(k){assert.equal(k,'.feux-notas-envio-status');return status;}};
    const win={addEventListener(k,fn){listeners[`window:${k}`]=fn;}};
    const source=read('resources/js/notaitem/feux-notas-lote06.js');
    assert.doesNotMatch(source, /\b(fetch|XMLHttpRequest|preventDefault|localStorage|sessionStorage)\b|\.submit\(|\.remove\(/);
    runInNewContext(source,{document:doc,window:win});
    assert.equal(form.dataset.feuxNotasLote06,'1');
    listeners['form:submit']();
    assert.equal(form.dataset.feuxEnviando,'1');
    assert.equal(attributes['aria-busy'],'true');
    assert.match(status.textContent,/Enviando/);
    listeners['window:pageshow']();
    assert.equal(form.dataset.feuxEnviando,undefined);
    assert.equal(attributes['aria-busy'],undefined);
    assert.equal(status.textContent,'');
});
