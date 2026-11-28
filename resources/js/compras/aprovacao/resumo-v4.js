// FEUX V4: camada de orientação, nunca interfere no submit nem no backend.
export function orientacaoAprovacao(acao, haDivergencia = false) {
    if (haDivergencia) {
        return 'Há divergência física: aprove sem gerar conta e encaminhe o ajuste à conciliação D03.';
    }
    switch (acao) {
        case 'gerar':
            return 'Configure as parcelas. A conta a pagar só será solicitada ao confirmar a aprovação.';
        case 'confirmar-geracao':
            return 'A aprovação e a solicitação da conta serão validadas pelo Laravel.';
        case 'nao-gerar':
            return 'Aprovar sem gerar conta não cadastra parcelas nesta operação.';
        case 'voltar':
        default:
            return 'Escolha aprovar sem gerar conta ou configurar parcelas para gerar uma conta a pagar.';
    }
}

export default function inicializarResumoAprovacao() {
    const modal = document.querySelector('#modalAprovarCompra[data-feux-aprovacao-v4]');
    if (!modal) return;
    const resumo = modal.querySelector('[data-feux-aprovacao-resumo]');
    const orientacao = modal.querySelector('[data-feux-aprovacao-orientacao]');
    if (!resumo || !orientacao) return;
    const divergencia = resumo.dataset.divergencia === '1';
    modal.addEventListener('click', (evento) => {
        const botao = evento.target?.closest?.('[data-aprovacao]');
        if (!botao || !modal.contains(botao)) return;
        orientacao.textContent = orientacaoAprovacao(botao.dataset.aprovacao, divergencia);
    });
    modal.addEventListener('hidden.bs.modal', () => {
        orientacao.textContent = orientacaoAprovacao('voltar', divergencia);
    });
}
