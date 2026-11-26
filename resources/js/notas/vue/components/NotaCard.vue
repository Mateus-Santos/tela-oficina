<script setup>
import { computed } from 'vue';
const props = defineProps({ nota: { type: Object, required: true }, etapas: { type: Array, default: () => [] }, loading: Boolean });
const emit = defineEmits(['abrir', 'mover']);
const money = v => Number(v ?? 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
const podeMover = computed(() => props.nota.status === 'Aberto');
function drag(event) {
    if (!podeMover.value || props.loading) { event.preventDefault(); return; }
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(props.nota.id));
}
</script>
<template>
    <article class="sos-note" :data-note-id="nota.id" :data-status="nota.status" :class="{ 'is-busy': loading, 'sos-note--cancelled': nota.status === 'Cancelado', 'sos-note--finished': ['Finalizado','Concluido'].includes(nota.status) }" :draggable="podeMover && !loading" @dragstart="drag" :aria-label="`Nota ${nota.id}`">
        <header class="sos-note__head">
            <strong>#{{ nota.id }}</strong>
            <span class="sos-tag" :class="`sos-tag--${nota.status === 'Aberto' ? 'open' : nota.status === 'Cancelado' ? 'cancelled' : 'green'}`">{{ nota.status === 'Concluido' ? 'Finalizado' : nota.status }}</span>
            <strong class="sos-note__total">{{ money(nota.total) }}</strong>
        </header>
        <h3>{{ nota.cliente }}</h3>
        <div class="sos-note__contact">
            <span v-if="nota.telefone">{{ nota.telefone }}</span><span v-else>Contato não informado</span>
            <a v-if="nota.whatsapp" :href="nota.whatsapp" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp do cliente" @click.stop><i class="bi bi-whatsapp"/></a>
        </div>
        <div class="sos-note__details">
            <span><i class="bi bi-car-front"/> {{ nota.veiculo || 'Sem veículo' }} <template v-if="nota.placa">· {{ nota.placa }}</template></span>
            <span><i class="bi bi-calendar3"/> {{ nota.criada_em || '—' }}</span>
        </div>
        <div class="sos-note__tags">
            <span v-if="nota.pecas">{{ nota.pecas }} peça(s)</span><span v-if="nota.servicos">{{ nota.servicos }} serviço(s)</span>
            <span v-if="nota.sem_estoque" class="sos-tag sos-tag--red">Estoque insuficiente</span>
        </div>
        <footer class="sos-note__foot">
            <button type="button" class="sos-link" @click="emit('abrir', nota.id)"><i class="bi bi-eye"/> Detalhes</button>
            <label v-if="podeMover" class="sos-stage-select"><span class="visually-hidden">Etapa da nota</span>
                <select :value="nota.etapa_id || ''" :disabled="loading" @change="emit('mover', nota, $event.target.value); $event.target.value = String(nota.etapa_id || '')">
                    <option v-if="!nota.etapa_id" value="">Sem etapa</option>
                    <option v-for="e in etapas" :key="e.id" :value="e.id">{{ e.nome }}</option>
                </select>
            </label>
            <span v-else class="sos-note__closed">{{ ['Finalizado','Concluido'].includes(nota.status) ? (etapas.some(e=>e.tipo==='final' && String(e.id)===String(nota.etapa_id)) ? 'Finalizada · etapa final' : 'Finalizada · revisar etapa') : 'Encerrada' }}</span>
        </footer>
        <span v-if="loading" class="sos-note__spinner" role="status"><span class="spinner-border spinner-border-sm"/> Salvando…</span>
    </article>
</template>
