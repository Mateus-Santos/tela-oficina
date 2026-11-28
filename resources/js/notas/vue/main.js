import { createApp } from 'vue';
import NotasApp from './NotasApp.vue';
const element = document.getElementById('sos-notas-vue');
if (element) createApp(NotasApp, {
    api: element.dataset.api,
    base: element.dataset.base,
    legacyCreate: element.dataset.createLegacy,
    attachmentsTemplate: element.dataset.attachmentsTemplate,
    createCliente: element.dataset.createCliente,
    createVeiculo: element.dataset.createVeiculo,
    createProduto: element.dataset.createProduto,
}).mount(element);
