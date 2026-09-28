export function inicializarExclusaoImagemProduto() {
    document.addEventListener('click', async event => {
        const botao = event.target.closest(
            '[data-excluir-imagem-produto]'
        );

        if (!botao) {
            return;
        }

        const confirmar = window.confirm(
            'Deseja excluir esta imagem do produto?'
        );

        if (!confirmar) {
            return;
        }

        const url = botao.dataset.url;
        const imagemId = botao.dataset.imagemId;

        const csrf = document.querySelector(
            'meta[name="csrf-token"]'
        )?.content;

        if (!url || !csrf) {
            alert('Não foi possível excluir a imagem.');
            return;
        }

        try {
            botao.disabled = true;

            const response = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error(
                    'Erro ao excluir imagem.'
                );
            }

            const imagem = document.querySelector(
                `[data-produto-imagem="${imagemId}"]`
            );

            imagem?.remove();

        } catch (error) {
            console.error(
                'Erro ao excluir imagem:',
                error
            );

            alert(
                'Não foi possível excluir a imagem.'
            );

            botao.disabled = false;
        }
    });
}
