// Puro, reaproveitavel e seguro com indices esparsos vindos do old('itens').
export function proximoIndiceDisponivel(indices) {
    return indices.reduce((proximo, atual) => {
        if (atual === '' || atual == null) return proximo;
        const numero = Number(atual);
        return Number.isSafeInteger(numero) && numero >= 0
            ? Math.max(proximo, numero + 1)
            : proximo;
    }, 0);
}
