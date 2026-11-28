// Adaptador puro para a resposta oficial de /api/notas-itens/buscar.
// Não atribui IDs de catálogo a Produtos operacionais.
export function ehReferenciaCatalogo(item) {
    return item?.tipo_resultado === 'referencia_catalogo'
        || item?.selecionavel_em_nota === false
        || (item?.catalogo_id != null && item?.id == null);
}
export function podeSelecionarProduto(item) {
    return !ehReferenciaCatalogo(item)
        && Number.isSafeInteger(Number(item?.id)) && Number(item.id) > 0;
}
export function precoProduto(item) {
    const n = item?.preco_jaime ?? item?.preco;
    if (n === null || n === undefined || n === '') return null;
    const valor = Number(n);
    return Number.isFinite(valor) && valor >= 0 ? valor : null;
}
export function statusEstoqueProduto(item) {
    if (ehReferenciaCatalogo(item)) return 'Referência de catálogo: cadastre ou vincule antes de usar';
    const estoque = item?.estoque_atual ?? item?.estoque;
    if (estoque === null || estoque === undefined || estoque === '') return 'Estoque não informado';
    const quantidade = Number(estoque);
    if (!Number.isFinite(quantidade)) return 'Estoque não informado';
    if (item?.disponivel === false || quantidade <= 0) return 'Sem estoque — finalização sujeita às regras do Laravel';
    const minimo = Number(item?.estoque_minimo);
    if (item?.estoque_minimo !== null && item?.estoque_minimo !== undefined
        && Number.isFinite(minimo) && quantidade <= minimo) return `Estoque baixo: ${quantidade} (mínimo ${minimo})`;
    return `Estoque disponível: ${quantidade}`;
}
export function urlInternaSegura(valor, origem) {
    if (!valor || !origem) return '';
    try {
        const base = new URL(origem);
        const url = new URL(valor, base);
        return ['http:', 'https:'].includes(url.protocol) && url.origin === base.origin ? url.href : '';
    } catch { return ''; }
}
