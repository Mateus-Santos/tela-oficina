export function inicializarImportacaoAplicacoes() {
    const botao = document.getElementById('btn-importar-aplicacoes');

    if (!botao) {
        return;
    }

    botao.addEventListener('click', async () => {
        if (
            !navigator.clipboard
            || !navigator.clipboard.readText
        ) {
            alert('O navegador não permite acesso à área de transferência.');
            return;
        }

        const componente = botao.closest('[wire\\:id]');

        if (!componente) {
            alert('Não foi possível identificar o formulário de produtos.');
            return;
        }

        const wireId = componente.getAttribute('wire:id');

        if (!wireId) {
            alert('Não foi possível identificar o componente Livewire.');
            return;
        }

        try {
            botao.disabled = true;

            const texto = await navigator.clipboard.readText();

            if (!texto.trim()) {
                alert('A área de transferência está vazia.');
                return;
            }

            await Livewire.find(wireId).$call(
                'importarAplicacoes',
                texto
            );
        } catch (error) {
            console.error('Erro ao importar aplicações:', error);
            alert('Não foi possível ler os dados da área de transferência.');
        } finally {
            botao.disabled = false;
        }
    });
}
