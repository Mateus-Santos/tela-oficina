<script setup>
import { ref, reactive, computed, nextTick, watch, onMounted, onBeforeUnmount } from 'vue';
import { reconciliarEtapaNota } from './reconciliarEtapaNota.js';
import UiModal from '../../components/sos/UiModal.vue';
import UiFilterDrawer from '../../components/sos/UiFilterDrawer.vue';
import NotaCard from './components/NotaCard.vue';
import NotaForm from './components/NotaForm.vue';
import NotaAnexoForm from './components/NotaAnexoForm.vue';
import { formatarDataNota } from './validarAnexoNota.mjs';
const props = defineProps({
    api: { type: String, required: true },
    base: { type: String, required: true },
    legacyCreate: { type: String, default: '' },
    attachmentsTemplate: { type: String, default: '' },
    buscaItensUrl: { type: String, default: '' },
    createCliente: { type: String, default: '' },
    createVeiculo: { type: String, default: '' },
    createProduto: { type: String, default: '' },
});
const statusOptions = [{v:'Aberto',l:'Em aberto'}, {v:'Finalizado',l:'Finalizadas'}, {v:'Cancelado',l:'Canceladas'}, {v:'Todos',l:'Todas'}];
const types = [{v:'todos',l:'Peças e serviços'}, {v:'pecas',l:'Com peças'}, {v:'servicos',l:'Com serviços'}, {v:'mistas',l:'Mistas'}, {v:'sem_itens',l:'Sem itens'}];
const initial = new URLSearchParams(window.location.search);
const filtros = reactive({status:initial.get('status') || 'Aberto', etapa_id: initial.get('etapa_id') || '',
    q: initial.get('q') || '', cliente: initial.get('cliente') || '', placa: initial.get('placa') || '',
    item_tipo: initial.get('item_tipo') || 'todos', incluir_canceladas: initial.get('incluir_canceladas') === '1' ? '1' : '0'});
const view = ref(initial.get('visualizacao') === 'lista' ? 'lista' : 'quadro');
const notas = ref([]), etapas = ref([]), metricas = ref(null), pagination = ref(null);
const filtroOpen = ref(false), modalOpen = ref(false), modalMode = ref('show'), detalhes = ref(null);
const pending = ref(''), loading = ref(false), loadingMore = ref(false), notePending = ref(null);
const aviso = ref(''), error = ref(''), moveFeedback = ref(''), finalizeOpen = ref(false), categorias = ref([]);
// L03N: drag targets sempre acompanham a maior coluna; lote e finalizacao isolados.
const dropTarget = ref(''), batchOpen = ref(false), batchFrom = ref(''), batchTo = ref('');
const batchPreview = ref(null), batchBusy = ref(false), batchError = ref('');
let batchGeneration = 0;

const categoria = ref(''), parcelas = ref([]), formError = ref('');
const formDirty = ref(false);
const somaParcelas = computed(() => Math.round(parcelas.value.reduce((s,p) => s + Number(p.valor || 0), 0) * 100) / 100);
function dividirParcelas() {
    if (!detalhes.value || !parcelas.value.length) return;
    const centavos = Math.round(Number(detalhes.value.total) * 100);
    const cada = Math.floor(centavos / parcelas.value.length);
    const resto = centavos % parcelas.value.length;
    parcelas.value.forEach((p,i) => { p.numero=i+1; p.valor=(cada+(i<resto?1:0))/100; });
}
function adicionarParcela() {
    const hoje = parcelas.value[0]?.data_vencimento || new Date().toISOString().slice(0,10);
    parcelas.value.push({numero:parcelas.value.length+1,valor:0,data_vencimento:hoje});
    dividirParcelas();
}
function removerParcela(index) {
    if (parcelas.value.length <= 1) return;
    parcelas.value.splice(index,1); dividirParcelas();
}
const filtroCount = computed(() => [filtros.etapa_id, filtros.cliente, filtros.placa, filtros.item_tipo !== 'todos' ? filtros.item_tipo : '', filtros.status === 'Todos' && filtros.incluir_canceladas === '1' ? 'canceladas' : ''].filter(Boolean).length);
const money = n => Number(n ?? 0).toLocaleString('pt-BR', { style:'currency', currency:'BRL' });
const numbers = n => Number(n ?? 0).toLocaleString('pt-BR');
const viewGrupos = computed(() => {
    const result = etapas.value.map(e => ({ id: String(e.id), etapa:e, notas:notas.value.filter(n => String(n.etapa_id) === String(e.id)) }));
    const semEtapa = notas.value.filter(n => !n.etapa_id || !etapas.value.some(e => String(e.id) === String(n.etapa_id)));
    if (semEtapa.length) result.push({id:'0', etapa:{nome:'Sem etapa',cor:'secondary'}, notas:semEtapa});
    return result;
});
// L03M: uma unica fonte de rolagem horizontal. O controle superior e um
// slider, nao outro scroller: elimina ciclos de sincronizacao e tremedeira.
const INITIAL_CARDS_PER_LANE = 2;
const MORE_CARDS_PER_LANE = 3;
const laneVisible = reactive({});
function visibleInLane(grupo) {
    return grupo.notas.slice(0, laneVisible[grupo.id] || INITIAL_CARDS_PER_LANE);
}
function hiddenInLane(grupo) {
    return Math.max(0, grupo.notas.length - visibleInLane(grupo).length);
}
function showMoreInLane(grupo) {
    laneVisible[grupo.id] = (laneVisible[grupo.id] || INITIAL_CARDS_PER_LANE) + MORE_CARDS_PER_LANE;
    void nextTick(updateBoardMetrics);
}
const boardRef = ref(null);
const scrollPosition = ref(0), scrollMax = ref(0), selectedStageId = ref('');
const canScrollLeft = computed(() => scrollPosition.value > 2);
const canScrollRight = computed(() => scrollPosition.value < scrollMax.value - 2);
function updateBoardMetrics() {
    const board = boardRef.value;
    if (!board) { scrollPosition.value = 0; scrollMax.value = 0; selectedStageId.value = ''; return; }
    scrollMax.value = Math.max(0, Math.round(board.scrollWidth - board.clientWidth));
    scrollPosition.value = Math.min(scrollMax.value, Math.max(0, Math.round(board.scrollLeft)));
    const lanes = [...board.querySelectorAll(':scope > .sos-lane')];
    if (lanes.length) {
        const left = board.getBoundingClientRect().left;
        const nearest = lanes.reduce((best, el) =>
            Math.abs(el.getBoundingClientRect().left - left) < Math.abs(best.getBoundingClientRect().left - left) ? el : best,
            lanes[0]);
        selectedStageId.value = String(nearest.dataset.stageId || '');
    }
}
function setBoardOffset(target) {
    const board = boardRef.value;
    if (!board) return;
    const max = Math.max(0, board.scrollWidth - board.clientWidth);
    board.scrollLeft = Math.max(0, Math.min(max, Number(target) || 0));
    updateBoardMetrics();
}
function scrollBoard(direction) {
    const board = boardRef.value;
    if (!board) return;
    const lane = board.querySelector('.sos-lane');
    const gap = Number.parseFloat(getComputedStyle(board).columnGap) || 14;
    const step = Math.max(240, (lane?.getBoundingClientRect().width || 270) + gap);
    // Sem behavior:smooth: evita combinar animacao, snap e eventos de rolagem.
    setBoardOffset(board.scrollLeft + step * direction);
}
function jumpToStage(event) {
    const board = boardRef.value;
    if (!board) return;
    const lane = [...board.querySelectorAll(':scope > .sos-lane')].find(el => String(el.dataset.stageId) === String(event.target.value));
    if (!lane) return;
    const target = lane.getBoundingClientRect().left - board.getBoundingClientRect().left + board.scrollLeft;
    setBoardOffset(target);
}
function slideBoard(event) { setBoardOffset(event.target.value); }
watch([view, loading, notas, etapas], () => { void nextTick(updateBoardMetrics); }, { flush: 'post' });
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
let boardAbort, qTimer;
let boardGeneration = 0, detailGeneration = 0;
function updateUrl() {
    const p = new URLSearchParams();
    Object.entries(filtros).forEach(([k,v]) => { if (v !== '' && v !== null && !(k==='status'&&v==='Aberto') && !(k==='item_tipo'&&v==='todos') && !(k==='incluir_canceladas'&&v==='0')) p.set(k,String(v)); });
    if (view.value !== 'quadro') p.set('visualizacao',view.value);
    const query = p.toString();
    window.history.replaceState({}, '', window.location.pathname + (query ? '?' + query : ''));
}
async function parseError(r) {
    if (r.status === 419) return new Error('Sessão expirada. Faça login novamente.');
    const body = await r.json().catch(() => ({}));
    const errors = Object.values(body.errors || {}).flat();
    return new Error(errors.slice(0, 5).join(' · ') || body.message || `Erro HTTP ${r.status}`);
}
async function get(url, signal) {
    const r = await fetch(url, {credentials:'same-origin', headers:{Accept:'application/json'}, signal});
    if (!r.ok) throw await parseError(r);
    return r.json();
}
async function mutate(url, method, data) {
    const r = await fetch(url, {method, credentials:'same-origin',
        headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'X-Requested-With':'XMLHttpRequest'},
        body: data === null ? undefined : JSON.stringify(data)});
    if (!r.ok) throw await parseError(r);
    return r.json();
}
function notify(msg, isError = false) { aviso.value = isError ? '' : msg; error.value = isError ? msg : ''; }
async function loadBoard(reset = true, { silent = false } = {}) {
    if (reset) {
        ++boardGeneration;
        boardAbort?.abort();
        boardAbort = new AbortController();
        // Reconciliacao silenciosa nao desmonta o Kanban, mesmo se havia
        // outro carregamento de filtros em andamento.
        loading.value = !silent;
        loadingMore.value = false;
    } else {
        if (loading.value || !pagination.value?.possui_mais || loadingMore.value) return;
        loadingMore.value = true;
    }
    const generation = boardGeneration;
    const current = reset ? 1 : (pagination.value.pagina + 1);
    const p = new URLSearchParams();
    Object.entries(filtros).forEach(([k, v]) => {
        if (v !== '' && v !== null) p.set(k, String(v));
    });
    p.set('pagina', String(current));
    try {
        // O mesmo AbortController protege também o "Carregar mais".
        const result = await get(`${props.api}/dados?${p}`, boardAbort?.signal);
        if (generation !== boardGeneration) return;
        notas.value = reset ? result.data : [...notas.value, ...result.data];
        etapas.value = result.etapas;
        metricas.value = result.metricas;
        pagination.value = result.paginacao;
        if (reset) { error.value = ''; updateUrl(); }
    } catch (e) {
        if (generation === boardGeneration && e.name !== 'AbortError') notify(e.message, true);
    } finally {
        if (generation === boardGeneration) {
            if (reset && !silent) loading.value = false;
            else if (!reset) loadingMore.value = false;
        }
    }
}
function aplicar() { filtroOpen.value = false; void loadBoard(true); }
function buscar() { clearTimeout(qTimer); qTimer = setTimeout(aplicar, 340); }
function limpar() { Object.assign(filtros,{status:'Aberto',etapa_id:'',q:'',cliente:'',placa:'',item_tipo:'todos',incluir_canceladas:'0'}); aplicar(); }
function setView(v) { view.value = v; updateUrl(); }
function toggleCancelled(event) {
    filtros.incluir_canceladas = event.target.checked ? '1' : '0';
    if (filtros.status === 'Todos') aplicar();
    else updateUrl(); // A opção fica preparada para quando a pessoa abrir "Todas".
}
async function openNote(id) {
    const generation = ++detailGeneration;
    modalMode.value = 'show';
    modalOpen.value = true;
    detalhes.value = null;
    pending.value = 'Carregando detalhes…';
    try {
        const result = await get(`${props.api}/${id}`);
        if (generation === detailGeneration && modalOpen.value && modalMode.value === 'show') {
            detalhes.value = result.data;
        }
    } catch (e) {
        if (generation === detailGeneration) {
            notify(e.message, true);
            modalOpen.value = false;
        }
    } finally {
        if (generation === detailGeneration) pending.value = '';
    }
}
function createNote() {
    ++detailGeneration;
    detalhes.value = null;
    formDirty.value = false;
    formError.value = '';
    modalMode.value='create'; modalOpen.value=true; finalizeOpen.value=false;
}
function editNote() {
    if (!detalhes.value?.pode_editar) return;
    ++detailGeneration;
    formDirty.value = false;
    formError.value = '';
    modalMode.value='edit'; finalizeOpen.value=false;
}
function closeNote() {
    // Permitir fechar a consulta em andamento, mas nunca uma gravacao em curso.
    if (pending.value && !(modalMode.value === 'show' && !detalhes.value)) return;
    if ((modalMode.value === 'create' || modalMode.value === 'edit')
        && formDirty.value && !window.confirm('Descartar as alterações não salvas desta Nota?')) return;
    formDirty.value = false;
    ++detailGeneration;
    modalOpen.value=false; detalhes.value=null; finalizeOpen.value=false;
    pending.value=''; formError.value='';
}
async function saveNote(body) {
    if (pending.value) return;
    formError.value = '';
    pending.value = modalMode.value==='edit'?'Salvando alterações…':'Criando Nota…';
    try {
        const isEdit = modalMode.value === 'edit';
        const data = await mutate(isEdit ? `${props.api}/${detalhes.value.id}` : props.api,
            isEdit?'PUT':'POST',body);
        notify(data.message || 'Nota salva.');
        formDirty.value = false;
        modalOpen.value=false; detalhes.value=null;
        await loadBoard(true);
        await openNote(data.id);
    } catch(e) { formError.value = e.message; notify(e.message,true); }
    finally { pending.value=''; }
}
async function startFinalizationFromStage(n) {
    if (notePending.value || pending.value || n.status !== 'Aberto') return;
    notePending.value = n.id;
    try {
        // O drop nao efetua um PATCH de etapa: abre a Action oficial de finalizar.
        await openNote(n.id);
        if (!modalOpen.value || !detalhes.value || String(detalhes.value.id) !== String(n.id)) return;
        if (!detalhes.value.pode_finalizar) throw new Error('A Nota nao pode ser finalizada neste estado.');
        await openFinalizar();
    } catch (e) { notify(e.message, true); }
    finally { notePending.value = null; }
}
async function moveNote(n, id) {
    if (notePending.value || pending.value || n.status !== 'Aberto' || !id) return;
    const target = etapas.value.find(e => String(e.id) === String(id));
    if (!target) return;
    // A etapa final e um comando de finalizacao, NAO uma simples mudanca de etapa.
    if (target.tipo === 'final') { await startFinalizationFromStage(n); return; }
    if (String(n.etapa_id) === String(id)) return;
    const generation = boardGeneration;
    const active = document.activeElement;
    const restoreSelectFocus = active instanceof HTMLSelectElement &&
        active.closest('.sos-note')?.dataset.noteId === String(n.id);
    notePending.value = n.id;
    moveFeedback.value = '';
    aviso.value = ''; // Não inserir banner que desloca o Kanban após o drop.
    try {
        // Mantém o cartão na coluna de origem enquanto a Action Laravel confirma.
        // Nenhuma requisição de listagem nem skeleton para movimentos comuns.
        await mutate(`${props.api}/${n.id}/etapa`, 'PATCH', { etapa_id: Number(id) });
        if (generation !== boardGeneration) {
            // O usuário mudou filtros enquanto o PATCH estava pendente.
            // Revalidar os filtros atuais sem ocultar o quadro.
            await loadBoard(true, { silent: true });
            return;
        }
        const resultado = reconciliarEtapaNota({
            notas: notas.value,
            etapas: etapas.value,
            metricas: metricas.value,
            notaId: n.id,
            novaEtapaId: id,
            filtroEtapaId: filtros.etapa_id,
        });
        if (!resultado.atualizada) return;
        // O detalhe aberto do mesmo atendimento acompanha a etapa sem novo GET.
        const destino = etapas.value.find(e => String(e.id) === String(id));
        if (detalhes.value && String(detalhes.value.id) === String(n.id) && destino) {
            detalhes.value.etapa_id = destino.id;
            detalhes.value.etapa = destino.nome;
        }
        // Se há filtro de etapa, a composição e as métricas precisam ser
        // recalculadas no servidor, mas sem desmontar o quadro e os modais.
        if (resultado.revalidar) await loadBoard(true, { silent: true });
        // A nota recem-movida precisa ficar visivel mesmo em uma coluna recolhida.
        const destinoNotas = notas.value.filter(item => String(item.etapa_id) === String(id));
        const movedIndex = destinoNotas.findIndex(item => String(item.id) === String(n.id));
        if (movedIndex >= 0) laneVisible[String(id)] = Math.max(laneVisible[String(id)] || INITIAL_CARDS_PER_LANE, movedIndex + 1);
        const etapa = etapas.value.find(e => String(e.id) === String(id));
        moveFeedback.value = `Nota #${n.id} movida para ${etapa?.nome || 'a nova etapa'}.`;
        void nextTick(updateBoardMetrics);
        if (restoreSelectFocus) {
            await nextTick();
            document.querySelector(`.sos-vue-app .sos-note[data-note-id="${n.id}"] select`)?.focus({ preventScroll: true });
        }
    } catch (e) { notify(e.message, true); }
    finally { notePending.value = null; }
}
function dragOverLane(e, stageId) {
    if (!e.dataTransfer.types.includes('text/plain')) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    dropTarget.value = String(stageId);
}
function leaveLane(e, stageId) {
    if (!e.currentTarget.contains(e.relatedTarget) && dropTarget.value === String(stageId)) dropTarget.value = '';
}
function dropNote(e, stageId) {
    dropTarget.value = '';
    const id = Number(e.dataTransfer.getData('text/plain'));
    const n = notas.value.find(x => x.id === id);
    if (n && stageId !== '0') void moveNote(n, stageId);
}
function openBatch() {
    batchGeneration++;
    batchPreview.value = null;
    batchError.value = '';
    const operacionais = etapas.value.filter(e => e.tipo !== 'final');
    batchFrom.value = String(operacionais[0]?.id || '');
    batchTo.value = String(operacionais.find(e => String(e.id) !== batchFrom.value)?.id || '');
    batchOpen.value = true;
}
function closeBatch() {
    if (batchBusy.value) return;
    batchGeneration++;
    batchOpen.value = false;
    batchPreview.value = null;
}
function changeBatch() { batchGeneration++; batchPreview.value = null; batchError.value = ''; batchBusy.value = false; }
async function previewBatch() {
    const generation = ++batchGeneration;
    batchPreview.value = null; batchError.value = ''; batchBusy.value = true;
    try {
        const p = new URLSearchParams({origem_etapa_id:batchFrom.value, destino_etapa_id:batchTo.value});
        const result = await get(`${props.api}/etapas/lote?${p}`);
        if (generation === batchGeneration && batchOpen.value) batchPreview.value = result;
    } catch(e) { if (generation === batchGeneration) batchError.value = e.message; }
    finally { if (generation === batchGeneration) batchBusy.value = false; }
}
async function executeBatch() {
    if (!batchPreview.value || batchBusy.value || batchPreview.value.excede_limite || !batchPreview.value.contagem) return;
    const expected = batchPreview.value;
    if (!window.confirm(`Mover ${expected.contagem} Notas abertas para a etapa selecionada? Esta operacao cria historico para cada Nota.`)) return;
    batchBusy.value = true; batchError.value = '';
    try {
        const r = await mutate(`${props.api}/etapas/lote`, 'POST', {
            origem_etapa_id:Number(batchFrom.value), destino_etapa_id:Number(batchTo.value),
            assinatura:expected.assinatura, confirmacao:'MOVER_ETAPA_LOTE',
        });
        closeBatchAfterSuccess();
        notify(r.message || `${r.movidas} Notas movidas.`);
        await loadBoard(true, {silent:true});
    } catch(e) { batchError.value = e.message; batchPreview.value = null; }
    finally { batchBusy.value = false; }
}
function closeBatchAfterSuccess() {
    batchGeneration++; batchOpen.value = false; batchPreview.value = null;
}
async function cancelNote() {
    if (!detalhes.value?.pode_cancelar || pending.value) return;
    if (!window.confirm(`Cancelar a Nota #${detalhes.value.id}? Se estiver finalizada, haverá reversões conforme as regras de estoque e financeiro.`)) return;
    pending.value='Cancelando Nota…';
    try { const r=await mutate(`${props.api}/${detalhes.value.id}/cancelar`,'POST',null); notify(r.message); modalOpen.value=false; await loadBoard(true); }
    catch(e) { notify(e.message,true); }
    finally { pending.value=''; }
}
async function deleteNote() {
    if (!detalhes.value?.pode_excluir || pending.value) return;
    if (!window.confirm(`Excluir definitivamente a Nota #${detalhes.value.id}?`)) return;
    pending.value='Excluindo Nota…';
    try { const r=await mutate(`${props.api}/${detalhes.value.id}`,'DELETE',null); notify(r.message); modalOpen.value=false; await loadBoard(true); }
    catch(e) { notify(e.message,true); }
    finally { pending.value=''; }
}
async function openFinalizar() {
    if (!detalhes.value?.pode_finalizar) return;
    finalizeOpen.value=true;
    if (detalhes.value.conta_receber) return;
    pending.value='Carregando categorias financeiras…';
    try {
        const d = await get(`${props.api}/opcoes?tipo=categorias`);
        categorias.value=d.data; categoria.value=String(categorias.value[0]?.id || '');
        const hoje = new Date();
        const vencimento = `${hoje.getFullYear()}-${String(hoje.getMonth()+1).padStart(2,'0')}-${String(hoje.getDate()).padStart(2,'0')}`;
        parcelas.value=[{numero:1,valor:Number(detalhes.value.total),data_vencimento:vencimento}];
    } catch(e) { notify(e.message,true); }
    finally { pending.value=''; }
}
async function finishNote() {
    if (!detalhes.value || pending.value) return;
    const body = detalhes.value.conta_receber ? {} : {
        categoria_financeira_id: Number(categoria.value),
        parcelas:parcelas.value.map((p,i) => ({numero:i+1,valor:Number(p.valor),data_vencimento:p.data_vencimento})),
    };
    pending.value='Finalizando Nota, conferindo estoque e financeiro…';
    try {
        const id = detalhes.value.id;
        const r = await mutate(`${props.api}/${id}/finalizar`, 'POST', body);
        notify(r.message); finalizeOpen.value = false;
        // Reconciliacao sem skeleton: mantem o dialogo e a rolagem do usuario.
        await loadBoard(true, {silent:true});
        const atualizado = await get(`${props.api}/${id}`);
        if (modalOpen.value && detalhes.value && String(detalhes.value.id) === String(id)) detalhes.value = atualizado.data;
    } catch(e) { notify(e.message,true); }
    finally { pending.value=''; }
}
function handlePopstate() {
    const p = new URLSearchParams(window.location.search);
    Object.keys(filtros).forEach(k => { filtros[k]=p.get(k) ?? ({status:'Aberto',item_tipo:'todos',incluir_canceladas:'0'}[k] ?? ''); });
    view.value=p.get('visualizacao')==='lista'?'lista':'quadro';
    void loadBoard(true);
}
onMounted(() => { window.addEventListener('popstate',handlePopstate); window.addEventListener('resize',updateBoardMetrics); void loadBoard(true); });
onBeforeUnmount(() => { ++boardGeneration; ++detailGeneration; ++batchGeneration; boardAbort?.abort(); clearTimeout(qTimer); window.removeEventListener('popstate',handlePopstate); window.removeEventListener('resize',updateBoardMetrics); });
</script>
<template>
    <div class="sos-vue-app" :aria-busy="loading" @dragend.capture="dropTarget=''">
        <header class="sos-vue-head">
            <div><span class="sos-label">S.O.S MECÂNICA · CENTRAL OPERACIONAL</span><h1>Notas e atendimentos</h1><p>Etapas, clientes, peças e serviços em um só lugar.</p></div>
            <div class="sos-vue-head__actions">
                <a v-if="props.legacyCreate" :href="props.legacyCreate" class="sos-btn sos-btn--secondary"><i class="bi bi-box-arrow-up-right"/> Formulário completo</a>
                <button type="button" class="sos-btn sos-btn--primary" @click="createNote"><i class="bi bi-plus-lg"/> Nova Nota</button>
            </div>
        </header>
        <div class="sos-status-line">
            <nav class="sos-tabs" aria-label="Status das notas">
                <button v-for="opt in statusOptions" :key="opt.v" type="button" :class="{'is-active':filtros.status===opt.v}" :aria-current="filtros.status===opt.v?'page':undefined" @click="filtros.status=opt.v; aplicar()">{{ opt.l }}</button>
            </nav>
            <label class="sos-cancel-switch" title="Aplica-se à aba Todas. Na aba Canceladas as notas canceladas aparecem sempre.">
                <input type="checkbox" :checked="filtros.incluir_canceladas === '1'" @change="toggleCancelled" />
                <span>Exibir canceladas em Todas</span>
            </label>
        </div>
        <p class="sos-cancel-rule" role="note">
            <i class="bi bi-info-circle" aria-hidden="true" />
            <template v-if="filtros.status === 'Cancelado'">A aba Canceladas mostra somente notas canceladas; os cartões são cinza e não podem ser movidos.</template>
            <template v-else-if="filtros.status === 'Todos' && filtros.incluir_canceladas === '1'">Canceladas incluídas, identificadas em cinza-escuro e sem movimentação. Valores e indicadores incluem essas notas.</template>
            <template v-else>Na aba Todas, canceladas ficam ocultas por padrão. Marque a opção para incluí-las nos cartões e indicadores.</template>
        </p>
        <section class="sos-metrics" aria-label="Indicadores da consulta">
            <article class="sos-metric"><span><i class="bi bi-people"/> Atendimentos</span><strong>{{ numbers(metricas?.notas) }}</strong><small>{{ numbers(metricas?.clientes) }} clientes distintos</small></article>
            <article class="sos-metric sos-metric--finance"><span><i class="bi bi-cash-stack"/> Peças e Serviços</span><dl class="sos-metric__finance">
                <div><dt>Peças</dt><dd>{{ money(metricas?.pecas) }}</dd></div>
                <div><dt>Serviços</dt><dd>{{ money(metricas?.servicos) }}</dd></div>
                <div class="sos-metric__finance-total"><dt>Total dos itens</dt><dd>{{ money(Number(metricas?.pecas || 0) + Number(metricas?.servicos || 0)) }}</dd></div>
            </dl></article>
            <article class="sos-metric"><span><i class="bi bi-check-circle"/> Notas finalizadas</span><strong>{{ numbers(metricas?.etapa_final) }}</strong><small>Na última etapa operacional, conforme os filtros</small></article>
            <article class="sos-metric"><span><i class="bi bi-exclamation-triangle"/> Estoque insuficiente</span><strong>{{ numbers(metricas?.sem_estoque) }}</strong><small>Notas abertas com falta de peças</small></article>
        </section>
        <p class="sos-total-caption">Valor das notas filtradas: <strong>{{ money(metricas?.valor_notas) }}</strong>. Valores em itens não representam faturamento realizado.</p>
        <section class="sos-toolbar" aria-label="Pesquisa e modos de exibição">
            <div class="sos-search"><i class="bi bi-search"/><input v-model="filtros.q" @input="buscar" maxlength="120" type="search" placeholder="Número, cliente ou placa…" aria-label="Pesquisar notas"/><button type="button" @click="aplicar" aria-label="Pesquisar"><i class="bi bi-arrow-right"/></button></div>
            <button type="button" class="sos-btn sos-btn--secondary" @click="filtroOpen=true"><i class="bi bi-sliders"/> Filtros <span v-if="filtroCount" class="sos-pill">{{ filtroCount }}</span></button>
            <div class="sos-toggle" role="group" aria-label="Modo de visualização"><button type="button" :class="{'is-active':view==='quadro'}" @click="setView('quadro')"><i class="bi bi-kanban"/> Quadro</button><button type="button" :class="{'is-active':view==='lista'}" @click="setView('lista')"><i class="bi bi-list-ul"/> Lista</button></div>
        </section>
        <p v-if="moveFeedback" role="status" class="sos-move-feedback"><i class="bi bi-check-circle" aria-hidden="true"/> {{ moveFeedback }} <button type="button" aria-label="Dispensar confirmação da etapa" @click="moveFeedback=''">×</button></p>
        <p v-if="aviso" role="status" class="sos-alert sos-alert--success"><i class="bi bi-check-circle"/> {{ aviso }} <button type="button" aria-label="Dispensar aviso" @click="aviso=''">×</button></p>
        <p v-if="error" role="alert" class="sos-alert sos-alert--error"><i class="bi bi-exclamation-triangle"/> {{ error }} <button type="button" aria-label="Dispensar erro" @click="error=''">×</button></p>
        <div v-if="loading" class="sos-skeleton" role="status"><span class="spinner-border"/> Atualizando notas e indicadores…</div>
        <template v-else>
            <div v-if="!notas.length" class="sos-empty">Nenhuma nota corresponde aos filtros. <button type="button" class="sos-link" @click="limpar">Limpar filtros</button></div>
            <div v-else-if="view==='quadro'" class="sos-kanban-viewport">
                <div class="sos-board-controls">
                    <button type="button" class="sos-btn sos-btn--secondary sos-batch-entry" :disabled="notePending !== null" @click="openBatch"><i class="bi bi-layers" /> Mover etapa em lote</button>
                    <span class="sos-board-hint"><i class="bi bi-arrows" aria-hidden="true" /> Deslize as etapas; role a página normalmente para cima/baixo</span>
                    <div class="sos-board-navigation" role="group" aria-label="Navegação horizontal do Kanban">
                        <button type="button" :disabled="!canScrollLeft" aria-label="Ver etapa anterior" @click="scrollBoard(-1)"><i class="bi bi-chevron-left" /></button>
                        <button type="button" :disabled="!canScrollRight" aria-label="Ver próxima etapa" @click="scrollBoard(1)"><i class="bi bi-chevron-right" /></button>
                    </div>
                </div>
                <div class="sos-board-jump">
                    <label for="sos-board-stage-jump">Ir para etapa</label>
                    <select id="sos-board-stage-jump" :value="selectedStageId" @change="jumpToStage" aria-label="Ir diretamente para uma etapa">
                        <option v-for="grupo in viewGrupos" :key="grupo.id" :value="grupo.id">{{ grupo.etapa.nome }}</option>
                    </select>
                    <input v-if="scrollMax > 0" class="sos-board-progress" type="range" min="0" :max="scrollMax" step="1" :value="scrollPosition" @input="slideBoard" aria-label="Deslocamento horizontal pelas etapas" />
                </div>
                <section ref="boardRef" class="sos-board" aria-label="Kanban de notas" tabindex="0" @scroll.passive="updateBoardMetrics">
                    <div v-for="grupo in viewGrupos" :key="grupo.id" :data-stage-id="grupo.id" class="sos-lane" :class="[`sos-stage--${grupo.etapa.cor}`, {'sos-lane--drop-target':dropTarget === grupo.id, 'sos-lane--final':grupo.etapa.tipo === 'final'}]" @dragover="dragOverLane($event,grupo.id)" @dragleave="leaveLane($event,grupo.id)" @drop.prevent="dropNote($event,grupo.id)">
                        <header class="sos-lane__head"><div class="sos-lane__name"><span class="sos-stage-dot"/><h2>{{ grupo.etapa.nome }}</h2></div><span class="sos-count">{{ grupo.notas.length }}</span></header>
                        <p v-if="grupo.etapa.tipo === 'final'" class="sos-lane__finish-hint"><i class="bi bi-shield-check" /> Soltar aqui inicia a finalização real, com confirmação e validação financeira.</p>
                        <div class="sos-lane__cards"><NotaCard v-for="nota in visibleInLane(grupo)" :key="nota.id" :nota="nota" :etapas="etapas" :loading="notePending===nota.id" @abrir="openNote" @mover="moveNote"/><p v-if="!grupo.notas.length" class="sos-lane__empty">Nenhuma nota carregada nesta etapa</p></div>
                        <button v-if="hiddenInLane(grupo)" type="button" class="sos-lane__more" @click="showMoreInLane(grupo)">Ver mais {{ Math.min(hiddenInLane(grupo), MORE_CARDS_PER_LANE) }} nesta etapa <span>({{ hiddenInLane(grupo) }} restantes)</span></button>
                    </div>
                </section>
            </div>
            <section v-else class="sos-list" aria-label="Listagem de notas"><NotaCard v-for="nota in notas" :key="nota.id" :nota="nota" :etapas="etapas" :loading="notePending===nota.id" @abrir="openNote" @mover="moveNote"/></section>
        </template>
        <footer class="sos-pagination" v-if="pagination"><span>Exibindo {{ numbers(notas.length) }} de {{ numbers(pagination.total) }} notas</span>
            <button v-if="pagination.possui_mais" type="button" class="sos-btn sos-btn--secondary" :disabled="loadingMore" @click="loadBoard(false)"><span v-if="loadingMore" class="spinner-border spinner-border-sm"/> {{ loadingMore?'Carregando…':'Carregar mais' }}</button>
        </footer>
        <UiFilterDrawer :open="filtroOpen" :filtros="filtros" :etapas="etapas" :types="types" @fechar="filtroOpen=false" @aplicar="aplicar" @limpar="limpar" />
        <UiModal :open="batchOpen" title="Mover Notas entre etapas" @close="closeBatch">
            <div class="sos-batch-form">
                <p>Movimenta todas as <strong>Notas abertas</strong> da etapa de origem, registrando o histórico. Não altera o financeiro nem finaliza Notas. Para a etapa final, finalize cada Nota individualmente.</p>
                <label class="sos-field">Etapa de origem<select v-model="batchFrom" :disabled="batchBusy" @change="changeBatch"><option v-for="e in etapas.filter(e=>e.tipo!=='final')" :key="e.id" :value="String(e.id)">{{ e.nome }}</option></select></label>
                <label class="sos-field">Etapa de destino<select v-model="batchTo" :disabled="batchBusy" @change="changeBatch"><option v-for="e in etapas.filter(e=>e.tipo!=='final' && String(e.id)!==batchFrom)" :key="e.id" :value="String(e.id)">{{ e.nome }}</option></select></label>
                <button type="button" class="sos-btn sos-btn--secondary" :disabled="batchBusy || !batchFrom || !batchTo || batchFrom===batchTo" @click="previewBatch">Conferir quantidade</button>
                <p v-if="batchPreview" role="status" class="sos-batch-preview"><strong>{{ batchPreview.contagem }}</strong> Notas abertas encontradas. <span v-if="batchPreview.excede_limite">Limite operacional de {{ batchPreview.limite }} por ação: a operação está bloqueada.</span><span v-else>Se alguma Nota mudar antes da confirmação, o sistema bloqueará o lote e exigirá nova prévia.</span></p>
                <p v-if="batchError" role="alert" class="sos-alert sos-alert--error">{{ batchError }}</p>
                <div class="sos-modal-actions"><button type="button" class="sos-btn sos-btn--quiet" :disabled="batchBusy" @click="closeBatch">Voltar</button><button type="button" class="sos-btn sos-btn--primary" :disabled="batchBusy || !batchPreview || batchPreview.excede_limite || !batchPreview.contagem" @click="executeBatch">{{ batchBusy ? 'Movendo…' : 'Confirmar movimentação em lote' }}</button></div>
            </div>
        </UiModal>
        <UiModal :open="modalOpen" :title="modalMode==='create'?'Nova Nota': modalMode==='edit'?`Editar Nota #${detalhes?.id}`:`Nota #${detalhes?.id ?? '…'}`" wide @close="closeNote">
            <div v-if="pending && !detalhes && modalMode==='show'" class="sos-skeleton" role="status"><span class="spinner-border"/> {{ pending }}</div>
            <NotaForm v-else-if="modalMode==='create' || modalMode==='edit'" :key="modalMode+'-'+(detalhes?.id||'nova')" :api="api" :nota="modalMode==='edit'?detalhes:null" :loading="!!pending" :server-error="formError" :busca-itens-url="props.buscaItensUrl" :cadastros="{cliente:props.createCliente,veiculo:props.createVeiculo,produto:props.createProduto}" @sujo="formDirty=$event" @cancelar="closeNote" @salvar="saveNote"/>
            <div v-else-if="detalhes" class="sos-detail">
                <p v-if="['Finalizado','Concluido'].includes(detalhes.status) && etapas.some(e=>e.tipo==='final' && String(e.id)===String(detalhes.etapa_id))" class="sos-detail__finished" role="status"><i class="bi bi-check-circle-fill" /> Nota finalizada · última etapa operacional</p>
                <p v-else-if="['Finalizado','Concluido'].includes(detalhes.status)" class="sos-alert sos-alert--error" role="alert">Nota finalizada com etapa operacional divergente. Execute a auditoria de finalizadas.</p>
                <div class="sos-detail__main"><div><span class="sos-label">CLIENTE / CONTATO</span><h3>{{ detalhes.cliente }}</h3><div class="sos-contact"><span>{{ detalhes.telefone || 'Telefone não informado' }}</span><a v-if="detalhes.whatsapp" :href="detalhes.whatsapp" target="_blank" rel="noopener noreferrer"><i class="bi bi-whatsapp"/> WhatsApp</a></div></div>
                <div><span class="sos-label">STATUS / ETAPA</span><p><strong>{{ detalhes.status }}</strong> · {{ detalhes.etapa || 'Sem etapa' }}</p></div></div>
                <div class="sos-detail__advanced-link">
                    <p>Precisa de anexos, ajustes de estoque, lançamentos ou outras funções avançadas?</p>
                    <a :href="detalhes.urls.show_legado" class="sos-btn sos-btn--secondary" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right"/> Abrir atendimento completo</a>
                </div>
                <div class="sos-detail__grid"><div><small>Veículo</small><strong>{{ detalhes.veiculo || 'Não informado' }}</strong></div><div><small>Placa</small><strong>{{ detalhes.placa || '—' }}</strong></div><div><small>KM atual</small><strong>{{ detalhes.km ?? '—' }}</strong></div><div><small>Próxima troca</small><strong>{{ detalhes.km_proxima_troca_oleo ?? '—' }}</strong></div></div>
                <h3>Itens da Nota</h3><div class="sos-detail__items"><div v-for="i in detalhes.itens" :key="i.id"><div><strong>{{ i.itemable_type.endsWith('OrdemServico') ? `O.S. #${i.itemable_id}` : 'Peça' }}</strong><p>{{ i.descricao }}</p><small>{{ i.quantidade }} × {{ money(i.valor_unitario) }} <span v-if="i.desconto">· desconto {{ money(i.desconto) }}</span></small>
                    <small v-if="i.estoque_atual !== null && i.estoque_atual !== undefined" class="sos-detail__stock">Estoque atual: {{ numbers(i.estoque_atual) }} unidade(s)</small></div><strong>{{ money(i.valor_total) }}</strong></div></div>
                <p v-if="!detalhes.itens.length" class="sos-empty">Nenhum item.</p>
                <div class="sos-detail__total"><span>Total da Nota</span><strong>{{ money(detalhes.total) }}</strong></div>
                <section v-if="detalhes.conta_receber" class="sos-detail__finance" aria-label="Resumo financeiro da Nota">
                    <h3><i class="bi bi-wallet2"/> Conta a receber #{{ detalhes.conta_receber.id }}</h3>
                    <div class="sos-detail__finance-grid">
                        <div><small>Status informado pelo financeiro</small><strong>{{ detalhes.conta_receber.status }}</strong></div>
                        <div><small>Valor original</small><strong>{{ money(detalhes.conta_receber.valor_original) }}</strong></div>
                        <div><small>Recebido</small><strong>{{ money(detalhes.conta_receber.recebido) }}</strong></div>
                    </div>
                    <a v-if="detalhes.urls.conta" :href="detalhes.urls.conta" class="sos-btn sos-btn--secondary"><i class="bi bi-box-arrow-up-right"/> Gerenciar na Conta a Receber</a>
                    <p class="sos-field__hint">Valores informados pelo financeiro oficial; não realizar baixa ou ajuste neste painel.</p>
                </section>
                <section v-if="detalhes.conta_receber?.parcelas?.length" class="sos-detail__installments" aria-label="Parcelas da conta a receber">
                    <h3>Parcelas existentes</h3>
                    <div v-for="p in detalhes.conta_receber.parcelas" :key="p.numero" class="sos-detail__installment">
                        <strong>Parcela {{ p.numero }} · {{ money(p.valor) }}</strong>
                        <span>Vencimento: {{ formatarDataNota(p.data_vencimento) }}</span>
                    </div>
                    <p class="sos-field__hint">Consulta somente leitura. Para alterar valores, vencimentos ou recebimentos, utilize a Conta a Receber oficial.</p>
                </section>
                <NotaAnexoForm v-if="!finalizeOpen && props.attachmentsTemplate"
                    :key="'anexos-'+detalhes.id"
                    :nota-id="detalhes.id" :action-template="props.attachmentsTemplate"
                    :csrf-token="csrf()" :permitido="detalhes.status === 'Aberto' && detalhes.pode_editar"
                    :show-url="detalhes.urls.show_legado" :disabled="!!pending" />
                <section v-if="finalizeOpen" class="sos-finalize">
                    <h3>Confirmar finalização real</h3><p>A Nota só passa para a última etapa depois da confirmação, da validação de estoque, O.S. e financeiro e do registro da finalização.</p>
                    <template v-if="!detalhes.conta_receber"><label class="sos-field">Categoria de entrada<select v-model="categoria"><option value="">Selecione</option><option v-for="c in categorias" :key="c.id" :value="String(c.id)">{{ c.nome }}</option></select></label>
                    <div v-for="(p,index) in parcelas" :key="index" class="sos-parcela-row">
                        <label class="sos-field">Parcela #{{ index+1 }} · Valor (R$)<input v-model.number="p.valor" type="number" step="0.01" min="0.01" /></label>
                        <label class="sos-field">Vencimento<input v-model="p.data_vencimento" type="date" /></label>
                        <button type="button" class="sos-icon-button" :disabled="parcelas.length<=1" :aria-label="`Remover parcela ${index+1}`" @click="removerParcela(index)"><i class="bi bi-trash"/></button>
                    </div>
                    <button type="button" class="sos-btn sos-btn--secondary" @click="adicionarParcela"><i class="bi bi-plus-circle"/> Adicionar parcela</button>
                    <p :class="{'sos-parcela-error':Math.abs(somaParcelas-Number(detalhes.total))>.001}">Soma das parcelas: <strong>{{ money(somaParcelas) }}</strong> · Total esperado: {{ money(detalhes.total) }}</p></template>
                    <p v-else>Será utilizada a Conta a Receber já vinculada.</p>
                    <div class="sos-modal-actions"><button class="sos-btn sos-btn--quiet" type="button" :disabled="!!pending" @click="finalizeOpen=false">Voltar</button><button class="sos-btn sos-btn--primary" type="button" :disabled="!!pending || (!detalhes.conta_receber && (!categoria || !parcelas.length || parcelas.some(p => !p.data_vencimento || Number(p.valor)<=0) || Math.abs(somaParcelas-Number(detalhes.total))>.001))" @click="finishNote">Confirmar finalização</button></div>
                </section>
                <div v-if="pending" class="sos-pending" role="status"><span class="spinner-border spinner-border-sm"/> {{ pending }}</div>
                <footer v-if="!finalizeOpen" class="sos-modal-actions sos-modal-actions--wrap">
                    <a :href="detalhes.urls.pdf" target="_blank" rel="noopener noreferrer" class="sos-btn sos-btn--quiet"><i class="bi bi-file-earmark-pdf"/> Ver PDF</a>
                    <a :href="detalhes.urls.pdf_download" class="sos-btn sos-btn--quiet"><i class="bi bi-download"/> Baixar PDF</a>
                    <form class="sos-pdf-interno" method="POST" target="_blank" :action="detalhes.urls.pdf_interno" aria-label="PDF interno protegido por senha">
                        <input type="hidden" name="_token" :value="csrf()" />
                        <input type="password" name="senha" aria-label="Senha do PDF interno" placeholder="Senha do PDF interno" minlength="4" maxlength="64" autocomplete="new-password" required />
                        <button class="sos-btn sos-btn--quiet" type="submit"><i class="bi bi-file-earmark-lock"/> PDF interno</button>
                    </form>
                    <a v-if="detalhes.urls.conta" :href="detalhes.urls.conta" class="sos-btn sos-btn--quiet"><i class="bi bi-wallet2"/> Conta a receber</a>
                    <a :href="detalhes.urls.show_legado" class="sos-btn sos-btn--quiet" title="Recursos avançados ainda disponíveis durante a transição">Anexos e operações avançadas</a>
                    <button v-if="detalhes.pode_editar" class="sos-btn sos-btn--secondary" type="button" :disabled="!!pending" @click="editNote"><i class="bi bi-pencil"/> Editar</button>
                    <button v-if="detalhes.pode_finalizar" class="sos-btn sos-btn--primary" type="button" :disabled="!!pending" @click="openFinalizar"><i class="bi bi-check2-circle"/> Finalizar</button>
                    <button v-if="detalhes.pode_cancelar" class="sos-btn sos-btn--danger" type="button" :disabled="!!pending" @click="cancelNote">Cancelar Nota</button>
                    <button v-if="detalhes.pode_excluir" class="sos-btn sos-btn--danger" type="button" :disabled="!!pending" @click="deleteNote">Excluir</button>
                </footer>
            </div>
        </UiModal>
    </div>
</template>
