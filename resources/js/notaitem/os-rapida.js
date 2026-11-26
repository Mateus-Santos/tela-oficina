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

        function salvar() {
            if (salvando) return;
            esconderAlerta();
            const cliente = clienteId();
            const veiculo = veiculoId();
            const setor = setorInput?.value || '';
            const descricao = descricaoInput?.value?.trim() || '';
            const valorTexto = String(valorInput?.value || '');
            const valor = Number(valorTexto.includes(',')
                ? valorTexto.replace(/\./g, '').replace(',', '.')
                : valorTexto);
            if (!cliente || !veiculo || !setor || !descricao
                || !Number.isFinite(valor) || valor < 0) {
                mostrarAlerta('Informe cliente, veiculo, setor, descricao e valor valido.');
                return;
            }
            salvando = true;
            salvarBotao.disabled = true;
            try {
                // ID temporario negativo: NENHUMA O.S. e gravada antes da Nota.
                const id = -(Date.now() + Math.floor(Math.random() * 1000));
                preencherBuilder({ id, descricao, valor });
                document.getElementById('btn-adicionar-item')?.click();
                const linha = Array.from(document.querySelectorAll(
                    '#container-itens-dinamicos tr[data-item-index]'
                )).find((tr) => tr.dataset.itemableId === String(id));
                if (!linha) {
                    throw new Error('Nao foi possivel adicionar o rascunho a Nota.');
                }
                const campo = document.createElement('input');
                campo.type = 'hidden';
                campo.name = `itens[${linha.dataset.itemIndex}][os_nova_setor_servico_id]`;
                campo.value = setor;
                linha.querySelector('td')?.appendChild(campo);
                limpar();
                fechar();
                // O backend persiste O.S. e item somente na transacao da Nota.
            } catch (error) {
                mostrarAlerta(error.message || 'Nao foi possivel adicionar O.S.');
            } finally {
                salvando = false;
                salvarBotao.disabled = false;
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
