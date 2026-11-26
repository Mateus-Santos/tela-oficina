// L03K: reconciliação local, sem refetch geral quando a etapa muda.
// Puro e testável; recebe objetos Vue reativos sem depender da DOM.
export function reconciliarEtapaNota({ notas, etapas, metricas, notaId, novaEtapaId, filtroEtapaId = '' }) {
    const nota = notas.find(n => String(n.id) === String(notaId));
    const destino = etapas.find(e => String(e.id) === String(novaEtapaId));
    if (!nota || !destino) return { atualizada: false, revalidar: false };
    const anterior = etapas.find(e => String(e.id) === String(nota.etapa_id));
    const haviaFiltro = filtroEtapaId !== '' && filtroEtapaId != null;
    const etapaAnteriorFinal = anterior?.tipo === 'final';
    const etapaAtualFinal = destino.tipo === 'final';
    nota.etapa_id = destino.id;
    nota.etapa = destino.nome;
    if (!haviaFiltro && metricas && nota.status === 'Aberto' && etapaAnteriorFinal !== etapaAtualFinal) {
        metricas.etapa_final = Math.max(0, Number(metricas.etapa_final || 0) + (etapaAtualFinal ? 1 : -1));
    }
    // Com filtro por etapa, consultar totais e composição na API. Sem skeleton.
    return { atualizada: true, revalidar: haviaFiltro };
}
