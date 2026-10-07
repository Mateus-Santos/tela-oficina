(function () {
    'use strict';

    function iniciar() {
        const tipoInput =
            document.getElementById(
                'builder_type'
            );

        const acoes =
            document.getElementById(
                'builder_produto_acoes'
            );

        const container =
            document.getElementById(
                'builder_produto_rapido'
            );

        const abrirBotao =
            document.getElementById(
                'btn-abrir-produto-rapido'
            );

        const fecharBotao =
            document.getElementById(
                'btn-fechar-produto-rapido'
            );

        const cancelarBotao =
            document.getElementById(
                'btn-cancelar-produto-rapido'
            );

        const salvarBotao =
            document.getElementById(
                'btn-salvar-produto-rapido'
            );

        const nomeInput =
            document.getElementById(
                'builder_produto_nome'
            );

        const marcaInput =
            document.getElementById(
                'builder_produto_marca'
            );

        const codigoFabricanteInput =
            document.getElementById(
                'builder_produto_codigo_fabricante'
            );

        const codigoBarrasInput =
            document.getElementById(
                'builder_produto_codigo_barras'
            );

        const descricaoInput =
            document.getElementById(
                'builder_produto_descricao'
            );

        const precoInput =
            document.getElementById(
                'builder_produto_preco'
            );

        const quantidadeInput =
            document.getElementById(
                'builder_produto_quantidade'
            );

        const estoqueMinimoInput =
            document.getElementById(
                'builder_produto_estoque_minimo'
            );

        const alertas =
            document.getElementById(
                'builder_produto_rapido_alertas'
            );

        if (
            !tipoInput
            || !acoes
            || !container
            || !abrirBotao
            || !salvarBotao
        ) {
            return;
        }

        let marcasCarregadas = false;
        let salvando = false;

        function clienteId() {
            return document
                .getElementById(
                    'cliente_id'
                )
                ?.value || '';
        }

        function veiculoClienteId() {
            return document
                .getElementById(
                    'veiculo_cliente_id'
                )
                ?.value || '';
        }

        function csrfToken() {
            return document
                .querySelector(
                    'meta[name="csrf-token"]'
                )
                ?.getAttribute(
                    'content'
                ) || '';
        }

        function esconderAlerta() {
            alertas.innerHTML = '';

            alertas.classList.add(
                'd-none'
            );
        }

        function mostrarAlerta(
            mensagem,
            tipo = 'danger'
        ) {
            alertas.innerHTML = `
                <div class="alert alert-${tipo} mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    ${mensagem}
                </div>
            `;

            alertas.classList.remove(
                'd-none'
            );
        }

        function atualizarAcoes() {
            const ehProduto =
                tipoInput.value === 'produto';

            acoes.classList.toggle(
                'd-none',
                !ehProduto
            );

            if (!ehProduto) {
                abrirBotao.disabled = false;
                abrirBotao.removeAttribute('title');
                fechar();
                return;
            }

            const clienteSelecionado =
                Boolean(clienteId());

            const veiculoSelecionado =
                Boolean(veiculoClienteId());

            abrirBotao.disabled =
                !clienteSelecionado
                || !veiculoSelecionado;

            if (!clienteSelecionado) {
                abrirBotao.title =
                    'Selecione um cliente antes de cadastrar um produto.';

                return;
            }

            if (!veiculoSelecionado) {
                abrirBotao.title =
                    'Selecione um veículo antes de cadastrar um produto.';

                return;
            }

            abrirBotao.removeAttribute('title');
        }

        function abrir() {
            esconderAlerta();

            if (!clienteId()) {
                container.classList.remove(
                    'd-none'
                );

                mostrarAlerta(
                    'Selecione um cliente na Nota antes de cadastrar o produto.'
                );

                return;
            }

            if (!veiculoClienteId()) {
                container.classList.remove(
                    'd-none'
                );

                mostrarAlerta(
                    'Selecione um veículo na Nota antes de cadastrar o produto.'
                );

                return;
            }

            container.classList.remove(
                'd-none'
            );

            carregarMarcas();

            nomeInput?.focus();
        }

        function fechar() {
            container.classList.add(
                'd-none'
            );

            esconderAlerta();
        }

        async function carregarMarcas() {
            if (
                marcasCarregadas
                || !marcaInput
            ) {
                return;
            }

            try {
                const response =
                    await fetch(
                        '/api/notas-itens/marcas-produto',
                        {
                            headers: {
                                Accept:
                                    'application/json',
                            },
                        }
                    );

                if (!response.ok) {
                    throw new Error(
                        'Não foi possível carregar as marcas.'
                    );
                }

                const resposta =
                    await response.json();

                const marcas =
                    Array.isArray(
                        resposta.data
                    )
                        ? resposta.data
                        : [];

                marcaInput.innerHTML =
                    '<option value="">Selecione...</option>';

                marcas.forEach(
                    function (marca) {
                        const option =
                            document
                                .createElement(
                                    'option'
                                );

                        option.value =
                            marca.id;

                        option.textContent =
                            marca.nome;

                        marcaInput.appendChild(
                            option
                        );
                    }
                );

                marcasCarregadas =
                    true;
            } catch (error) {
                console.error(
                    '[SOS Mecânica] Erro ao carregar marcas:',
                    error
                );

                mostrarAlerta(
                    error.message
                );
            }
        }

        function limpar() {
            if (nomeInput) {
                nomeInput.value = '';
            }

            if (marcaInput) {
                marcaInput.value = '';
            }

            if (codigoFabricanteInput) {
                codigoFabricanteInput.value =
                    '';
            }

            if (codigoBarrasInput) {
                codigoBarrasInput.value =
                    '';
            }

            if (descricaoInput) {
                descricaoInput.value =
                    '';
            }

            if (precoInput) {
                precoInput.value =
                    '0.00';
            }

            if (quantidadeInput) {
                quantidadeInput.value =
                    '0';
            }

            if (estoqueMinimoInput) {
                estoqueMinimoInput.value =
                    '0';
            }

            esconderAlerta();
        }

        function preencherBuilder(
            produto
        ) {
            const itemId =
                document.getElementById(
                    'builder_item_id'
                );

            const busca =
                document.getElementById(
                    'builder_item_busca'
                );

            const descricao =
                document.getElementById(
                    'builder_descricao'
                );

            const quantidade =
                document.getElementById(
                    'builder_quantidade'
                );

            const valor =
                document.getElementById(
                    'builder_valor_unitario'
                );

            const desconto =
                document.getElementById(
                    'builder_desconto'
                );

            const codigoWrapper =
                document.getElementById(
                    'builder_item_codigo_wrapper'
                );

            const codigo =
                document.getElementById(
                    'builder_item_codigo'
                );

            if (itemId) {
                itemId.value =
                    produto.id;
            }

            if (busca) {
                busca.value =
                    produto.nome;
            }

            if (descricao) {
                descricao.value =
                    produto.nome;
            }

            if (quantidade) {
                quantidade.value =
                    '1';
            }

            if (valor) {
                valor.value =
                    Number(
                        produto.preco_uni
                        || 0
                    ).toFixed(2);
            }

            if (desconto) {
                desconto.value =
                    '0.00';
            }

            if (codigo) {
                codigo.textContent =
                    produto.codigo
                    || '—';
            }

            if (
                codigoWrapper
                && produto.codigo
            ) {
                codigoWrapper
                    .classList
                    .remove(
                        'd-none'
                    );
            }
        }

        async function salvar() {
            if (salvando) {
                return;
            }

            esconderAlerta();

            const dados = {
                cliente_id:
                    clienteId(),

                veiculo_cliente_id:
                    veiculoClienteId(),

                nome:
                    nomeInput
                        ?.value
                        ?.trim()
                    || '',

                marca_id:
                    marcaInput?.value
                    || '',

                codigo_fabricante:
                    codigoFabricanteInput
                        ?.value
                        ?.trim()
                    || '',

                codigo_barras:
                    codigoBarrasInput
                        ?.value
                        ?.trim()
                    || null,

                descricao:
                    descricaoInput
                        ?.value
                        ?.trim()
                    || '',

                preco_uni:
                    precoInput?.value
                    || '',

                quantidade:
                    quantidadeInput?.value
                    || '0',

                estoque_minimo:
                    estoqueMinimoInput
                        ?.value
                    || '0',
            };

            if (!dados.cliente_id) {
                mostrarAlerta(
                    'Selecione o cliente da Nota.'
                );

                return;
            }

            if (
                !dados.veiculo_cliente_id
            ) {
                mostrarAlerta(
                    'Selecione o veículo da Nota.'
                );

                return;
            }

            if (!dados.nome) {
                mostrarAlerta(
                    'Informe o nome do produto.'
                );

                return;
            }

            if (!dados.marca_id) {
                mostrarAlerta(
                    'Selecione a marca do produto.'
                );

                return;
            }

            if (
                !dados.codigo_fabricante
            ) {
                mostrarAlerta(
                    'Informe o código do fabricante.'
                );

                return;
            }

            if (!dados.descricao) {
                mostrarAlerta(
                    'Informe a descrição do produto.'
                );

                return;
            }

            salvando = true;

            salvarBotao.disabled =
                true;

            salvarBotao.innerHTML =
                '<span class="spinner-border spinner-border-sm me-1"></span>Criando...';

            try {
                const response =
                    await fetch(
                        '/api/notas-itens/produtos',
                        {
                            method:
                                'POST',

                            headers: {
                                Accept:
                                    'application/json',

                                'Content-Type':
                                    'application/json',

                                'X-CSRF-TOKEN':
                                    csrfToken(),
                            },

                            body:
                                JSON.stringify(
                                    dados
                                ),
                        }
                    );

                const resposta =
                    await response.json();

                if (!response.ok) {
                    const erros =
                        resposta.errors
                        || {};

                    const primeiraMensagem =
                        Object
                            .values(erros)
                            .flat()
                            .at(0);

                    throw new Error(
                        primeiraMensagem
                        || resposta.message
                        || 'Não foi possível criar o produto.'
                    );
                }

                preencherBuilder(
                    resposta.data
                );

                limpar();
                fechar();

                document
                    .getElementById(
                        'btn-adicionar-item'
                    )
                    ?.click();
            } catch (error) {
                console.error(
                    '[SOS Mecânica] Erro ao cadastrar produto:',
                    error
                );

                mostrarAlerta(
                    error.message
                    || 'Não foi possível cadastrar o produto.'
                );
            } finally {
                salvando =
                    false;

                salvarBotao.disabled =
                    false;

                salvarBotao.innerHTML =
                    '<i class="bi bi-plus-circle"></i> Criar e adicionar à Nota';
            }
        }

        window.addEventListener(
            'nota-cliente-veiculo-atualizado',
            atualizarAcoes
        );

        tipoInput.addEventListener(
            'change',
            atualizarAcoes
        );

        abrirBotao.addEventListener(
            'click',
            abrir
        );

        fecharBotao?.addEventListener(
            'click',
            fechar
        );

        cancelarBotao?.addEventListener(
            'click',
            function () {
                limpar();
                fechar();
            }
        );

        salvarBotao.addEventListener(
            'click',
            salvar
        );

        atualizarAcoes();
    }

    if (
        document.readyState
        === 'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            iniciar,
            {
                once: true,
            }
        );
    } else {
        iniciar();
    }
})();
