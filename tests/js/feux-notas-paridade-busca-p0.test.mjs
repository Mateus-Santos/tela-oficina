import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {ehReferenciaCatalogo, podeSelecionarProduto, precoProduto, statusEstoqueProduto, urlInternaSegura} from '../../resources/js/notas/vue/buscaProdutosParidade.mjs';
const read = p => readFileSync(p, 'utf8');
const legado = read('app/Http/Controllers/Api/NotaItemBuscaController.php');
const vue = read('resources/js/notas/vue/components/NotaForm.vue');
test('P0 usa busca legada oficial autenticada com marca, imagem, codigos e disponibilidade', () => {
    const blade=read('resources/views/notas_item/vue/index.blade.php');
    const app=read('resources/js/notas/vue/NotasApp.vue');
    assert.match(blade, /api\.notas-itens\.buscar/);
    assert.match(app, /:busca-itens-url="props\.buscaItensUrl"/);
    for (const field of ['imagem_url','marca','codigo','codigo_fabricante','codigo_barras','estoque_atual','estoque_minimo']) {
        assert.ok(legado.includes(`'${field}'`), field);
        assert.ok(vue.includes(`item.${field}`), field);
    }
    assert.ok(legado.includes("'preco'"));
    assert.match(vue, /precoProduto\(item\)/);
    assert.match(legado,/orWhereHas\('marcaRelacionada'/);
    assert.match(vue,/fetchProdutosRicos\(q, controller.signal\)/);
});
test('P0 catalogo nao recebe id operacional e pode ser encaminhado ao cadastro', () => {
    const ref={id:null, catalogo_id:112, tipo_resultado:'referencia_catalogo', selecionavel_em_nota:false, preco:89};
    assert.equal(ehReferenciaCatalogo(ref),true);
    assert.equal(podeSelecionarProduto(ref),false);
    assert.equal(podeSelecionarProduto({id:112}),true);
    assert.equal(podeSelecionarProduto({id:0}),false);
    assert.match(statusEstoqueProduto(ref),/Referência/);
    assert.equal(urlInternaSegura('/catalogo/112','https://oficina.local'),'https://oficina.local/catalogo/112');
    assert.equal(urlInternaSegura('javascript:alert(1)','https://oficina.local'),'');
    assert.equal(urlInternaSegura('https://externo.test','https://oficina.local'),'');
});
test('P0 status de estoque e preco nao inventa disponibilidade', () => {
    assert.match(statusEstoqueProduto({estoque_atual:0, estoque_minimo:2}),/Sem estoque/);
    assert.match(statusEstoqueProduto({estoque_atual:1, estoque_minimo:2}),/baixo/);
    assert.match(statusEstoqueProduto({estoque_atual:5, estoque_minimo:2}),/disponível/);
    assert.equal(precoProduto({preco_jaime:10.2,preco:9}),10.2);
    assert.equal(precoProduto({preco:null}),null);
});
test('P0 protecao contra respostas obsoletas, sessao expirada e erro de busca', () => {
    for(const x of ['AbortController','itemToken','AbortError','401','419','403','Nenhum resultado encontrado','buscaItensErro']) {
        assert.ok(vue.includes(x),x);
    }
    assert.match(vue,/podeSelecionarProduto\(item\)/);
});
test('P1 formulario expõe garantia, desconto total, diferença KM e preserva payload original', () => {
    for(const x of ['v-model.number="i.garantia_dias"','v-model.number="diferencaTrocaOleo"','Descontos dos itens','garantia_dias: i.garantia_dias','km_proxima_troca_oleo']) assert.ok(vue.includes(x),x);
    const request = read('app/Http/Requests/Notas/StoreNotaRequest.php');
    for(const x of ["'itens.*.garantia_dias'", "'km_proxima_troca_oleo'", "'itens.*.desconto'"]) assert.ok(request.includes(x),x);
    assert.doesNotMatch(vue, /desconto_global:/);
});
