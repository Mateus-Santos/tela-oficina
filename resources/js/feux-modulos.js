// Melhoria progressiva sem alterar o envio nativo, CSRF ou regras de validacao.
function prepararFeedback() {
    document.querySelectorAll('.cadastro.feux-moderno').forEach((area) => {
        const invalido = area.querySelector('.is-invalid:not([type="hidden"]), [aria-invalid="true"]:not([type="hidden"])');
        if (invalido instanceof HTMLElement && !invalido.closest('[hidden]')) {
            invalido.setAttribute('aria-invalid', 'true');
            if (invalido.getClientRects().length) {
                invalido.focus({ preventScroll: true });
                invalido.scrollIntoView({ block: 'center', behavior: 'auto' });
            }
        } else {
            const aviso = area.querySelector('.alert-danger');
            if (aviso instanceof HTMLElement) {
                aviso.setAttribute('role', 'alert');
                aviso.setAttribute('tabindex', '-1');
            }
        }
        area.querySelectorAll('form[data-feux-form="true"]').forEach((form) => {
            const submit = form.querySelector('button[type="submit"]');
            if (!(submit instanceof HTMLButtonElement)) return;
            let enviando = false;
            const etiqueta = submit.innerHTML;
            const restaurar = () => {
                if (!enviando) return;
                enviando = false;
                submit.removeAttribute('aria-disabled');
                submit.removeAttribute('aria-busy');
                submit.innerHTML = etiqueta;
            };
            form.addEventListener('submit', (event) => {
                // Eventos cancelados por validadores existentes nao ativam o estado de envio.
                if (event.defaultPrevented || !form.checkValidity()) return;
                if (enviando) { event.preventDefault(); return; }
                enviando = true;
                // Mantem o botao habilitado para nao omitir seu name/value no POST.
                submit.setAttribute('aria-disabled', 'true');
                submit.setAttribute('aria-busy', 'true');
                submit.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm feux-enviando-spinner" aria-hidden="true"></span>');
            });
            window.addEventListener('pageshow', restaurar);
        });
    });
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', prepararFeedback, { once: true });
} else {
    prepararFeedback();
}
