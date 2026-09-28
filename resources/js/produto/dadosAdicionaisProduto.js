export function inicializarDadosAdicionaisProduto() {
    const dialog = document.getElementById(
        'dialog-dados-adicionais-produto'
    );

    const botaoAbrir = document.getElementById(
        'btn-dados-adicionais-produto'
    );

    const botaoFechar = document.getElementById(
        'btn-fechar-dados-adicionais-produto'
    );

    const botaoConcluir = document.getElementById(
        'btn-concluir-dados-adicionais-produto'
    );

    if (!dialog || !botaoAbrir) {
        return;
    }

    botaoAbrir.addEventListener('click', () => {
        if (!dialog.open) {
            dialog.showModal();
        }
    });

    botaoFechar?.addEventListener('click', () => {
        dialog.close();
    });

    botaoConcluir?.addEventListener('click', () => {
        dialog.close();
    });

    dialog.addEventListener('click', event => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
}
