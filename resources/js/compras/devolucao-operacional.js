/** FEUX Compras V2 / D01A: auxílio visual. Backend continua fonte de verdade. */
import { lerQuantidadeMilessimos, formatarQuantidadeMilessimos } from './devolucoes-documental.js';

export function resumirDevolucaoOperacional(linhas) {
    const erros = [];
    let itens = 0;
    let milessimos = 0;
    for (const linha of linhas) {
        const quantidade = lerQuantidadeMilessimos(linha.quantidade);
        const limite = lerQuantidadeMilessimos(linha.limite);
        let mensagem = '';
        if (quantidade === null || limite === null) mensagem = 'Quantidade ou saldo inválido';
        else if (quantidade > limite) mensagem = `Acima do limite disponível (${formatarQuantidadeMilessimos(limite)})`;
        if (mensagem) erros.push({ id: linha.id, mensagem });
        else if (quantidade > 0) {
            itens++;
            milessimos += quantidade;
            if (!Number.isSafeInteger(milessimos)) erros.push({ id: linha.id, mensagem: 'Quantidade total inválida' });
        }
    }
    return { itens, milessimos, erros, valido: erros.length === 0 && itens > 0 };
}

export function iniciarDevolucaoOperacional(raiz) {
    const form = raiz.querySelector('[data-feux-devolucao-operacional]');
    if (!form) return false;
    const cards = Array.from(form.querySelectorAll('[data-feux-item-operacional]'));
    const status = form.querySelector('[data-feux-resumo-operacional]');
    const global = form.querySelector('[data-feux-erro-global]');
    const botao = form.querySelector('[data-feux-submit-operacional]');
    if (!status || !global || !botao) return false;
    function atualizar() {
        const linhas = cards.map(card => {
            const input = card.querySelector('[data-feux-quantidade-operacional]');
            return { id: card.dataset.itemId, quantidade: input.disabled ? '0' : input.value,
                limite: card.dataset.limite };
        });
        const resumo = resumirDevolucaoOperacional(linhas);
        status.textContent = `${resumo.itens} item(ns) · ${formatarQuantidadeMilessimos(resumo.milessimos)} unidade(s)`;
        global.textContent = resumo.erros.length ? 'Revise as quantidades sinalizadas antes de enviar.' : '';
        global.classList.toggle('d-none', resumo.erros.length === 0);
        for (const card of cards) {
            const input = card.querySelector('[data-feux-quantidade-operacional]');
            const aviso = card.querySelector('[data-feux-erro-item]');
            const erro = resumo.erros.find(e => e.id === card.dataset.itemId);
            aviso.textContent = erro?.mensagem ?? '';
            aviso.classList.toggle('d-none', !erro);
            if (erro) input.setAttribute('aria-invalid', 'true');
            else input.removeAttribute('aria-invalid');
        }
        botao.disabled = !resumo.valido;
    }
    form.addEventListener('input', atualizar);
    form.addEventListener('change', atualizar);
    atualizar();
    return true;
}
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => iniciarDevolucaoOperacional(document));
}
