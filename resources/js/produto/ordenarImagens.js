function gerarChaveNovaImagem() {
    return `nova-${Date.now()}-${Math.random()
        .toString(36)
        .slice(2)}`;
}

export function inicializarOrdenacaoImagensProduto() {
    const input = document.getElementById('imagens');
    const container = document.getElementById(
        'produto-imagens-ordenaveis'
    );
    const ordemInput = document.getElementById(
        'ordem_imagens'
    );
    const contador = document.getElementById(
        'produto-imagens-contador'
    );

    if (!input || !container || !ordemInput) {
        return;
    }

    const maxImagens = Number(
        container.dataset.maxImagens || 8
    );

    const arquivosNovos = new Map();

    let elementoArrastado = null;

    function obterItens() {
        return Array.from(
            container.querySelectorAll(
                '.produto-imagem-ordenavel'
            )
        );
    }

    function atualizarPosicoes() {
        const itens = obterItens();

        itens.forEach((item, indice) => {
            const posicao = item.querySelector(
                '.produto-imagem-posicao'
            );

            if (!posicao) {
                return;
            }

            posicao.textContent = indice === 0
                ? 'Capa'
                : `#${indice + 1}`;
        });

        if (contador) {
            contador.textContent =
                `${itens.length}/${maxImagens} imagens cadastradas.`;
        }
    }

    function reconstruirFileList() {
        const transfer = new DataTransfer();

        const novosOrdenados = obterItens()
            .filter(item => item.dataset.novaChave)
            .map(item => ({
                item,
                chave: item.dataset.novaChave,
            }));

        novosOrdenados.forEach(({ item, chave }, indice) => {
            const arquivo = arquivosNovos.get(chave);

            if (!arquivo) {
                return;
            }

            transfer.items.add(arquivo);

            item.dataset.imagemToken = `nova:${indice}`;
        });

        input.files = transfer.files;
    }

    function atualizarOrdem() {
        reconstruirFileList();

        const ordem = obterItens()
            .map(item => item.dataset.imagemToken)
            .filter(Boolean);

        ordemInput.value = JSON.stringify(ordem);

        atualizarPosicoes();
    }

    function criarCardNovaImagem(
        arquivo,
        chave
    ) {
        const card = document.createElement('div');

        card.className =
            'produto-imagem-ordenavel position-relative border rounded bg-white p-2';

        card.draggable = true;
        card.dataset.novaChave = chave;
        card.style.cursor = 'grab';

        const grip = document.createElement('div');

        grip.className =
            'position-absolute top-0 start-0 m-1 badge text-bg-dark';

        grip.title = 'Arraste para alterar a posição';
        grip.style.zIndex = '2';

        grip.innerHTML =
            '<i class="bi bi-grip-vertical"></i>';

        const imagem = document.createElement('img');

        imagem.className = 'img-thumbnail d-block';
        imagem.alt = arquivo.name;
        imagem.draggable = false;

        Object.assign(imagem.style, {
            width: '120px',
            height: '120px',
            objectFit: 'contain',
        });

        const url = URL.createObjectURL(arquivo);

        imagem.src = url;

        imagem.addEventListener(
            'load',
            () => URL.revokeObjectURL(url),
            { once: true }
        );

        const remover = document.createElement('button');

        remover.type = 'button';
        remover.className =
            'btn btn-danger btn-sm position-absolute top-0 end-0 m-1';

        remover.title = 'Remover imagem';
        remover.style.zIndex = '2';

        remover.innerHTML =
            '<i class="bi bi-trash"></i>';

        remover.addEventListener('click', () => {
            arquivosNovos.delete(chave);
            card.remove();
            atualizarOrdem();
        });

        const posicao = document.createElement('div');

        posicao.className =
            'small text-center fw-bold text-dark mt-1 produto-imagem-posicao';

        card.appendChild(grip);
        card.appendChild(imagem);
        card.appendChild(remover);
        card.appendChild(posicao);

        return card;
    }

    input.addEventListener('change', () => {
        const selecionadas = Array.from(
            input.files || []
        );

        if (!selecionadas.length) {
            return;
        }

        const existentesAtuais = obterItens().length;
        const disponiveis = maxImagens - existentesAtuais;

        if (selecionadas.length > disponiveis) {
            alert(
                `Você pode adicionar no máximo mais ${disponiveis} imagem(ns).`
            );

            input.value = '';

            return;
        }

        selecionadas.forEach(arquivo => {
            const chave = gerarChaveNovaImagem();

            arquivosNovos.set(chave, arquivo);

            container.appendChild(
                criarCardNovaImagem(
                    arquivo,
                    chave
                )
            );
        });

        atualizarOrdem();
    });

    container.addEventListener(
        'dragstart',
        event => {
            const item = event.target.closest(
                '.produto-imagem-ordenavel'
            );

            if (!item) {
                return;
            }

            elementoArrastado = item;

            item.classList.add('opacity-50');

            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
            }
        }
    );

    container.addEventListener(
        'dragend',
        () => {
            if (elementoArrastado) {
                elementoArrastado.classList.remove(
                    'opacity-50'
                );
            }

            elementoArrastado = null;

            atualizarOrdem();
        }
    );

    container.addEventListener(
        'dragover',
        event => {
            event.preventDefault();

            if (!elementoArrastado) {
                return;
            }

            const alvo = event.target.closest(
                '.produto-imagem-ordenavel'
            );

            if (
                !alvo
                || alvo === elementoArrastado
            ) {
                return;
            }

            const rect = alvo.getBoundingClientRect();

            const depois =
                event.clientY > rect.top + rect.height / 2
                || (
                    Math.abs(
                        event.clientY
                        - (rect.top + rect.height / 2)
                    ) < rect.height / 3
                    && event.clientX
                        > rect.left + rect.width / 2
                );

            if (depois) {
                alvo.after(elementoArrastado);
            } else {
                alvo.before(elementoArrastado);
            }
        }
    );

    container.addEventListener(
        'drop',
        event => {
            event.preventDefault();

            atualizarOrdem();
        }
    );

    const observer = new MutationObserver(() => {
        atualizarOrdem();
    });

    observer.observe(container, {
        childList: true,
    });

    atualizarOrdem();
}
