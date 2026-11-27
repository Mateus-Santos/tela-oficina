// FEUX D02 UX: prévia SEM persistência, sem alterar os contratos Laravel.
// Toda conferência efetiva é revalidada por ConferirCompra (backend).
export function milessimosPositivos(valor, obrigatorio = true) {
    const texto = String(valor ?? '').trim();
    if (!texto && !obrigatorio) return null;
    if (!/^(?:0|[1-9]\d{0,8})(?:\.\d{1,3})?$/.test(texto)) return NaN;
    const [parteInteira, parteDecimal = ''] = texto.split('.');
    const total = Number(parteInteira) * 1000 + Number(parteDecimal.padEnd(3, '0'));
    return Number.isSafeInteger(total) && total <= 999999999999 ? total : NaN;
}

export function avaliarLinhaConferencia({ nf, recebida, avariada, aceita, observacao }) {
    const previsto = milessimosPositivos(nf);
    const fisico = milessimosPositivos(recebida);
    const avaria = milessimosPositivos(avariada ?? '0');
    const aceiteDigitado = milessimosPositivos(aceita, false);
    const erros = [];
    if (!Number.isInteger(previsto)) erros.push(['nf', 'Quantidade da NF inválida.']);
    if (!Number.isInteger(fisico)) erros.push(['recebida', 'Informe uma quantidade recebida válida, inclusive zero.']);
    if (!Number.isInteger(avaria)) erros.push(['avariada', 'Quantidade avariada inválida.']);
    if (aceiteDigitado !== null && !Number.isInteger(aceiteDigitado)) erros.push(['aceita', 'Quantidade aceita inválida.']);
    if (erros.length) return { valido: false, divergente: false, erros, aceite: null };
    if (avaria > fisico) erros.push(['avariada', 'Avaria não pode superar o recebido.']);
    const limite = Math.min(previsto, Math.max(0, fisico - avaria));
    const aceiteFinal = aceiteDigitado === null ? limite : aceiteDigitado;
    if (aceiteFinal > limite) erros.push(['aceita', 'Aceite supera a NF ou o recebido sem avarias.']);
    const divergente = previsto !== fisico || avaria > 0 || aceiteFinal !== previsto;
    if (divergente && String(observacao ?? '').trim().length < 5) {
        erros.push(['observacao', 'Justifique a divergência com pelo menos 5 caracteres.']);
    }
    if (String(observacao ?? '').length > 2000) {
        erros.push(['observacao', 'Justificativa excede 2.000 caracteres.']);
    }
    return { valido: erros.length === 0, divergente, erros, aceite: aceiteFinal };
}

function campo(linha, sufixo) {
    return linha.querySelector(`input[name$="[${sufixo}]"], textarea[name$="[${sufixo}]"]`);
}

function limparAvisos(linha) {
    linha.querySelectorAll('[data-feux-conferencia-erro]').forEach(no => no.remove());
    ['quantidade_conferida', 'quantidade_avariada', 'quantidade_aceita', 'observacao_divergencia']
        .forEach(nome => {
            const input = campo(linha, nome);
            if (!input) return;
            input.classList.remove('is-invalid');
            input.removeAttribute('aria-invalid');
        });
}

export default function inicializarConferenciaCompra() {
    const formulario = document.querySelector('form[data-feux-conferencia-v3]');
    if (!formulario) return;
    const resumo = formulario.querySelector('[data-feux-conferencia-resumo]');
    const entradas = [...formulario.querySelectorAll('input[name$="[quantidade_conferida]"]')];
    if (!entradas.length) return;
    const linhas = entradas.map(entrada => entrada.closest('tr')).filter(Boolean);

    function atualizar(exibirErros = false) {
        let divergencias = 0;
        let incorretas = 0;
        let primeiroErro = null;
        linhas.forEach(linha => {
            limparAvisos(linha);
            const qtdNF = linha.querySelector('[data-feux-quantidade-nf]')?.dataset.feuxQuantidadeNf;
            const recebida = campo(linha, 'quantidade_conferida');
            const avariada = campo(linha, 'quantidade_avariada');
            const aceita = campo(linha, 'quantidade_aceita');
            const observacao = campo(linha, 'observacao_divergencia');
            if (!qtdNF || !recebida || !avariada || !aceita || !observacao) {
                incorretas += 1;
                if (!primeiroErro) primeiroErro = recebida;
                return;
            }
            const avaliacao = avaliarLinhaConferencia({
                nf: qtdNF, recebida: recebida.value, avariada: avariada.value,
                aceita: aceita.value, observacao: observacao.value,
            });
            if (avaliacao.divergente) divergencias += 1;
            if (!avaliacao.valido) incorretas += 1;
            if (exibirErros) {
                const nomes = { recebida, avariada, aceita, observacao };
                avaliacao.erros.forEach(([nome, mensagem]) => {
                    const input = nomes[nome] || recebida;
                    if (!input) return;
                    input.classList.add('is-invalid');
                    input.setAttribute('aria-invalid', 'true');
                    const msg = document.createElement('div');
                    msg.className = 'invalid-feedback d-block small';
                    msg.dataset.feuxConferenciaErro = '1';
                    msg.textContent = mensagem;
                    input.insertAdjacentElement('afterend', msg);
                    if (!primeiroErro) primeiroErro = input;
                });
            }
            linha.classList.toggle('feux-conferencia-v3--divergente', avaliacao.divergente);
        });
        if (resumo) {
            resumo.textContent = `${linhas.length} item(ns) · ${divergencias} com divergência · ${incorretas} com dados incompletos ou inválidos. O Laravel valida novamente ao salvar.`;
            resumo.classList.toggle('alert-warning', incorretas > 0);
            resumo.classList.toggle('alert-secondary', incorretas === 0);
        }
        return { ok: incorretas === 0, primeiroErro };
    }

    formulario.addEventListener('input', () => atualizar(false));
    formulario.addEventListener('change', () => atualizar(false));
    formulario.addEventListener('submit', evento => {
        const estado = atualizar(true);
        if (!estado.ok) {
            evento.preventDefault();
            estado.primeiroErro?.focus();
        }
    });
    atualizar(false);
}
