<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
const props = defineProps({ nota: { type: Object, default: null }, api: { type: String, required: true }, loading: Boolean, serverError: { type: String, default: '' }, cadastros: { type: Object, default: () => ({}) } });
const emit = defineEmits(['salvar', 'cancelar', 'sujo']);
const OS = 'App\\Models\\OrdemServico';
const PRODUTO = 'App\\Models\\Produto';
const clientes = ref([]), veiculos = ref([]), setores = ref([]), resultados = ref([]);
const procurando = ref(false), buscaCliente = ref(''), buscaItem = ref('');
const tipoItem = ref('produto'), quantidade = ref(1), valorItem = ref(0), descontoItem = ref(0);
const setorId = ref(''), descricaoOs = ref(''), erro = ref('');
const form = ref({ cliente_id: null, veiculo_cliente_id: null, km: null, km_proxima_troca_oleo: null, itens: [] });
const novoCliente = ref(null);
let clientTimer, itemTimer, clientToken = 0, itemToken = 0, vehicleToken = 0, draft = -1;
const money = n => Number(n ?? 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
const total = computed(() => form.value.itens.reduce((v, i) => v + Math.max(0, Number(i.quantidade) * Number(i.valor_unitario) - Number(i.desconto || 0)), 0));
const isEdit = computed(() => !!props.nota?.id);

function markDirty() { emit('sujo', true); }
function initialize() {
    clearTimeout(clientTimer);
    clearTimeout(itemTimer);
    ++clientToken; ++itemToken; ++vehicleToken;
    clientes.value = []; veiculos.value = []; resultados.value = [];
    procurando.value = false;
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
onBeforeUnmount(() => { clearTimeout(clientTimer); clearTimeout(itemTimer); ++clientToken; ++itemToken; ++vehicleToken; });
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
    clearTimeout(itemTimer);
    ++itemToken;
    tipoItem.value = tipo;
    resultados.value = [];
    procurando.value = false;
    buscaItem.value = '';
}
function procurarItens() {
    clearTimeout(itemTimer);
    const generation = ++itemToken;
    resultados.value = [];
    procurando.value = false;
    if (tipoItem.value === 'nova_os') return;
    const q = buscaItem.value.trim();
    if (q.length < 2 && tipoItem.value !== 'os') return;
    const tipo = tipoItem.value === 'produto' ? 'produtos' : 'os';
    const cliente = form.value.cliente_id;
    const veiculo = form.value.veiculo_cliente_id;
    itemTimer = setTimeout(async () => {
        if (generation !== itemToken) return;
        procurando.value = true;
        try {
            const data = await fetchOpts(tipo, { q, cliente_id: cliente, veiculo_cliente_id: veiculo });
            if (generation === itemToken) resultados.value = data;
        } catch (e) {
            if (generation === itemToken) erro.value = e.message;
        } finally {
            if (generation === itemToken) procurando.value = false;
        }
    }, 250);
}
function selecionarItem(item) {
    clearTimeout(itemTimer);
    ++itemToken;
    const os = tipoItem.value === 'os';
    const itemId = Number(item.id);
    const type = os ? OS : PRODUTO;
    if (form.value.itens.some(i => i.itemable_type === type && Number(i.itemable_id) === itemId)) {
        erro.value = 'Este item já foi incluído.'; return;
    }
    const price = Number(os ? item.valor : item.preco);
    markDirty();
    form.value.itens.push({ _key: `n-${Date.now()}-${item.id}`,
        itemable_type: type, itemable_id: itemId, descricao: os ? item.descricao : item.nome,
        quantidade: Number(quantidade.value) || 1, valor_unitario: price,
        desconto: 0, garantia_dias: null,
    });
    buscaItem.value = ''; resultados.value = []; erro.value = '';
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
                <label class="sos-field">Próxima troca de óleo <input v-model.number="form.km_proxima_troca_oleo" type="number" min="0" placeholder="Opcional" :disabled="loading"/></label>
            </div>
        </section>
        <section class="sos-form-section">
            <h3>Peças e serviços</h3>
            <div class="sos-type-tabs" role="group" aria-label="Tipo de item">
                <button type="button" v-for="t in [{v:'produto',l:'Peças'},{v:'os',l:'O.S. existente'},{v:'nova_os',l:'Nova O.S.'}]" :key="t.v" :class="{'is-active': tipoItem===t.v}" @click="mudarTipoItem(t.v)">{{ t.l }}</button>
            </div>
            <div v-if="tipoItem !== 'nova_os'" class="sos-search-block">
                <label class="sos-field">Buscar {{ tipoItem === 'produto' ? 'produto' : 'O.S. sem Nota' }}
                    <input v-model="buscaItem" type="search" @input="procurarItens" :placeholder="tipoItem === 'produto' ? 'Nome, código ou referência' : 'Número ou descrição'" :disabled="loading" />
                </label>
                <p v-if="tipoItem==='os'" class="sos-field__hint">Para O.S. existente, selecione cliente e veículo acima.</p>
                <span v-if="procurando" role="status" class="sos-field__hint"><span class="spinner-border spinner-border-sm"/> Consultando…</span>
                <div v-if="resultados.length" class="sos-suggestions sos-suggestions--items">
                    <button v-for="item in resultados" :key="item.id" type="button" @click="selecionarItem(item)">
                        <span>{{ tipoItem === 'produto' ? item.nome : `O.S. #${item.id} · ${item.descricao}` }}</span>
                        <strong>{{ money(tipoItem === 'produto' ? item.preco : item.valor) }}</strong>
                    </button>
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
                        <input v-model="i.descricao" type="text" maxlength="250" :disabled="loading || (i.id && i.itemable_type === OS)" aria-label="Descrição do item"/></div>
                    <label>Quantidade <input v-model.number="i.quantidade" type="number" min="1" step="1" :disabled="loading || i.itemable_type === OS"/></label>
                    <label>Unitário (R$) <input v-model.number="i.valor_unitario" type="number" step="0.01" min="0" :disabled="loading || (i.id && i.itemable_type === OS)"/></label>
                    <label>Desconto (R$) <input v-model.number="i.desconto" type="number" step="0.01" min="0" :disabled="loading"/></label>
                    <div class="sos-item-row__total">{{ money(Math.max(0, Number(i.quantidade)*Number(i.valor_unitario)-Number(i.desconto||0))) }}</div>
                    <button v-if="!(i.id && i.itemable_type === OS)" type="button" class="sos-icon-button" @click="remover(index)" aria-label="Remover item" :disabled="loading"><i class="bi bi-trash"/></button>
                </div>
            </div>
            <div v-else class="sos-empty">Nenhum item adicionado.</div>
            <div class="sos-form-total">Total da Nota <strong>{{ money(total) }}</strong></div>
        </section>
        <footer class="sos-modal-actions">
            <button type="button" class="sos-btn sos-btn--quiet" @click="emit('cancelar')" :disabled="loading">Fechar sem salvar</button>
            <button type="submit" class="sos-btn sos-btn--primary" :disabled="loading || !form.itens.length"><span v-if="loading" class="spinner-border spinner-border-sm"/> {{ loading ? 'Salvando…' : isEdit ? 'Salvar alterações' : 'Criar Nota' }}</button>
        </footer>
    </form>
</template>
