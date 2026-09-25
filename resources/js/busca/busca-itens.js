export default class BuscaItens {
    constructor(config) {
        config = config || {};

        this.input =
            this.obterElemento(config.input);

        this.resultados =
            this.obterElemento(config.resultados);

        this.status =
            this.obterElemento(config.status);

        this.endpoint =
            config.endpoint || '';

        this.debounce =
            config.debounce || 350;

        this.minimoCaracteres =
            config.minimoCaracteres ?? 1;

        this.obterParametros =
            config.obterParametros ||
            function () {
                return {};
            };

        this.renderizarItem =
            config.renderizarItem ||
            function () {
                return '';
            };

        this.aoSelecionar =
            config.aoSelecionar ||
            function () {};

        this.aoBuscar =
            config.aoBuscar ||
            function () {};

        this.aoErro =
            config.aoErro ||
            function () {};

        this.controlador = null;
        this.timeout = null;
        this.inicializado = false;

        if (
            !this.input ||
            !this.resultados ||
            !this.endpoint
        ) {
            console.warn(
                '[SOS Mecânica] Configuração inválida do BuscaItens.'
            );

            return;
        }

        this.inicializar();
    }

    obterElemento(elemento) {
        if (typeof elemento === 'string') {
            return document.querySelector(
                elemento
            );
        }

        return elemento || null;
    }

    inicializar() {
        if (this.inicializado) {
            return;
        }

        this.inicializado = true;

        this.input.addEventListener(
            'input',
            () => {
                this.agendarBusca();
            }
        );

        this.input.addEventListener(
            'keydown',
            (event) => {
                if (event.key === 'Escape') {
                    this.limparResultados();
                }
            }
        );
    }

    agendarBusca() {
        if (this.timeout) {
            clearTimeout(this.timeout);
        }

        this.timeout = setTimeout(
            () => {
                this.timeout = null;
                this.buscar();
            },
            this.debounce
        );
    }

    async buscar() {
        const busca =
            this.input.value.trim();

        this.cancelarBuscaAnterior();

        if (
            busca.length <
            this.minimoCaracteres
        ) {
            this.limparResultados();

            this.definirStatus(
                busca === ''
                    ? 'Digite para pesquisar.'
                    : 'Digite mais caracteres para pesquisar.'
            );

            return;
        }

        const parametros =
            this.obterParametros(
                busca
            );

        if (
            !parametros ||
            typeof parametros !== 'object'
        ) {
            this.definirStatus(
                'Não foi possível preparar a busca.'
            );

            return;
        }

        this.controlador =
            new AbortController();

        this.definirStatus(
            'Buscando...'
        );

        try {
            const url =
                this.montarUrl(
                    parametros
                );

            const response =
                await fetch(
                    url,
                    {
                        method: 'GET',

                        headers: {
                            Accept:
                                'application/json'
                        },

                        signal:
                            this.controlador
                                .signal
                    }
                );

            if (!response.ok) {
                throw new Error(
                    'Erro HTTP ' +
                    response.status
                );
            }

            const data =
                await response.json();

            const itens =
                Array.isArray(data.data)
                    ? data.data
                    : [];

            this.renderizarResultados(
                itens
            );

            this.aoBuscar(
                itens,
                busca
            );

            if (itens.length === 0) {
                this.definirStatus(
                    'Nenhum resultado encontrado.'
                );
            } else {
                this.definirStatus(
                    itens.length +
                    ' resultado(s) encontrado(s).'
                );
            }
        } catch (error) {
            if (
                error.name ===
                'AbortError'
            ) {
                return;
            }

            console.error(
                '[SOS Mecânica] Erro ao realizar busca:',
                error
            );

            this.renderizarErro();

            this.definirStatus(
                'Erro ao consultar os resultados.'
            );

            this.aoErro(error);
        } finally {
            this.controlador = null;
        }
    }

    montarUrl(parametros) {
        const url =
            new URL(
                this.endpoint,
                window.location.origin
            );

        Object.keys(parametros).forEach(
            (chave) => {
                const valor =
                    parametros[chave];

                if (
                    valor !== null &&
                    typeof valor !==
                        'undefined' &&
                    valor !== ''
                ) {
                    url.searchParams.set(
                        chave,
                        valor
                    );
                }
            }
        );

        return url.toString();
    }

    renderizarResultados(itens) {
        this.resultados.innerHTML =
            '';

        if (
            !Array.isArray(itens) ||
            itens.length === 0
        ) {
            this.resultados.innerHTML = `
                <div class="list-group-item text-muted">
                    <i class="bi bi-search"></i>
                    Nenhum resultado encontrado.
                </div>
            `;

            return;
        }

        itens.forEach(
            (item) => {
                const botao =
                    document.createElement(
                        'button'
                    );

                botao.type =
                    'button';

                botao.className =
                    'list-group-item list-group-item-action';

                const conteudo =
                    this.renderizarItem(
                        item
                    );

                if (
                    conteudo instanceof Node
                ) {
                    botao.appendChild(
                        conteudo
                    );
                } else {
                    botao.innerHTML =
                        conteudo;
                }

                botao.addEventListener(
                    'click',
                    () => {
                        this.selecionar(
                            item
                        );
                    }
                );

                this.resultados.appendChild(
                    botao
                );
            }
        );
    }

    renderizarErro() {
        this.resultados.innerHTML = `
            <div class="list-group-item text-danger">
                <i class="bi bi-exclamation-triangle"></i>
                Não foi possível realizar a busca.
            </div>
        `;
    }

    selecionar(item) {
        this.aoSelecionar(item);

        this.limparResultados();

        this.definirStatus(
            'Item selecionado.'
        );
    }

    limparResultados() {
        this.resultados.innerHTML =
            '';
    }

    definirStatus(mensagem) {
        if (this.status) {
            this.status.textContent =
                mensagem;
        }
    }

    cancelarBuscaAnterior() {
        if (this.controlador) {
            this.controlador.abort();
            this.controlador = null;
        }
    }

    limpar() {
        if (this.timeout) {
            clearTimeout(this.timeout);
            this.timeout = null;
        }

        this.cancelarBuscaAnterior();

        this.input.value = '';

        this.limparResultados();

        this.definirStatus(
            'Digite para pesquisar.'
        );
    }

    destruir() {
        if (this.timeout) {
            clearTimeout(this.timeout);
            this.timeout = null;
        }

        this.cancelarBuscaAnterior();

        this.inicializado = false;
    }
}
