<script setup>
import { ref, watch, nextTick, onBeforeUnmount } from 'vue';
const props = defineProps({
    open: Boolean,
    filtros: { type: Object, required: true },
    etapas: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] },
});
const emit = defineEmits(['fechar', 'aplicar', 'limpar']);
const aside = ref(null);
let trigger = null;
const selector = 'button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
function keydown(event) {
    if (event.key === 'Escape') { event.preventDefault(); emit('fechar'); return; }
    if (event.key !== 'Tab' || !aside.value) return;
    const nodes = Array.from(aside.value.querySelectorAll(selector));
    if (!nodes.length) return;
    const first = nodes[0], last = nodes[nodes.length - 1];
    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault(); last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault(); first.focus();
    }
}
watch(() => props.open, async open => {
    if (open) {
        trigger = document.activeElement;
        await nextTick();
        aside.value?.querySelector(selector)?.focus();
    } else if (trigger?.isConnected) {
        await nextTick();
        trigger.focus();
        trigger = null;
    }
}, { immediate: true });
onBeforeUnmount(() => { if (trigger?.isConnected) trigger.focus(); });
</script>
<template>
    <div v-if="open" class="sos-drawer-overlay" @click.self="emit('fechar')">
        <aside ref="aside" class="sos-drawer" role="dialog" aria-modal="true" aria-label="Filtros avançados" @keydown="keydown">
            <header>
                <div><span class="sos-label">CONSULTA AVANÇADA</span><h2>Filtros</h2></div>
                <button class="sos-icon-button" type="button" aria-label="Fechar filtros" @click="emit('fechar')"><i class="bi bi-x-lg"/></button>
            </header>
            <label class="sos-field">Cliente <input v-model="filtros.cliente" maxlength="120" placeholder="Nome do cliente"/></label>
            <label class="sos-field">Placa <input v-model="filtros.placa" maxlength="20" placeholder="ABC1D23"/></label>
            <label class="sos-field">Etapa operacional<select v-model="filtros.etapa_id"><option value="">Todas</option><option v-for="e in etapas" :key="e.id" :value="String(e.id)">{{ e.nome }}</option></select></label>
            <label class="sos-field">Tipo de item<select v-model="filtros.item_tipo"><option v-for="t in types" :key="t.v" :value="t.v">{{ t.l }}</option></select></label>
            <footer><button type="button" class="sos-btn sos-btn--quiet" @click="emit('limpar')">Limpar</button><button type="button" class="sos-btn sos-btn--primary" @click="emit('aplicar')">Aplicar filtros</button></footer>
        </aside>
    </div>
</template>
