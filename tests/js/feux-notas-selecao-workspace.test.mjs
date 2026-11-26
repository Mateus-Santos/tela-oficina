import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { runInNewContext } from 'node:vm';

const read = p => readFileSync(p, 'utf8');
const src = read('resources/js/notaitem/feux-notas-selecao-workspace.js');
const modal = 'resources/views/notas_item/modal/_modal_adicionar_item.blade.php';
const base = '44bf409846965cf557eec7e1cdbea47641d2645a';
const old = execFileSync('git',['show',`${base}:${modal}`], {encoding:'utf8'});
const attr = s => [...s.matchAll(/\b(?:id|name|action|method|data-bs-target|data-bs-toggle|type)=(?:"[^"]*"|'[^']*')/g)].map(x=>x[0]).sort();

// O modal original continua disponivel sem JS, e seus contratos sao identicos.
test('L03: formulario/modal e rotas permanecem intactos', () => {
    const now = read(modal);
    assert.deepEqual(attr(now),attr(old));
    assert.equal((now.match(/@vite\(/g)||[]).length,(old.match(/@vite\(/g)||[]).length);
    for (const id of ['builder_item_id','builder_type','builder_item_busca','builder_resultados','builder_descricao','builder_quantidade','builder_total','btn-adicionar-item']) {
        assert.equal((now.match(new RegExp(`id="${id}"`,'g'))||[]).length,1,id);
    }
    assert.match(now, /feux-notas-selecao-grid/);
    assert.match(now, /feux-notas-selecao-configurar/);
});

test('L03: estilos e Vite isolados no modal de Notas', () => {
    const css=read('resources/scss/_feux_notas_selecao_workspace.scss');
    assert.match(css, /^\.modal\.feux-notas-modal-itens\.feux-notas-selecao-workspace\s*\{/m);
    assert.match(css, /grid-template-columns:/);
    assert.match(css, /max-width: 575\.98px/);
    assert.match(css, /focus-visible/);
    assert.match(read('resources/scss/_app.scss'), /@use '\.\/_feux_notas_selecao_workspace\.scss';/);
    assert.match(read('vite.config.js'), /resources\/js\/notaitem\/feux-notas-selecao-workspace\.js/);
    assert.match(read(modal), /'resources\/js\/notaitem\/feux-notas-selecao-workspace\.js'/);
});

test('L03: nao faz chamada de rede, persistencia ou submit duplicado', () => {
    assert.doesNotMatch(src, /\bfetch\s*\(|XMLHttpRequest|localStorage|sessionStorage|\.submit\s*\(|\.remove\s*\(|FormData|preventDefault\s*\(/);
    assert.match(src, /tipo\.dispatchEvent\(new Event\('change'/);
    assert.match(src, /quantidade\.dispatchEvent\(new Event\('input'/);
});

test('L03: selecao de tipo usa evento original e mantem a quantidade minima', () => {
    const listeners={};
    const create=(props={})=>({dataset:props.dataset||{},value:props.value??'',textContent:'',attrs:{},
      classList:{add(){}},setAttribute(k,v){this.attrs[k]=v;},
      addEventListener(k,fn){(this.listeners??={})[k]=fn;},
      dispatchEvent(e){this.listeners?.[e.type]?.(e);(listeners.disparados??=[]).push(e.type);},
      focus(){listeners.foco=this},
      insertAdjacentElement(where,child){listeners.tipoInserido=[where,child]},
      querySelector(s){return this.nodes?.[s]||null;},
      querySelectorAll(s){return this.lists?.[s]||[]},
      closest(){return props.closest||null;}});
    const b1=create({dataset:{feuxModalTipo:'produto'}}),b2=create({dataset:{feuxModalTipo:'os'}});
    const qtdM=create({dataset:{feuxQtd:'-1'}}),qtdP=create({dataset:{feuxQtd:'1'}});
    const tipos=create();tipos.lists={'[data-feux-modal-tipo]':[b1,b2]};
    const acessos=create();acessos.lists={'[data-feux-qtd]':[qtdM,qtdP]};
    const texto=create();const resumo=create();resumo.nodes={'.feux-notas-selecao-status__texto':texto};
    const passo=create();passo.insertAdjacentElement=(where,el)=>{listeners.resumoInserido=[where,el]};
    const coluna=create();coluna.nodes={'.feux-notas-selecao-passo':passo};
    const tipo=create({value:'produto'}), busca=create(), result=create();
    const id=create(),descricao=create(),valor=create(),desconto=create(),total=create();
    const qtdBox=create();qtdBox.appendChild=(el)=>{listeners.qtdInserido=el};
    const quantidade=create({value:'1',closest:qtdBox});
    const m=create();m.nodes={'.feux-notas-selecao-configurar':coluna};
    const fields={modalAdicionarItem:m,builder_type:tipo,builder_item_busca:busca,builder_resultados:result,
       builder_item_id:id,builder_descricao:descricao,builder_quantidade:quantidade,
       builder_valor_unitario:valor,builder_desconto:desconto,builder_total:total};
    const document={readyState:'complete',activeElement:null,body:create(),getElementById(k){return fields[k]??null},
      createElement(k){assert.equal(k,'div'); return listeners.created++ === 0 ? tipos : listeners.created===2 ? resumo : acessos;}};
    listeners.created=0;
    const Event=function(type,init){this.type=type;this.bubbles=init?.bubbles||false;};
    runInNewContext(src,{document,Event,MutationObserver:undefined});
    assert.equal(m.dataset.feuxSelecaoWorkspace,'1');
    assert.equal(b1.attrs['aria-pressed'],'true');
    b2.listeners.click();
    assert.equal(tipo.value,'os');
    assert.deepEqual(listeners.disparados,['change']);
    assert.equal(b2.attrs['aria-pressed'],'true');
    assert.equal(listeners.foco,busca);
    qtdM.listeners.click(); assert.equal(quantidade.value,'1');
    qtdP.listeners.click(); assert.equal(quantidade.value,'2');
    assert.deepEqual(listeners.disparados,['change','input','input']);
    id.value='12';descricao.value='Filtro de óleo';
    descricao.listeners.input();
    assert.equal(m.dataset.feuxItemEscolhido,'1');
    assert.match(texto.textContent,/Filtro de óleo/);
    id.value='';descricao.listeners.input();
    assert.equal(m.dataset.feuxItemEscolhido,'0');
});
