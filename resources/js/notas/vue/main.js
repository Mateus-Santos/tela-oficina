import { createApp } from 'vue';
import NotasApp from './NotasApp.vue';
const element = document.getElementById('sos-notas-vue');
if (element) createApp(NotasApp, {
    api: element.dataset.api,
    base: element.dataset.base,
}).mount(element);
