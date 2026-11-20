/** Sprint S01: somente apresentacao. Valores financeiros sao revalidados no Laravel. */
const dinheiro = centavos => (centavos / 100).toLocaleString('pt-BR', {
    style: 'currency', currency: 'BRL'
});
const converter = valor => {
    if (!/^\d+(?:\.\d{1,2})?$/.test(String(valor ?? ''))) return NaN;
    const [inteiro, frac = ''] = String(valor).split('.');
    return Number(inteiro) * 100 + Number(frac.padEnd(2, '0'));
};
const moverMeses = (str, meses) => {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(str || '')) return '';
    const [ano, mes, dia] = str.split('-').map(Number);
    const data = new Date(Date.UTC(ano, mes - 1 + meses, 1));
    const ultimoDia = new Date(Date.UTC(data.getUTCFullYear(), data.getUTCMonth() + 1, 0)).getUTCDate();
    data.setUTCDate(Math.min(dia, ultimoDia));
    return data.toISOString().slice(0, 10);
};

function montarCadastro(form) {
    const alternar = form.querySelector('[data-personalizar-parcelas]');
    const painel = form.querySelector('[data-painel-parcelas]');
    const destino = form.querySelector('[data-linhas-parcelas]');
    const resumo = form.querySelector('[data-resumo-parcelas]');
    const qtd = form.querySelector('[name="quantidade_parcelas"]');
    const valor = form.querySelector('[name="valor"]');
    const venc = form.querySelector('[name="primeiro_vencimento"]');
    if (!alternar || !painel || !destino || !qtd || !valor || !venc) return;

    function conferir() {
        const total = converter(valor.value);
        const atuais = Array.from(destino.querySelectorAll('[data-valor-parcela]'));
        const soma = atuais.reduce((s, e) => s + converter(e.value), 0);
        const valido = Number.isSafeInteger(total) && total > 0 &&
            atuais.length === Number(qtd.value) && Number.isSafeInteger(soma) && soma === total;
        resumo.textContent = valido
            ? `Total das parcelas: ${dinheiro(soma)}. Valores conferidos.`
            : `Total das parcelas: ${Number.isFinite(soma) ? dinheiro(soma) : 'inválido'}. Deve ser igual ao valor da dívida.`;
        resumo.classList.toggle('text-danger', !valido);
        resumo.classList.toggle('text-success', valido);
        return valido;
    }
    function construir() {
        if (!alternar.checked) return;
        const quantidade = Number(qtd.value), total = converter(valor.value);
        destino.replaceChildren();
        if (!Number.isInteger(quantidade) || quantidade < 1 || quantidade > 120 ||
            !Number.isSafeInteger(total) || total < quantidade) {
            resumo.textContent = 'Informe valor e quantidade de parcelas válidos.';
            return;
        }
        const base = Math.floor(total / quantidade), resto = total % quantidade;
        for (let i = 0; i < quantidade; i++) {
            const linha = document.createElement('tr');
            const valorInicial = ((base + (i < resto ? 1 : 0)) / 100).toFixed(2);
            linha.innerHTML = `<td class="align-middle">#${i + 1}</td>
                <td><label class="visually-hidden" for="socio-parcela-valor-${i}">Valor parcela ${i + 1}</label>
                <input class="form-control form-control-sm" id="socio-parcela-valor-${i}"
                data-valor-parcela type="number" step="0.01" min="0.01" name="parcelas[${i}][valor]"
                value="${valorInicial}" required></td>
                <td><label class="visually-hidden" for="socio-parcela-data-${i}">Vencimento parcela ${i + 1}</label>
                <input class="form-control form-control-sm" id="socio-parcela-data-${i}"
                type="date" name="parcelas[${i}][data_vencimento]" required></td>`;
            linha.querySelector('input[type="date"]').value = moverMeses(venc.value, i);
            destino.append(linha);
        }
        conferir();
    }
    alternar.addEventListener('change', () => {
        painel.hidden = !alternar.checked;
        destino.querySelectorAll('input').forEach(e => { e.disabled = !alternar.checked; });
        if (alternar.checked) construir();
    });
    [qtd, valor, venc].forEach(e => e.addEventListener('change', construir));
    destino.addEventListener('input', conferir);
    destino.addEventListener('change', conferir);
    if (alternar.checked) {
        painel.hidden = false;
        construir();
    }
    form.addEventListener('submit', event => {
        if (alternar.checked && !conferir()) {
            event.preventDefault();
            resumo.setAttribute('role', 'alert');
            resumo.scrollIntoView({ block: 'center' });
        }
    });
}

function montarEdicao(form) {
    const valor = Number(form.dataset.valorContaCentavos || 0);
    const resumo = form.querySelector('[data-resumo-edicao]');
    if (!resumo) return;
    const conferir = () => {
        const inputs = Array.from(form.querySelectorAll('[data-valor-parcela-edit]'));
        const soma = inputs.reduce((s, el) => s + converter(el.value), 0);
        const valido = Number.isSafeInteger(soma) && soma === valor;
        resumo.textContent = `${Number.isFinite(soma) ? dinheiro(soma) : 'Valor inválido'} de ${dinheiro(valor)} — ${valido ? 'distribuição correta' : 'corrija a soma antes de salvar'}.`;
        resumo.classList.toggle('text-danger', !valido);
        resumo.classList.toggle('text-success', valido);
        return valido;
    };
    form.addEventListener('input', conferir);
    form.addEventListener('submit', event => {
        if (!conferir()) { event.preventDefault(); resumo.setAttribute('role', 'alert'); resumo.scrollIntoView({block:'center'}); }
    });
    conferir();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-socios-cadastro]').forEach(montarCadastro);
    document.querySelectorAll('[data-socios-edicao]').forEach(montarEdicao);
});
