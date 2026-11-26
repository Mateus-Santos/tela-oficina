<script setup>
import { watch, nextTick, ref, onBeforeUnmount } from 'vue';
const props = defineProps({ open: Boolean, title: { type: String, default: 'Detalhes' }, wide: Boolean });
const emit = defineEmits(['close']);
const dialog = ref(null);
let trigger = null;
const onClose = () => emit('close');
const onBackdrop = (event) => { if (event.target === dialog.value) emit('close'); };
watch(() => props.open, async value => {
    if (value) trigger = document.activeElement;
    await nextTick();
    if (value && dialog.value && !dialog.value.open) {
        dialog.value.showModal();
        dialog.value.querySelector('.sos-icon-button')?.focus();
    }
    if (!value && dialog.value?.open) dialog.value.close();
    if (!value && trigger?.isConnected) {
        trigger.focus();
        trigger = null;
    }
}, { immediate: true });
onBeforeUnmount(() => { if (dialog.value?.open) dialog.value.close(); });
</script>
<template>
    <dialog ref="dialog" class="sos-modal" :class="{ 'sos-modal--wide': wide }" :aria-label="title" @cancel.prevent="onClose" @click="onBackdrop" @close="onClose">
        <div class="sos-modal__head">
            <div><span class="sos-label">S.O.S MECÂNICA · CENTRAL</span><h2>{{ title }}</h2></div>
            <button type="button" class="sos-icon-button" aria-label="Fechar janela" @click="emit('close')"><i class="bi bi-x-lg"/></button>
        </div>
        <div class="sos-modal__body"><slot/></div>
    </dialog>
</template>
