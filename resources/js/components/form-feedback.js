// Feedback acessivel apenas em formularios que optem por usa-lo.
export function nomeCampoHtml(chave) {
    const partes = String(chave).split('.');
    return partes.length < 2 ? String(chave) :
        partes[0] + partes.slice(1).map(p => `[${p}]`).join('');
}

export function inicializarFeedbackFormulario() {
    const form = document.getElementById('compra-form');
    if (!form) return;

    const payload = document.getElementById('compra-erros-json');
    if (payload) {
        let mensagens = {};
        try { mensagens = JSON.parse(payload.textContent || '{}'); }
        catch { mensagens = {}; }
        Object.entries(mensagens).forEach(([chave, lista]) => {
            let campo = form.elements.namedItem(nomeCampoHtml(chave));
            if (!campo || !campo.classList) return;
            // O campo real do produto e oculto: evidenciar a busca visivel.
            if (campo.type === 'hidden' && chave.endsWith('.produto_id')) {
                campo = campo.closest('.compra-item')?.querySelector('.item-produto-busca');
                if (!campo) return;
            }
            campo.classList.add('is-invalid');
            campo.setAttribute('aria-invalid', 'true');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback d-block feux-compra-erro-campo';
            feedback.setAttribute('role', 'alert');
            feedback.textContent = Array.isArray(lista) ? lista.join(' ') : String(lista);
            const ancora = campo.closest('.input-group') || campo;
            ancora.insertAdjacentElement('afterend', feedback);
        });
    }

    const submit = form.querySelector('button[type="submit"]');
    if (!submit) return;
    const rotulo = submit.innerHTML;
    let enviando = false;
    form.addEventListener('submit', () => {
        if (enviando) return;
        enviando = true;
        submit.disabled = true;
        submit.setAttribute('aria-busy', 'true');
        submit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span> Salvando...';
    });
    // Caso o usuario volte pelo historico, evitar botao preso em "Salvando".
    window.addEventListener('pageshow', () => {
        if (!enviando) return;
        enviando = false;
        submit.disabled = false;
        submit.removeAttribute('aria-busy');
        submit.innerHTML = rotulo;
    });
}
