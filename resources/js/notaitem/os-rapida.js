(function () {
    'use strict';

    function iniciar() {
        const tipoInput =
            document.getElementById(
                'builder_type'
            );

        const acoes =
            document.getElementById(
                'builder_os_acoes'
            );

        const container =
            document.getElementById(
                'builder_os_rapida'
            );

        const abrirBotao =
            document.getElementById(
                'btn-abrir-os-rapida'
            );

        const fecharBotao =
            document.getElementById(
                'btn-fechar-os-rapida'
            );

        const cancelarBotao =
            document.getElementById(
                'btn-cancelar-os-rapida'
            );

        const salvarBotao =
            document.getElementById(
                'btn-salvar-os-rapida'
            );

        const setorInput =
            document.getElementById(
                'builder_os_setor'
            );

        const descricaoInput =
            document.getElementById(
                'builder_os_descricao'
            );

        const valorInput =
            document.getElementById(
                'builder_os_valor'
            );

        const alertas =
            document.getElementById(
                'builder_os_rapida_alertas'
            );

        if (
            !tipoInput
            || !container
            || !abrirBotao
            || !salvarBotao
        ) {
            return;
        }

        let setoresCarregados = false;
        let salvando = false;

        function clienteId() {
            return document
                .getElementById(
                    'cliente_id'
                )
                ?.value || '';
        }

        function veiculoId() {
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
            if (!alertas) {
                return;
            }

            alertas.innerHTML = '';
            alertas.classList.add(
                'd-none'
            );
        }

        function mostrarAlerta(
            mensagem,
            tipo = 'danger'
        ) {
            if (!alertas) {
                return;
            }

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
            const ehOs =
                tipoInput.value === 'os';

            acoes?.classList.toggle(
                'd-none',
                !ehOs
            );

            if (!ehOs) {
                fechar();
            }
        }

        function abrir() {
            esconderAlerta();

            if (!clienteId()) {
                mostrarAlerta(
                    'Selecione um cliente na Nota antes de criar uma O.S.'
                );

                container.classList.remove(
                    'd-none'
                );

                return;
            }

            if (!veiculoId()) {
                mostrarAlerta(
                    'Selecione um veículo na Nota antes de criar uma O.S.'
                );

                container.classList.remove(
                    'd-none'
                );

                return;
            }

            container.classList.remove(
                'd-none'
            );

            carregarSetores();
        }

        function fechar() {
            container.classList.add(
                'd-none'
            );

            esconderAlerta();
        }

        async function carregarSetores() {
            if (
                setoresCarregados
                || !setorInput
            ) {
                return;
            }

            try {
                const response =
                    await fetch(
                        '/api/notas-itens/setores-servico',
                        {
                            headers: {
                                Accept:
                                    'application/json',
                            },
                        }
                    );

                if (!response.ok) {
                    throw new Error(
                        'Falha ao carregar setores.'
                    );
                }

                const resposta =
                    await response.json();

                const setores =
                    Array.isArray(
                        resposta.data
                    )
                        ? resposta.data
                        : [];

                setorInput.innerHTML =
                    '<option value="">Selecione...</option>';

                setores.forEach(
                    function (setor) {
                        const option =
                            document
                                .createElement(
                                    'option'
                                );

                        option.value =
                            setor.id;

                        option.textContent =
                            setor.setor;

                        setorInput.appendChild(
                            option
                        );
                    }
                );

                setoresCarregados =
                    true;
            } catch (error) {
                console.error(
                    '[SOS Mecânica] Erro ao carregar setores:',
                    error
                );

                mostrarAlerta(
                    'Não foi possível carregar os setores de serviço.'
                );
            }
        }

        function limpar() {
            if (setorInput) {
                setorInput.value = '';
            }

            if (descricaoInput) {
                descricaoInput.value = '';
            }

            if (valorInput) {
                valorInput.value =
                    '0.00';
            }

            esconderAlerta();
        }

        function preencherBuilder(
            ordemServico
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

            if (itemId) {
                itemId.value =
                    ordemServico.id;
            }

            if (busca) {
                busca.value =
                    `O.S. #${ordemServico.id} - ${ordemServico.descricao}`;
            }

            if (descricao) {
                descricao.value =
                    ordemServico.descricao;
            }

            if (quantidade) {
                quantidade.value =
                    '1';
            }

            if (valor) {
                valor.value =
                    Number(
                        ordemServico.valor || 0
                    ).toFixed(2);
            }

            if (desconto) {
                desconto.value =
                    '0.00';
            }
        }

        async function salvar() {
            if (salvando) {
                return;
            }

            esconderAlerta();

            const cliente =
                clienteId();

            const veiculo =
                veiculoId();

            const setor =
                setorInput?.value || '';

            const descricao =
                descricaoInput
                    ?.value
                    ?.trim() || '';

            const valor =
                valorInput?.value || '';

            if (!cliente) {
                mostrarAlerta(
                    'Selecione o cliente da Nota.'
                );

                return;
            }

            if (!veiculo) {
                mostrarAlerta(
                    'Selecione o veículo da Nota.'
                );

                return;
            }

            if (!setor) {
                mostrarAlerta(
                    'Selecione o setor de serviço.'
                );

                return;
            }

            if (!descricao) {
                mostrarAlerta(
                    'Informe a descrição da O.S.'
                );

                return;
            }

            salvando = true;

            salvarBotao.disabled = true;

            salvarBotao.innerHTML =
                '<span class="spinner-border spinner-border-sm me-1"></span>Criando...';

            try {
                const response =
                    await fetch(
                        '/api/notas-itens/ordens-servico',
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
                                JSON.stringify({
                                    cliente_id:
                                        cliente,

                                    veiculo_cliente_id:
                                        veiculo,

                                    setor_servico_id:
                                        setor,

                                    descricao,

                                    valor,
                                }),
                        }
                    );

                const resposta =
                    await response.json();

                if (!response.ok) {
                    const erros =
                        resposta.errors || {};

                    const primeiraMensagem =
                        Object
                            .values(erros)
                            .flat()
                            .at(0);

                    throw new Error(
                        primeiraMensagem
                        || resposta.message
                        || 'Não foi possível criar a O.S.'
                    );
                }

                const ordemServico =
                    resposta.data;

                preencherBuilder(
                    ordemServico
                );

                limpar();
                fechar();

                /*
                 * O builder já possui toda a O.S.
                 * preenchida. Agora utilizamos o mesmo
                 * botão de inclusão já existente.
                 */
                document
                    .getElementById(
                        'btn-adicionar-item'
                    )
                    ?.click();
            } catch (error) {
                console.error(
                    '[SOS Mecânica] Erro ao criar O.S.:',
                    error
                );

                mostrarAlerta(
                    error.message
                    || 'Não foi possível criar a O.S.'
                );
            } finally {
                salvando = false;

                salvarBotao.disabled =
                    false;

                salvarBotao.innerHTML =
                    '<i class="bi bi-plus-circle"></i> Criar e adicionar à Nota';
            }
        }

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
