export default function inicializarAnexosCompra() {
    const anexosContainer =
        document.getElementById('anexos-container');

    const btnAdicionarAnexo =
        document.getElementById('btn-adicionar-anexo');

    if (!anexosContainer || !btnAdicionarAnexo) {
        return;
    }

    function atualizarIndices() {
        anexosContainer
            .querySelectorAll('.anexo-item')
            .forEach(function (item, index) {
                const tipo =
                    item.querySelector('[name$="[tipo]"]');

                const arquivo =
                    item.querySelector('[name$="[arquivo]"]');

                const observacoes =
                    item.querySelector('[name$="[observacoes]"]');

                if (tipo) {
                    tipo.name =
                        `anexos[${index}][tipo]`;
                }

                if (arquivo) {
                    arquivo.name =
                        `anexos[${index}][arquivo]`;
                }

                if (observacoes) {
                    observacoes.name =
                        `anexos[${index}][observacoes]`;
                }
            });
    }

    function limpar(item) {
        const tipo =
            item.querySelector('select');

        const arquivo =
            item.querySelector('input[type="file"]');

        const observacoes =
            item.querySelector('input[type="text"]');

        if (tipo) {
            tipo.value = '';
        }

        if (arquivo) {
            arquivo.value = '';
        }

        if (observacoes) {
            observacoes.value = '';
        }
    }

    btnAdicionarAnexo.addEventListener(
        'click',
        function () {
            const modelo =
                anexosContainer.querySelector('.anexo-item');

            if (!modelo) {
                return;
            }

            const novoAnexo =
                modelo.cloneNode(true);

            limpar(novoAnexo);

            anexosContainer.appendChild(
                novoAnexo
            );

            atualizarIndices();
        }
    );

    anexosContainer.addEventListener(
        'click',
        function (event) {
            const botao =
                event.target.closest('.btn-remover-anexo');

            if (!botao) {
                return;
            }

            const item =
                botao.closest('.anexo-item');

            if (!item) {
                return;
            }

            const itens =
                anexosContainer.querySelectorAll('.anexo-item');

            if (itens.length === 1) {
                limpar(item);
                return;
            }

            item.remove();

            atualizarIndices();
        }
    );

    atualizarIndices();
}
