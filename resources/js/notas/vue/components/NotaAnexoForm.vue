<script setup>
import { ref, computed } from 'vue';
import { urlAnexoNota, validarArquivoNota } from '../validarAnexoNota.mjs';

const props = defineProps({
    notaId: { type: [Number, String], required: true },
    actionTemplate: { type: String, required: true },
    csrfToken: { type: String, required: true },
    permitido: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    showUrl: { type: String, required: true },
});
const tipo = ref('foto'), observacoes = ref(''), arquivoInput = ref(null), erro = ref('');
const tipos = [
    {v:'nf',l:'Nota fiscal'}, {v:'nf_xml',l:'XML'}, {v:'foto',l:'Foto'},
    {v:'comprovante',l:'Comprovante'}, {v:'boleto',l:'Boleto'},
    {v:'contrato',l:'Contrato'}, {v:'orcamento',l:'Orçamento'},
    {v:'conta_luz',l:'Conta de luz'}, {v:'conta_agua',l:'Conta de água'},
    {v:'conta_telefone',l:'Conta de telefone'}, {v:'recibo',l:'Recibo'},
    {v:'outro',l:'Outro'},
];
const action = computed(() => {
    try { return urlAnexoNota(props.actionTemplate, props.notaId); }
    catch { return ''; }
});
function validarEnvio(event) {
    erro.value = '';
    if (!props.permitido || props.disabled || !action.value || !props.csrfToken) {
        erro.value = 'Envio indisponível neste atendimento.';
    } else {
        erro.value = validarArquivoNota(arquivoInput.value?.files?.[0]);
    }
    if (erro.value) event.preventDefault();
}
</script>
<template>
    <section class="sos-note-attachments" aria-label="Documentos e anexos da Nota">
        <h3><i class="bi bi-paperclip"/> Documentos e anexos</h3>
        <p class="sos-note-attachments__disclaimer">A gestão de documentos usa o cadastro oficial da oficina. Arquivos são privados. Para consultar, editar ou excluir anexos, abra o atendimento completo.</p>
        <form v-if="permitido && action" :action="action" method="POST" enctype="multipart/form-data" target="_blank" rel="noopener" @submit="validarEnvio">
            <input type="hidden" name="_token" :value="csrfToken" />
            <div class="sos-note-attachments__fields">
                <label class="sos-field">Tipo de documento
                    <select name="tipo" v-model="tipo" :disabled="disabled" required>
                        <option v-for="t in tipos" :key="t.v" :value="t.v">{{ t.l }}</option>
                    </select>
                </label>
                <label class="sos-field">Arquivo (até 2 MiB)
                    <input ref="arquivoInput" type="file" name="arquivo" accept=".pdf,.jpg,.jpeg,.png,.webp,.xml" :disabled="disabled" required @change="erro=''" />
                </label>
                <label class="sos-field sos-field--wide">Observações
                    <textarea name="observacoes" v-model="observacoes" maxlength="1000" rows="2" :disabled="disabled"></textarea>
                </label>
            </div>
            <p v-if="erro" class="sos-alert sos-alert--error" role="alert">{{ erro }}</p>
            <div class="sos-note-attachments__actions">
                <button class="sos-btn sos-btn--secondary" type="submit" :disabled="disabled || !csrfToken"><i class="bi bi-upload"/> Enviar anexo</button>
                <a :href="showUrl" target="_blank" rel="noopener noreferrer" class="sos-btn sos-btn--quiet"><i class="bi bi-folder2-open"/> Ver / gerenciar anexos</a>
            </div>
            <p class="sos-field__hint">O resultado do envio abre em nova aba, na tela oficial, para mostrar confirmação e erros do servidor.</p>
        </form>
        <div v-else class="sos-note-attachments__actions">
            <p v-if="!permitido" class="sos-field__hint">Esta Nota está encerrada. Não é permitido incluir ou alterar anexos.</p>
            <a :href="showUrl" target="_blank" rel="noopener noreferrer" class="sos-btn sos-btn--secondary"><i class="bi bi-folder2-open"/> Consultar anexos</a>
        </div>
    </section>
</template>
