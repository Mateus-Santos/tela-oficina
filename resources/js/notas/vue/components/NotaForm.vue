<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { ehReferenciaCatalogo, podeSelecionarProduto, precoProduto, statusEstoqueProduto, urlInternaSegura } from '../buscaProdutosParidade.mjs';
const props = defineProps({ nota: { type: Object, default: null }, api: { type: String, required: true }, loading: Boolean, serverError: { type: String, default: '' }, cadastros: { type: Object, default: () => ({}) }, buscaItensUrl: { type: String, default: '' } });
const emit = defineEmits(['salvar', 'cancelar', 'sujo']);
const OS = 'App\\Models\\OrdemServico';
const PRODUTO = 'App\\Models\\Produto';
const clientes = ref([]), veiculos = ref([]), setores = ref([]), resultados = ref([]);
const procurando = ref(false), buscaCliente = ref(''), buscaItem = ref('');
const buscaItensErro = ref(''), buscaItensFeita = ref(false);
const tipoItem = ref('produto'), quantidade = ref(1), valorItem = ref(0), descontoItem = ref(0);
const setorId = ref(''), descricaoOs = ref(''), erro = ref('');
const form = ref({ cliente_id: null, veiculo_cliente_id: null, km: null, km_proxima_troca_oleo: null, itens: [] });
const novoCliente = ref(null);
let clientTimer, itemTimer, clientToken = 0, itemToken = 0, vehicleToken = 0, draft = -1;
let itemAbort = null;
const money = n => Number(n ?? 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
const total = computed(() => form.value.itens.reduce((v, i) => v + Math.max(0, Number(i.quantidade) * Number(i.valor_unitario) - Number(i.desconto || 0)), 0));
const totalBruto = computed(() => form.value.itens.reduce((v, i) => v + Number(i.quantidade || 0) * Number(i.valor_unitario || 0), 0));
const descontos = computed(() => form.value.itens.reduce((v, i) => v + Number(i.desconto || 0), 0));
const isEdit = computed(() => !!props.nota?.id);
// Mesma regra do formulário legado: KM atual + distância = próxima troca.
const diferencaTrocaOleo = computed({
    get() {
        if (form.value.km === null || form.value.km === '' || form.value.km_proxima_troca_oleo === null || form.value.km_proxima_troca_oleo === '') return null;
        const atual = Number(form.value.km), proxima = Number(form.value.km_proxima_troca_oleo);
        return Number.isFinite(atual) && Number.isFinite(proxima) ? Math.max(0, proxima - atual) : null;
    },
    set(valor) {
        if (valor === null || valor === '' || form.value.km === null || form.value.km === '') {
            form.value.km_proxima_troca_oleo = null; return;
        }
        const distancia = Number(valor), atual = Number(form.value.km);
        if (Number.isSafeInteger(distancia) && distancia >= 0 && Number.isSafeInteger(atual) && atual >= 0) {
            form.value.km_proxima_troca_oleo = atual + distancia;
        }
    },
});
const urlReferencia = link => urlInternaSegura(link, window.location.origin);

function markDirty() { emit('sujo', true); }
function initialize() {
    clearTimeout(clientTimer);
    clearTimeout(itemTimer);
    itemAbort?.abort(); itemAbort = null;
    ++clientToken; ++itemToken; ++vehicleToken;
    clientes.value = []; veiculos.value = []; resultados.value = [];
    procurando.value = false; buscaItensErro.value = ''; buscaItensFeita.value = false;
    const n = props.nota;
    form.value = {
        cliente_id: n?.cliente_id || null, veiculo_cliente_id: n?.veiculo_cliente_id || null,
        km: n?.km ?? null, km_proxima_troca_oleo: n?.km_proxima_troca_oleo ?? null,
        itens: n?.itens?.map(x => ({ ...x, _key: `e-${x.id}` })) || [],
    };
    buscaCliente.value = n?.cliente_id ? (n.cliente || '') : '';
    novoCliente.value = n?.cliente_id ? { id: n.cliente_id, nome: n.cliente } : null;
    erro.value = '';
    if (n?.cliente_id) void carregarVeiculos();
}
watch(() => props.nota, initialize, { immediate: true });
onMounted(async () => { await carregarSetores(); });
onBeforeUnmount(() => { clearTimeout(clientTimer); clearTimeout(itemTimer); itemAbort?.abort(); ++clientToken; ++itemToken; ++vehicleToken; });
async function fetchOpts(tipo, params = {}) {
    const url = new URL(`${props.api}/opcoes`, window.location.origin);
    url.searchParams.set('tipo', tipo);
    Object.entries(params).forEach(([key, val]) => { if (val !== null && val !== '' && val !== undefined) url.searchParams.set(key, String(val)); });
    const r = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
    if (!r.ok) throw new Error('Não foi possível consultar '+tipo);
    return (await r.json()).data || [];
}
async function carregarSetores() { try { setores.value = await fetchOpts('setores'); } catch(e) { erro.value = e.message; } }
function procurarCliente() {
    clearTimeout(clientTimer);
    const generation = ++clientToken;
    ++vehicleToken;
    form.value.cliente_id = null;
    form.value.veiculo_cliente_id = null;
    novoCliente.value = null;
    veiculos.value = [];
    clientes.value = [];
    const q = buscaCliente.value.trim();
    if (q.length < 2) return;
    clientTimer = setTimeout(async () => {
        try {
            const data = await fetchOpts('clientes', { q });
            if (generation === clientToken) clientes.value = data;
        } catch (e) {
            if (generation === clientToken) erro.value = e.message;
        }
    }, 300);
}
function selecionarCliente(c) {
    clearTimeout(clientTimer);
    ++clientToken;
    markDirty();
    novoCliente.value = c; form.value.cliente_id = c.id;
    buscaCliente.value = c.nome; clientes.value = [];
    form.value.veiculo_cliente_id = null;
    void carregarVeiculos();
}
function limparCliente() {
    markDirty();
    clearTimeout(clientTimer);
    ++clientToken; ++vehicleToken;
    novoCliente.value = null; form.value.cliente_id = null;
    form.value.veiculo_cliente_id = null; buscaCliente.value = ''; veiculos.value = [];
}
async function carregarVeiculos() {
    const cliente = form.value.cliente_id;
    const generation = ++vehicleToken;
    if (!cliente) { veiculos.value = []; return; }
    try {
        const data = await fetchOpts('veiculos', { cliente_id: cliente });
        if (generation === vehicleToken && form.value.cliente_id === cliente) veiculos.value = data;
    } catch (e) {
        if (generation === vehicleToken) erro.value = e.message;
    }
}
function mudarTipoItem(tipo) {
    clearTimeout(itemTimer); itemAbort?.abort(); itemAbort = null;
    ++itemToken;
    tipoItem.value = tipo;
    resultados.value = [];
    procurando.value = false;
    buscaItensErro.value = ''; buscaItensFeita.value = false;
    buscaItem.value = '';
}
async function fetchProdutosRicos(q, signal) {
    if (!props.buscaItensUrl) throw new Error('Rota oficial de busca de produtos indisponível.');
    const url = new URL(props.buscaItensUrl, window.location.origin);
    if (url.origin !== window.location.origin) throw new Error('Rota de busca inválida.');
    url.searchParams.set('tipo', 'produto');
    url.searchParams.set('q', q);
    const resposta = await fetch(url, {credentials:'same-origin', headers:{Accept:'application/json'}, signal});
    if (resposta.status === 401 || resposta.status === 419 || (resposta.redirected && new URL(resposta.url).pathname.includes('/login')))
        throw new Error('Sessão expirada. Faça login novamente.');
    if (resposta.status === 403) throw new Error('Sem permissão para consultar produtos.');
    if (!resposta.ok) throw new Error(`Falha na busca de produtos (HTTP ${resposta.status}).`);
    if (!(resposta.headers.get('content-type') || '').includes('json')) throw new Error('Resposta de busca inválida. Verifique a sessão.');
    const body = await resposta.json();
    if (!Array.isArray(body?.data)) throw new Error('Resposta de produtos inválida.');
    return body.data;
}
function procurarItens() {
    clearTimeout(itemTimer); itemAbort?.abort(); itemAbort = null;
    const generation = ++itemToken;
    resultados.value = [];
    procurando.value = false;
    buscaItensFeita.value = false; buscaItensErro.value = '';
    if (tipoItem.value === 'nova_os') return;
    const q = buscaItem.value.trim();
    if (q.length < 2 && tipoItem.value !== 'os') return;
    const tipo = tipoItem.value === 'produto' ? 'produtos' : 'os';
    const cliente = form.value.cliente_id, veiculo = form.value.veiculo_cliente_id;
    itemTimer = setTimeout(async () => {
        if (generation !== itemToken) return;
        procurando.value = true;
        const controller = tipo === 'produtos' ? new AbortController() : null;
        itemAbort = controller;
        try {
            const data = tipo === 'produtos'
                ? await fetchProdutosRicos(q, controller.signal)
                : await fetchOpts(tipo, {q, cliente_id:cliente, veiculo_cliente_id:veiculo});
            if (generation === itemToken) { resultados.value = data; buscaItensFeita.value = true; }
        } catch (e) {
            if (generation === itemToken && e?.name !== 'AbortError') buscaItensErro.value = e.message || 'Erro na consulta.';
        } finally {
            if (generation === itemToken) { procurando.value = false; itemAbort = null; }
        }
    }, 250);
}
function selecionarItem(item) {
    clearTimeout(itemTimer);
    ++itemToken;
    const os = tipoItem.value === 'os';
    if (!os && !podeSelecionarProduto(item)) {
        erro.value = 'Referências de catálogo não podem entrar na Nota. Use o cadastro/vínculo oficial.';
        return;
    }
    const itemId = Number(item.id);
    const type = os ? OS : PRODUTO;
    if (form.value.itens.some(i => i.itemable_type === type && Number(i.itemable_id) === itemId)) {
        erro.value = 'Este item já foi incluído.'; return;
    }
    const price = os ? Number(item.valor) : precoProduto(item);
    if (price === null || !Number.isFinite(price) || price < 0) {
        erro.value = 'Este item não possui preço operacional válido.'; return;
    }
    markDirty();
    form.value.itens.push({ _key: `n-${Date.now()}-${item.id}`,
        itemable_type: type, itemable_id: itemId, descricao: os ? item.descricao : item.nome,
        quantidade: Number(quantidade.value) || 1, valor_unitario: price,
        desconto: 0, garantia_dias: null,
        _produto: os ? null : {marca:item.marca, codigo:item.codigo, codigo_fabricante:item.codigo_fabricante,
            codigo_barras:item.codigo_barras, imagem_url:item.imagem_url, estoque_atual:item.estoque_atual,
            estoque_minimo:item.estoque_minimo, disponivel:item.disponivel},
    });
    buscaItem.value = ''; resultados.value = []; buscaItensFeita.value = false;
    buscaItensErro.value = ''; erro.value = '';
}
function adicionarOs() {
    if (!form.value.cliente_id || !form.value.veiculo_cliente_id) {
        erro.value = 'Selecione cliente e veículo antes de incluir uma O.S.'; return;
    }
    if (!setorId.value || !descricaoOs.value.trim() || !Number.isFinite(Number(valorItem.value)) || Number(valorItem.value) < 0) {
        erro.value = 'Informe setor, descrição e valor válido da O.S.'; return;
    }
    const id = draft--;
    markDirty();
    form.value.itens.push({ _key: `d-${Math.abs(id)}`, itemable_type: OS, itemable_id: id,
        descricao: descricaoOs.value.trim(), quantidade: 1, valor_unitario: Number(valorItem.value),
        desconto: 0, garantia_dias: null, os_nova_setor_servico_id: Number(setorId.value) });
    descricaoOs.value = ''; valorItem.value = 0; erro.value = '';
}
function remover(index) {
    const item = form.value.itens[index];
    if (item.id && item.itemable_type === OS) { erro.value = 'O.S. vinculada não pode ser removida.'; return; }
    markDirty();
    form.value.itens.splice(index, 1);
}
function enviar() {
    erro.value = '';
    if (!form.value.itens.length) { erro.value = 'Adicione ao menos um item.'; return; }
    if (form.value.itens.some(i => !i.descricao?.trim() || !Number.isInteger(Number(i.quantidade)) || Number(i.quantidade) < 1 || Number(i.valor_unitario) < 0 || Number(i.desconto) < 0 || Number(i.desconto) > Number(i.quantidade) * Number(i.valor_unitario))) {
        erro.value = 'Corrija as quantidades, descrições e valores dos itens.'; return;
    }
    const payload = {
        cliente_id: form.value.cliente_id || null,
        veiculo_cliente_id: form.value.veiculo_cliente_id || null,
        km: form.value.km === '' ? null : form.value.km,
        km_proxima_troca_oleo: form.value.km_proxima_troca_oleo === '' ? null : form.value.km_proxima_troca_oleo,
        itens: form.value.itens.map(i => {
            const d = { itemable_type: i.itemable_type, itemable_id: Number(i.itemable_id),
                descricao: i.descricao, quantidade: Number(i.quantidade),
                valor_unitario: Number(i.valor_unitario), desconto: Number(i.desconto || 0),
                garantia_dias: i.garantia_dias || null };
            if (i.id) d.id = i.id;
            if (Number(i.itemable_id) < 0) d.os_nova_setor_servico_id = Number(i.os_nova_setor_servico_id);
            return d;
        }),
    };
    emit('salvar', payload);
}
</script>
<template>
    <form class="sos-edit-form" @submit.prevent="enviar" @input.capture="markDirty" @change.capture="markDirty">
        <div v-if="erro || serverError" role="alert" class="sos-alert sos-alert--error">{{ serverError || erro }}</div>
        <section class="sos-form-section">
            <h3>Atendimento</h3>
            <nav v-if="cadastros.cliente || cadastros.veiculo || cadastros.produto" class="sos-quick-creates" aria-label="Cadastros rápidos oficiais">
                <span>Precisa cadastrar algo? Abra em outra aba e depois retome esta Nota:</span>
                <a v-if="cadastros.cliente" :href="cadastros.cliente" target="_blank" rel="noopener noreferrer"><i class="bi bi-person-plus"/> Novo cliente</a>
                <a v-if="cadastros.veiculo" :href="cadastros.veiculo" target="_blank" rel="noopener noreferrer"><i class="bi bi-car-front"/> Novo veículo</a>
                <a v-if="cadastros.produto" :href="cadastros.produto" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-seam"/> Novo produto</a>
            </nav>
            <div class="sos-form-grid">
                <label class="sos-field sos-field--wide">Cliente
                    <div class="sos-inline"><input v-model="buscaCliente" type="search" placeholder="Digite ao menos 2 letras" autocomplete="off" @input="procurarCliente" :disabled="loading"/><button v-if="novoCliente" class="sos-mini-button" type="button" @click="limparCliente">Limpar</button></div>
                    <span v-if="novoCliente" class="sos-field__hint">Selecionado: {{ novoCliente.nome }}</span>
                    <div v-if="clientes.length" class="sos-suggestions">
                        <button v-for="c in clientes" :key="c.id" type="button" @click="selecionarCliente(c)">{{ c.nome }} <small>#{{ c.id }}</small></button>
                    </div>
                </label>
                <label class="sos-field">Veículo
                    <select v-model="form.veiculo_cliente_id" :disabled="!form.cliente_id || loading"><option :value="null">Sem veículo</option><option v-for="v in veiculos" :value="v.id" :key="v.id">{{ v.nome }} · {{ v.placa || 'sem placa' }}</option></select>
                </label>
                <button type="button" class="sos-mini-button sos-quick-creates__reload" :disabled="!form.cliente_id || loading" @click="carregarVeiculos"><i class="bi bi-arrow-clockwise"/> Atualizar veículos</button>
                <label class="sos-field">Quilometragem atual <input v-model.number="form.km" type="number" min="0" placeholder="Opcional" :disabled="loading"/></label>
                <label class="sos-field">Distância até a próxima troca (km)
                    <input v-model.number="diferencaTrocaOleo" type="number" min="0" step="1" placeholder="Opcional; KM atual + distância" :disabled="loading" />
                </label>
                <label class="sos-field">KM da próxima troca de óleo <input v-model.number="form.km_proxima_troca_oleo" type="number" min="0" step="1" placeholder="Opcional" :disabled="loading"/></label>
            </div>
        </section>
        <section class="sos-form-section">
            <h3>Peças e serviços</h3>
            <div class="sos-type-tabs" role="group" aria-label="Tipo de item">
                <button type="button" v-for="t in [{v:'produto',l:'Peças'},{v:'os',l:'O.S. existente'},{v:'nova_os',l:'Nova O.S.'}]" :key="t.v" :class="{'is-active': tipoItem===t.v}" @click="mudarTipoItem(t.v)">{{ t.l }}</button>
            </div>
            <div v-if="tipoItem !== 'nova_os'" class="sos-search-block">
                <label class="sos-field">Buscar {{ tipoItem === 'produto' ? 'produto' : 'O.S. sem Nota' }}
                    <input v-model="buscaItem" type="search" @input="procurarItens"
                        :placeholder="tipoItem === 'produto' ? 'Nome, descrição, marca, código interno, fabricante ou barras' : 'Número ou descrição'"
                        :disabled="loading" autocomplete="off" />
                </label>
                <p v-if="tipoItem==='os'" class="sos-field__hint">Para O.S. existente, selecione cliente e veículo acima.</p>
                <span v-if="procurando" role="status" class="sos-field__hint"><span class="spinner-border spinner-border-sm"/> Consultando…</span>
                <p v-if="buscaItensErro" class="sos-alert sos-alert--error" role="alert">{{ buscaItensErro }}</p>
                <p v-else-if="buscaItensFeita && !procurando && !resultados.length" class="sos-field__hint" role="status">Nenhum resultado encontrado.</p>
                <div v-if="resultados.length" class="sos-suggestions sos-suggestions--items sos-produtos-resultados" role="group" aria-label="Resultados da pesquisa">
                    <template v-for="(item,index) in resultados" :key="(item.catalogo_id ? 'cat-' + item.catalogo_id : 'item-' + item.id) + '-' + index">
                        <div v-if="tipoItem === 'produto' && ehReferenciaCatalogo(item)" class="sos-produto-opcao sos-produto-opcao--referencia">
                            <img v-if="item.imagem_url" :src="item.imagem_url" :alt="`Referência ${item.nome || ''}`" loading="lazy" />
                            <div class="sos-produto-opcao__detalhes">
                                <strong>{{ item.nome || item.descricao || 'Referência de catálogo' }}</strong>
                                <small v-if="item.marca">Marca: {{ item.marca }}</small>
                                <small v-if="item.codigo_fabricante">Fabricante: {{ item.codigo_fabricante }}</small>
                                <small v-if="item.codigo_barras">Código de barras: {{ item.codigo_barras }}</small>
                                <span class="sos-produto-opcao__alerta">Referência de catálogo — ainda não é produto cadastrado.</span>
                                <a v-if="urlReferencia(item.url)" :href="urlReferencia(item.url)" target="_blank" rel="noopener noreferrer">Consultar / habilitar no cadastro oficial</a>
                            </div>
                            <strong v-if="precoProduto(item) !== null">{{ money(precoProduto(item)) }}</strong>
                        </div>
                        <button v-else-if="tipoItem === 'produto'" class="sos-produto-opcao" type="button" :disabled="loading || !podeSelecionarProduto(item)" @click="selecionarItem(item)">
                            <img v-if="item.imagem_url" :src="item.imagem_url" :alt="`Imagem de ${item.nome || 'produto'}`" loading="lazy" />
                            <span class="sos-produto-opcao__detalhes">
                                <strong>{{ item.nome }}</strong>
                                <small v-if="item.descricao && item.descricao !== item.nome">{{ item.descricao }}</small>
                                <small v-if="item.marca">Marca: {{ item.marca }}</small>
                                <small v-if="item.codigo">Código interno: {{ item.codigo }}</small>
                                <small v-if="item.codigo_fabricante">Fabricante: {{ item.codigo_fabricante }}</small>
                                <small v-if="item.codigo_barras">Código de barras: {{ item.codigo_barras }}</small>
                                <small :class="{'sos-produto-opcao__alerta': item.disponivel === false || Number(item.estoque_atual) <= Number(item.estoque_minimo)}">{{ statusEstoqueProduto(item) }}</small>
                            </span>
                            <strong>{{ precoProduto(item) === null ? 'Preço não informado' : money(precoProduto(item)) }}</strong>
                        </button>
                        <button v-else type="button" @click="selecionarItem(item)">
                            <span>{{ `O.S. #${item.id} · ${item.descricao}` }}</span>
                            <strong>{{ money(item.valor) }}</strong>
                        </button>
                    </template>
                </div>
            </div>
            <div v-else class="sos-form-grid">
                <label class="sos-field">Setor de serviço <select v-model="setorId" :disabled="loading"><option value="">Selecione</option><option v-for="s in setores" :key="s.id" :value="String(s.id)">{{ s.nome }}</option></select></label>
                <label class="sos-field">Valor (R$) <input v-model.number="valorItem" type="number" step="0.01" min="0" :disabled="loading" /></label>
                <label class="sos-field sos-field--wide">Descrição do serviço <input v-model="descricaoOs" maxlength="250" placeholder="Descreva a execução" :disabled="loading"/></label>
                <div><button class="sos-btn sos-btn--secondary" type="button" @click="adicionarOs" :disabled="loading">Adicionar O.S. em rascunho</button></div>
            </div>
            <p class="sos-field__hint">Uma O.S. em rascunho só será criada quando a Nota for salva. O abandono do formulário não grava O.S.</p>
            <div class="sos-items" v-if="form.itens.length">
                <div v-for="(i,index) in form.itens" :key="i._key" class="sos-item-row">
                    <div class="sos-item-row__name"><strong>{{ i.itemable_type === OS ? (i.itemable_id < 0 ? 'Nova O.S. (rascunho)' : `O.S. #${i.itemable_id}`) : 'Peça' }}</strong>
                        <input v-model="i.descricao" type="text" maxlength="250" :disabled="loading || (i.id && i.itemable_type === OS)" aria-label="Descrição do item"/>
                        <small v-if="i._produto" class="sos-field__hint">{{ [i._produto.marca, i._produto.codigo, i._produto.codigo_fabricante].filter(Boolean).join(' · ') }} · {{ statusEstoqueProduto(i._produto) }}</small>
                    </div>
                    <label>Quantidade <input v-model.number="i.quantidade" type="number" min="1" step="1" :disabled="loading || i.itemable_type === OS"/></label>
                    <label>Unitário (R$) <input v-model.number="i.valor_unitario" type="number" step="0.01" min="0" :disabled="loading || (i.id && i.itemable_type === OS)"/></label>
                    <label>Desconto (R$) <input v-model.number="i.desconto" type="number" step="0.01" min="0" :disabled="loading"/></label>
                    <label>Garantia (dias) <input v-model.number="i.garantia_dias" type="number" step="1" min="0" placeholder="Opcional" :disabled="loading"/></label>
                    <div class="sos-item-row__total">{{ money(Math.max(0, Number(i.quantidade)*Number(i.valor_unitario)-Number(i.desconto||0))) }}</div>
                    <button v-if="!(i.id && i.itemable_type === OS)" type="button" class="sos-icon-button" @click="remover(index)" aria-label="Remover item" :disabled="loading"><i class="bi bi-trash"/></button>
                </div>
            </div>
            <div v-else class="sos-empty">Nenhum item adicionado.</div>
            <div class="sos-form-total sos-form-resumo-paridade" aria-label="Resumo de valores da Nota">
                <span>Subtotal: <strong>{{ money(totalBruto) }}</strong></span>
                <span>Descontos dos itens: <strong>{{ money(descontos) }}</strong></span>
                <span>Total da Nota: <strong>{{ money(total) }}</strong></span>
            </div>
        </section>
        <footer class="sos-modal-actions">
            <button type="button" class="sos-btn sos-btn--quiet" @click="emit('cancelar')" :disabled="loading">Fechar sem salvar</button>
            <button type="submit" class="sos-btn sos-btn--primary" :disabled="loading || !form.itens.length"><span v-if="loading" class="spinner-border spinner-border-sm"/> {{ loading ? 'Salvando…' : isEdit ? 'Salvar alterações' : 'Criar Nota' }}</button>
        </footer>
    </form>
</template>
