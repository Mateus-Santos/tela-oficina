/**
 * FEUX / Devolução documental: prévia visual, sem cálculos financeiros e sem escrita.
 * O backend é a fonte de verdade de disponibilidade, estoque e abatimento.
 */
export function lerQuantidadeMilessimos(valor) {
    const texto = String(valor ?? '').trim().replace(',', '.');
    if (texto === '') return 0;
    if (!/^\d+(?:\.\d{1,3})?$/.test(texto)) return null;
    const [inteiro, decimal = ''] = texto.split('.');
    const milessimos = Number(inteiro) * 1000 + Number(decimal.padEnd(3, '0'));
    return Number.isSafeInteger(milessimos) ? milessimos : null;
}

export function formatarQuantidadeMilessimos(valor) {
    const inteiro = Math.floor(valor / 1000);
    return `${inteiro}.${String(valor % 1000).padStart(3, '0')}`;
}

/** Cada linha: { id, modalidade, quantidade, saldoEstoque, saldoRecusado }. */
export function resumirItensDevolucao(linhas) {
    const erros = [];
    let itens = 0;
    let milessimos = 0;
    for (const linha of linhas) {
        const quantidade = lerQuantidadeMilessimos(linha.quantidade);
        const saldo = lerQuantidadeMilessimos(
            linha.modalidade === 'recusado' ? linha.saldoRecusado : linha.saldoEstoque
        );
        let erro = '';
        if (!['recusado', 'estoque'].includes(linha.modalidade)) {
            erro = 'Tratamento inválido';
        } else if (quantidade === null || saldo === null) {
            erro = 'Quantidade ou saldo inválido';
        } else if (quantidade > saldo) {
            erro = `Quantidade supera o saldo disponível (${formatarQuantidadeMilessimos(saldo)})`;
        }
        if (erro) {
            erros.push({ id: linha.id, mensagem: erro });
        } else if (quantidade > 0) {
            itens += 1;
            milessimos += quantidade;
            if (!Number.isSafeInteger(milessimos)) {
                erros.push({ id: linha.id, mensagem: 'Quantidade total excede o limite numérico' });
            }
        }
    }
    return { itens, milessimos, erros, valido: erros.length === 0 && itens > 0 };
}

export function inicializarDevolucaoDocumental(raiz) {
    const formulario = raiz.querySelector('[data-feux-devolucao-documental]');
    if (!formulario) return false;
    const cards = Array.from(formulario.querySelectorAll('[data-feux-devolucao-item]'));
    const resumoEl = formulario.querySelector('[data-feux-devolucao-resumo]');
    const erroEl = formulario.querySelector('[data-feux-devolucao-erro]');
    const enviar = formulario.querySelector('[data-feux-submit-devolucao]');

    function atualizar() {
        const linhas = cards.map(card => {
            const modalidade = card.querySelector('[data-feux-modalidade]');
            const quantidade = card.querySelector('[data-feux-quantidade]');
            const saldoEstoque = card.dataset.saldoEstoque;
            const saldoRecusado = card.dataset.saldoRecusado;
            const limite = lerQuantidadeMilessimos(modalidade.value === 'recusado' ? saldoRecusado : saldoEstoque);
            // Limite no HTML é apenas orientação; a persistência valida novamente.
            if (limite !== null) quantidade.max = formatarQuantidadeMilessimos(limite);
            return {
                id: card.dataset.feuxItemId,
                modalidade: modalidade.value,
                quantidade: quantidade.disabled ? '0' : quantidade.value,
                saldoEstoque,
                saldoRecusado,
            };
        });
        const previa = resumirItensDevolucao(linhas);
        resumoEl.textContent = `${previa.itens} item(ns) · ${formatarQuantidadeMilessimos(previa.milessimos)} unidade(s)`;
        erroEl.textContent = previa.erros.length
            ? 'Revise a quantidade e o tratamento dos itens sinalizados antes de registrar.'
            : '';
        erroEl.classList.toggle('d-none', previa.erros.length === 0);
        for (const card of cards) {
            const aviso = card.querySelector('[data-feux-aviso-item]');
            const erro = previa.erros.find(item => item.id === card.dataset.feuxItemId);
            aviso.textContent = erro?.mensagem || '';
            aviso.classList.toggle('d-none', !erro);
        }
        enviar.disabled = !previa.valido;
    }
    formulario.addEventListener('input', atualizar);
    formulario.addEventListener('change', atualizar);
    atualizar();
    return true;
}

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => inicializarDevolucaoDocumental(document));
}
