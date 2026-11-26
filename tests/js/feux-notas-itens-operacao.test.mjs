import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { runInNewContext } from 'node:vm';

const read = path => readFileSync(path, 'utf8');
const source = read('resources/js/notaitem/feux-notas-itens-operacao.js');
const base = '06e9688fd49072f28120530814fc8b1bb20d08a7';
const old = path => execFileSync('git', ['show', `${base}:${path}`], { encoding: 'utf8' });
const attrs = text => [...text.matchAll(/\b(?:id|name|action|method|data-bs-target|data-bs-toggle|type)=(?:"[^"]*"|'[^']*')/g)].map(x=>x[0]).sort();

test('L02: telas preservam os atributos existentes e entrada de Vite', () => {
    for (const f of ['cadastro_notas_itens.blade.php','editar_notas_itens.blade.php']) {
        const p = `resources/views/notas_item/${f}`;
        assert.deepEqual(attrs(read(p)), attrs(old(p)));
        assert.equal((read(p).match(/@vite\(/g)||[]).length, (old(p).match(/@vite\(/g)||[]).length);
        assert.match(read(p), /feux-notas-itens-operacao\.js/);
    }
});

test('L02: Vite e SCSS registram a nova interface, isolada', () => {
    assert.match(read('vite.config.js'), /resources\/js\/notaitem\/feux-notas-itens-operacao\.js/);
    assert.match(read('resources/scss/_app.scss'), /@use '\.\/_feux_notas_itens_operacao\.scss';/);
    const css = read('resources/scss/_feux_notas_itens_operacao.scss');
    assert.match(css, /^\.cadastro\.feux-notas-lote02\.feux-workspace-redesign\.feux-notas-formulario \{/m);
    assert.match(css, /tr\[hidden\]\s*\{\s*display:\s*none\s*!important/);
    assert.match(css, /max-width: 575\.98px/);
    assert.match(css, /focus-visible/);
});

test('L02: exclusivamente filtro visual, sem alteracao financeira ou persistencia', () => {
    assert.doesNotMatch(source, /fetch\s*\(|XMLHttpRequest|\.submit\s*\(|formData|localStorage|sessionStorage|\.remove\s*\(|\.appendChild\s*\(\s*linha/);
    assert.match(source, /linha\.hidden\s*=/);
    assert.match(source, /MutationObserver/);
    assert.match(source, /data-feux-tipo/);
});

test('L02: busca, abas, filtro sem remover itens e foco no primeiro resultado', () => {
    const listeners = {};
    const busca={value:'', addEventListener(t,fn){listeners[`busca:${t}`]=fn;}};
    const botoes=['todos','produto','os'].map(nome=>({dataset:{feuxTipo:nome}, attrs:{},
        setAttribute(k,v){this.attrs[k]=v;}, addEventListener(t,fn){listeners[`tipo:${nome}:${t}`]=fn;}}));
    const total={textContent:''};
    const status={textContent:''};
    const editar={disabled:false, addEventListener(t,fn){listeners[`editar:${t}`]=fn;}};
    const els={'[data-feux-busca-itens]':busca,'[data-feux-total-itens]':total,'[data-feux-status-itens]':status,'[data-feux-editar-primeiro]':editar};
    const barra={className:'',innerHTML:'',setAttribute(){},querySelector(s){return els[s];},querySelectorAll(s){assert.equal(s,'[data-feux-tipo]');return botoes;}};
    const campoA={value:'Amortecedor dianteiro',focus(){listeners.foco='A';}};
    const campoB={value:'Alinhamento',focus(){listeners.foco='B';}};
    const linha=(tipo,campo,codigo)=>({hidden:false,textContent:codigo,dataset:{itemableType:tipo},
        querySelector(s){if(s==='input.itemable-type')return null;return campo;},
        querySelectorAll(){return [campo];}});
    const linhas=[linha('App\\Models\\Produto',campoA,'ABC1'),linha('App\\Models\\OrdemServico',campoB,'OS 12')];
    const corpo={querySelectorAll(s){assert.equal(s,'tr[data-item-index]');return linhas;}};
    const destino={insertBefore(n,w){assert.equal(n,barra);assert.equal(w,wrapper);}};
    const wrapper={parentElement:destino};
    const tabela={closest(s){assert.equal(s,'.feux-notas-itens-mobile');return wrapper;}};
    const nodes={'#tabela-itens-os':tabela,'#container-itens-dinamicos':corpo};
    const tela={dataset:{},querySelector(s){return nodes[s];}};
    const document={readyState:'complete',querySelector(s){assert.equal(s,'.feux-workspace-redesign');return tela;},createElement(s){assert.equal(s,'section');return barra;}};
    const Obs=function(cb){this.observe=function(){}; listeners.observar=cb;};
    runInNewContext(source,{document,MutationObserver:Obs});
    assert.equal(total.textContent,'2');
    assert.equal(status.textContent,'2 de 2 itens visíveis');
    listeners['tipo:produto:click']();
    assert.equal(linhas[0].hidden,false);
    assert.equal(linhas[1].hidden,true);
    assert.equal(status.textContent,'1 de 2 itens visíveis');
    listeners['editar:click']();assert.equal(listeners.foco,'A');
    busca.value='sem resultado';listeners['busca:input']();
    assert.equal(editar.disabled,true);
    assert.equal(linhas[0].hidden,true);
    assert.equal(linhas[1].hidden,true);
    assert.ok(status.textContent.includes('nenhum item corresponde'));
    busca.value='';listeners['tipo:todos:click']();
    assert.equal(linhas.filter(x=>!x.hidden).length,2);
    linhas.push(linha('App\\Models\\OrdemServico',{value:'Troca filtro'},'OS 13'));
    busca.value='abcdef';listeners['busca:input']();
    listeners.observar();
    assert.equal(busca.value,'');
    assert.equal(total.textContent,'3');
    assert.equal(linhas.filter(x=>!x.hidden).length,3);
});
