const MAX_ANEXO_BYTES = 2 * 1024 * 1024;
const EXTENSOES = new Set(['pdf', 'jpg', 'jpeg', 'png', 'webp', 'xml']);

// Apenas validacao de interface; StoreAnexoRequest Laravel e a fonte da verdade.
export function validarArquivoNota(arquivo) {
    if (!arquivo) return 'Selecione um arquivo.';
    const tamanho = Number(arquivo.size);
    if (!Number.isFinite(tamanho) || tamanho <= 0) return 'Arquivo vazio ou inválido.';
    if (tamanho > MAX_ANEXO_BYTES) return 'Arquivo maior que 2 MiB.';
    const extensao = String(arquivo.name || '').split('.').pop().toLowerCase();
    if (!EXTENSOES.has(extensao)) return 'Formato não permitido: use PDF, JPG, PNG, WEBP ou XML.';
    return '';
}

export function urlAnexoNota(template, id) {
    const notaId = String(id);
    if (!/^[1-9]\d*$/.test(notaId)) throw new Error('Identificador da Nota inválido.');
    if (typeof template !== 'string' || template.split('__NOTA_ID__').length !== 2) {
        throw new Error('Rota de anexo não configurada.');
    }
    return template.replace('__NOTA_ID__', notaId);
}

export function formatarDataNota(valor) {
    if (!valor) return 'Não informado';
    const texto = String(valor);
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(texto);
    if (!m) return texto;
    const ano = Number(m[1]), mes = Number(m[2]), dia = Number(m[3]);
    const data = new Date(Date.UTC(ano, mes - 1, dia));
    if (data.getUTCFullYear() !== ano || data.getUTCMonth() !== mes - 1 || data.getUTCDate() !== dia) return texto;
    return `${m[3]}/${m[2]}/${m[1]}`;
}
