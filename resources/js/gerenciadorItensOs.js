document.addEventListener('DOMContentLoaded', () => {

    // --- ELEMENTOS DE IDENTIFICAÇÃO DO VEÍCULO E PLACA ---
    const placaInput = document.getElementById('placa_input');
    const clienteNome = document.getElementById('cliente_nome');
    const idVeiculo = document.getElementById('veiculo_cliente_id');
    const clienteIdInput = document.getElementById('cliente_id');

    // --- ELEMENTOS DO CONSTRUTOR DE ITENS ---
    const typeSelect = document.getElementById('builder_type');
    const itemIdSelect = document.getElementById('builder_item_id');
    const valorUnitInput = document.getElementById('builder_valor_unitario');
    const descInput = document.getElementById('builder_descricao');
    const qtdInput = document.getElementById('builder_quantidade');
    const descontoInput = document.getElementById('builder_desconto');
    const garantiaInput = document.getElementById('builder_garantia');
    const btnAdicionar = document.getElementById('btn-adicionar-item');

    // --- ELEMENTO LOCAL OCULTO DE PRODUTOS ---
    const produtosEstaticosLocal = document.getElementById('produtos_estatiticos_local');

    // --- ELEMENTOS DA TABELA ---
    const containerItens = document.getElementById('container-itens-dinamicos');
    const linhaVazia = document.getElementById('linha-vazia');

    // --- ELEMENTOS DE DESCONTO E RESUMO GERAI ---
    const elDescontoGeral = document.getElementById('input-desconto-geral');
    const elTipoDesconto = document.getElementById('tipo-desconto-geral');

    let ordensServicoDoVeiculo = [];

    // Inicia o contador com base no número de itens já existentes na tabela (para edição)
    let itemIndex = containerItens ? containerItens.querySelectorAll('tr.item-row').length : 0;

    // --- 1. BUSCA POR PLACA ---
    if (placaInput) {
        placaInput.addEventListener('blur', async () => {
            let placa = placaInput.value.toUpperCase().replace(/[-\s]/g, '');
            if (!placa) return;

            ordensServicoDoVeiculo = [];

            try {
                const response = await fetch(`/api/veiculo/placa/${placa}`);

                if (!response.ok) {
                    clienteNome.value = '';
                    if (clienteIdInput) clienteIdInput.value = '';
                    if (idVeiculo) idVeiculo.value = '';
                    resetBuilder();
                    return;
                }

                const data = await response.json();

                if (clienteNome) clienteNome.value = data.cliente_nome || '';
                if (clienteIdInput) clienteIdInput.value = data.cliente_id || '';
                if (idVeiculo) idVeiculo.value = data.veiculo_id || '';
                ordensServicoDoVeiculo = data.ordens_servico || [];

                if (typeSelect && typeSelect.value === 'App\\Models\\OrdemServico') {
                    atualizarOpcoesItemRelacionado();
                }

            } catch (error) {
                console.error("Erro na busca por placa:", error);
                alert('Erro ao buscar dados do veículo. Verifique o console.');
            }
        });
    }

    // --- 2. ALTERNÂNCIA DE TIPOS POLIMÓRFICOS ---
    if (typeSelect) {
        typeSelect.addEventListener('change', () => {
            atualizarOpcoesItemRelacionado();
        });
    }

    function atualizarOpcoesItemRelacionado() {
        if (!typeSelect || !itemIdSelect) return;

        const tipo = typeSelect.value;
        itemIdSelect.innerHTML = '';
        itemIdSelect.disabled = true;
        if (valorUnitInput) valorUnitInput.value = '';
        if (descInput) descInput.value = '';

        if (!tipo) {
            itemIdSelect.innerHTML = '<option value="">Selecione o tipo primeiro</option>';
            return;
        }

        // Caso A: Vinculando Ordem de Serviço
        if (tipo === 'App\\Models\\OrdemServico') {
            if (ordensServicoDoVeiculo.length === 0) {
                itemIdSelect.innerHTML = '<option value="">Nenhuma O.S disponível para esta placa</option>';
                return;
            }

            itemIdSelect.innerHTML = '<option value="">Selecione qual O.S inserir...</option>';
            itemIdSelect.disabled = false;

            ordensServicoDoVeiculo.forEach(os => {
                const option = document.createElement('option');
                option.value = os.id;
                option.textContent = `OS #${os.id} - ${os.descricao}`;
                option.setAttribute('data-preco', os.valor || 0);
                itemIdSelect.appendChild(option);
            });
        }
        // Caso B: Adicionando Produto Físico (Autopeça)
        else if (tipo === 'App\\Models\\Produto') {
            if (!produtosEstaticosLocal || produtosEstaticosLocal.options.length === 0) {
                itemIdSelect.innerHTML = '<option value="">Nenhum produto cadastrado no sistema</option>';
                return;
            }

            itemIdSelect.innerHTML = '<option value="">Selecione um produto...</option>';
            itemIdSelect.disabled = false;

            Array.from(produtosEstaticosLocal.options).forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.textContent;
                option.setAttribute('data-preco', opt.getAttribute('data-preco') || 0);
                itemIdSelect.appendChild(option);
            });
        }
    }

    // Auto-preenchimento ao selecionar o item (Produto ou OS)
    if (itemIdSelect) {
        itemIdSelect.addEventListener('change', () => {
            const selectedOption = itemIdSelect.options[itemIdSelect.selectedIndex];
            if (selectedOption && selectedOption.value) {
                if (valorUnitInput) valorUnitInput.value = selectedOption.getAttribute('data-preco') || 0;
                if (descInput) descInput.value = selectedOption.textContent.trim();
            }
        });
    }

    // --- 3. ADICIONAR ITEM NA TABELA DINÂMICA ---
    if (btnAdicionar) {
        btnAdicionar.addEventListener('click', () => {
            if (!typeSelect.value || !itemIdSelect.value || !descInput.value.trim() || !valorUnitInput.value) {
                alert('Preencha os campos obrigatórios do item antes de inserir.');
                return;
            }

            const itemableType = typeSelect.value;
            const itemableId = itemIdSelect.value;
            const tipoText = itemableType.includes('Produto') ? 'Produto' : 'Serviço';
            const badgeColor = itemableType.includes('Produto') ? 'bg-info' : 'bg-warning';

            const descricao = descInput.value.trim();
            const quantidade = parseInt(qtdInput.value) || 1;
            const valorUnitario = parseFloat(valorUnitInput.value) || 0;
            const desconto = parseFloat(descontoInput.value) || 0;
            const garantiaDias = garantiaInput.value ? parseInt(garantiaInput.value) : '';

            const valorTotalItem = Math.max(0, (quantidade * valorUnitario) - desconto);

            if ((quantidade * valorUnitario) < desconto) {
                alert('O desconto não pode ser maior do que o valor total do item!');
                return;
            }

            if (linhaVazia) linhaVazia.style.display = 'none';

            const novaLinha = document.createElement('tr');
            novaLinha.classList.add('item-row');

            novaLinha.innerHTML = `
                <td>
                    <span class="badge ${badgeColor} text-dark">${tipoText}</span>
                    <input type="hidden" name="itens[${itemIndex}][itemable_type]" value="${itemableType}">
                    <input type="hidden" name="itens[${itemIndex}][itemable_id]" value="${itemableId}">
                </td>
                <td>
                    <input type="text" name="itens[${itemIndex}][descricao]" class="form-control form-control-sm input-desc" value="${descricao}" required>
                </td>
                <td>
                    <input type="number" name="itens[${itemIndex}][quantidade]" class="form-control form-control-sm input-qtd" value="${quantidade}" min="1" step="1" required>
                </td>
                <td>
                    <input type="number" name="itens[${itemIndex}][valor_unitario]" class="form-control form-control-sm input-vunit" value="${valorUnitario.toFixed(2)}" step="0.01" min="0" required>
                </td>
                <td>
                    <input type="number" name="itens[${itemIndex}][desconto]" class="form-control form-control-sm input-desc-val" value="${desconto.toFixed(2)}" step="0.01" min="0">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm input-vtotal fw-bold bg-light" value="${valorTotalItem.toFixed(2).replace('.', ',')}" readonly>
                </td>
                <td>
                    <input type="number" name="itens[${itemIndex}][garantia_dias]" class="form-control form-control-sm input-garantia" value="${garantiaDias}" min="0" placeholder="Dias">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger btn-remover-item" title="Remover Item">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;

            containerItens.appendChild(novaLinha);
            itemIndex++;

            resetBuilder();
            recalcularTotalGeral();
        });
    }

    // --- 4. REMOÇÃO DE ITEM E ESCUTA DE ALTERAÇÕES DENTRO DA TABELA ---
    if (containerItens) {
        containerItens.addEventListener('click', (e) => {
            const btnRemove = e.target.closest('.btn-remover-item');
            if (btnRemove) {
                btnRemove.closest('tr').remove();
                if (containerItens.querySelectorAll('tr.item-row').length === 0 && linhaVazia) {
                    linhaVazia.style.display = 'table-row';
                }
                recalcularTotalGeral();
            }
        });

        // Recalcular quando os valores dentro da tabela forem editados diretamente
        containerItens.addEventListener('input', (e) => {
            if (e.target.classList.contains('input-qtd') ||
                e.target.classList.contains('input-vunit') ||
                e.target.classList.contains('input-desc-val')) {

                const linha = e.target.closest('tr');
                if (linha) {
                    const elQtd = linha.querySelector('.input-qtd');
                    const elVunit = linha.querySelector('.input-vunit');
                    const elDescItem = linha.querySelector('.input-desc-val');
                    const elVtotal = linha.querySelector('.input-vtotal');

                    const qtd = elQtd ? parseFloat(elQtd.value) || 0 : 0;
                    const vunit = elVunit ? parseFloat(elVunit.value) || 0 : 0;
                    const descItem = elDescItem ? parseFloat(elDescItem.value) || 0 : 0;

                    const totalItem = Math.max(0, (qtd * vunit) - descItem);
                    if (elVtotal) {
                        elVtotal.value = totalItem.toFixed(2).replace('.', ',');
                    }
                }
                recalcularTotalGeral();
            }
        });
    }

    // --- 5. ESCUTADORES DO DESCONTO GERAL DA NOTA ---
    if (elDescontoGeral) {
        elDescontoGeral.addEventListener('input', recalcularTotalGeral);
    }
    if (elTipoDesconto) {
        elTipoDesconto.addEventListener('change', recalcularTotalGeral);
    }

    function resetBuilder() {
        if (typeSelect) typeSelect.value = '';
        if (itemIdSelect) {
            itemIdSelect.innerHTML = '<option value="">Selecione o tipo primeiro</option>';
            itemIdSelect.disabled = true;
        }
        if (descInput) descInput.value = '';
        if (qtdInput) qtdInput.value = '1';
        if (valorUnitInput) valorUnitInput.value = '';
        if (descontoInput) descontoInput.value = '0.00';
        if (garantiaInput) garantiaInput.value = '';
    }

    // Executa o cálculo inicial para preencher os resumos das notas já salvas
    recalcularTotalGeral();
});

// --- FUNÇÃO GLOBAL DE CÁLCULO FINANCEIRO DA NOTA ---
function recalcularTotalGeral() {
    let subtotal = 0;

    // Soma os totais das linhas (já com descontos individuais de item)
    document.querySelectorAll('.item-row').forEach(linha => {
        const elQtd = linha.querySelector('.input-qtd');
        const elVunit = linha.querySelector('.input-vunit');
        const elDescItem = linha.querySelector('.input-desc-val');

        const qtd = elQtd ? parseFloat(elQtd.value) || 0 : 0;
        const vunit = elVunit ? parseFloat(elVunit.value) || 0 : 0;
        const descItem = elDescItem ? parseFloat(elDescItem.value) || 0 : 0;

        subtotal += Math.max(0, (qtd * vunit) - descItem);
    });

    // Desconto Geral
    const elDescontoGeral = document.getElementById('input-desconto-geral');
    const elTipoDesconto = document.getElementById('tipo-desconto-geral');

    let valDescontoGeral = elDescontoGeral ? parseFloat(elDescontoGeral.value) || 0 : 0;
    let valorDescontoCalculado = valDescontoGeral;

    if (elTipoDesconto && elTipoDesconto.value === 'porcentagem') {
        valorDescontoCalculado = (subtotal * valDescontoGeral) / 100;
    }

    const totalFinal = Math.max(0, subtotal - valorDescontoCalculado);

    // Atualiza a tela
    const elResumoSubtotal = document.getElementById('resumo-subtotal');
    if (elResumoSubtotal) {
        elResumoSubtotal.innerText = 'R$ ' + subtotal.toFixed(2).replace('.', ',');
    }

    const elResumoDesconto = document.getElementById('resumo-desconto');
    if (elResumoDesconto) {
        elResumoDesconto.innerText = '- R$ ' + valorDescontoCalculado.toFixed(2).replace('.', ',');
    }

    const elTotalFinal = document.getElementById('valor-geral-os');
    if (elTotalFinal) {
        elTotalFinal.innerText = 'R$ ' + totalFinal.toFixed(2).replace('.', ',');
    }
}
