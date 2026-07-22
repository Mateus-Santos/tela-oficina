document.addEventListener('DOMContentLoaded', () => {

    // --- ELEMENTOS DE IDENTIFICAÇÃO DO VEÍCULO E PLACA ---
    const placaInput = document.getElementById('placa_input');
    const clienteNome = document.getElementById('cliente_nome');
    const idVeiculo = document.getElementById('veiculo_cliente_id');
    const clienteIdInput = document.getElementById('cliente_id');
    // CORREÇÃO: Removido o osSelect que não existe mais no HTML

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
    const valorGeralOsEl = document.getElementById('valor-geral-os');

    let ordensServicoDoVeiculo = [];
    let itemIndex = 0;

    if (!placaInput) return;

    // 1. BUSCA POR PLACA
    placaInput.addEventListener('blur', async () => {
        let placa = placaInput.value.toUpperCase().replace(/[-\s]/g, '');
        if (!placa) return;

        ordensServicoDoVeiculo = [];

        try {
            const response = await fetch(`/api/veiculo/placa/${placa}`);

            if (!response.ok) {
                clienteNome.value = '';
                if (clienteIdInput) clienteIdInput.value = '';
                idVeiculo.value = '';
                resetBuilder();
                return;
            }

            const data = await response.json();

            clienteNome.value = data.cliente_nome;
            
            // --- ADICIONE ESTA LINHA ---
            if (clienteIdInput) clienteIdInput.value = data.cliente_id;
            
            idVeiculo.value = data.veiculo_id;
            ordensServicoDoVeiculo = data.ordens_servico || [];

            if (typeSelect.value === 'App\\Models\\OrdemServico') {
                atualizarOpcoesItemRelacionado();
            }

        } catch (error) {
            console.error("Erro na busca por placa:", error);
            alert('Erro ao buscar dados do veículo. Verifique o console.');
        }
    });

    // 2. ALTERNÂNCIA DE TIPOS POLIMÓRFICOS
    typeSelect.addEventListener('change', () => {
        atualizarOpcoesItemRelacionado();
    });

    function atualizarOpcoesItemRelacionado() {
        const tipo = typeSelect.value;
        itemIdSelect.innerHTML = '';
        itemIdSelect.disabled = true;
        valorUnitInput.value = '';
        descInput.value = '';

        if (!tipo) {
            itemIdSelect.innerHTML = '<option value="">Selecione o tipo primeiro</option>';
            return;
        }

        // Caso A: Vinculando a própria Ordem de Serviço como item
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
        // Caso B: Adicionando um Produto Físico (Autopeça)
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
                option.setAttribute('data-preco', opt.getAttribute('data-preco'));
                itemIdSelect.appendChild(option);
            });
        }
    }

    // Auto-preenchimento ao selecionar o item (Produto ou OS)
    itemIdSelect.addEventListener('change', () => {
        const selectedOption = itemIdSelect.options[itemIdSelect.selectedIndex];
        if (selectedOption && selectedOption.value) {
            valorUnitInput.value = selectedOption.getAttribute('data-preco');
            descInput.value = selectedOption.textContent.trim();
        }
    });

    // 3. ADICIONAR ITEM NA TABELA DINÂMICA
    btnAdicionar.addEventListener('click', () => {
        if (!typeSelect.value || !itemIdSelect.value || !descInput.value.trim() || !valorUnitInput.value) {
            alert('Preencha os campos obrigatórios do item antes de inserir.');
            return;
        }

        const itemableType = typeSelect.value;
        const itemableId = itemIdSelect.value;
        const tipoText = itemableType.includes('Produto') ? 'Produto' : 'Serviço (OS)';
        const badgeColor = itemableType.includes('Produto') ? 'bg-info' : 'bg-warning';
        
        const descricao = descInput.value.trim();
        const quantidade = parseInt(qtdInput.value) || 1;
        const valorUnitario = parseFloat(valorUnitInput.value) || 0;
        const desconto = parseFloat(descontoInput.value) || 0;
        const garantiaDias = garantiaInput.value ? parseInt(garantiaInput.value) : '';

        const valorTotal = (quantidade * valorUnitario) - desconto;

        if (valorTotal < 0) {
            alert('O desconto não pode ser maior do que o valor total do item!');
            return;
        }

        if (linhaVazia) linhaVazia.style.display = 'none';

        const novaLinha = document.createElement('tr');
        novaLinha.setAttribute('data-item-total', valorTotal);

        novaLinha.innerHTML = `
            <td><span class="badge ${badgeColor} text-dark">${tipoText}</span></td>
            <td>${descricao}</td>
            <td>${quantidade}</td>
            <td>R$ ${valorUnitario.toFixed(2).replace('.', ',')}</td>
            <td>R$ ${desconto.toFixed(2).replace('.', ',')}</td>
            <td class="fw-bold text-dark">R$ ${valorTotal.toFixed(2).replace('.', ',')}</td>
            <td>${garantiaDias ? garantiaDias + ' dias' : 'Nenhuma'}</td>
            <td>
                <button type="button" class="btn btn-sm btn-danger btn-remover-item">Remover</button>
                <input type="hidden" name="itens[${itemIndex}][itemable_type]" value="${itemableType}">
                <input type="hidden" name="itens[${itemIndex}][itemable_id]" value="${itemableId}">
                <input type="hidden" name="itens[${itemIndex}][descricao]" value="${descricao}">
                <input type="hidden" name="itens[${itemIndex}][quantidade]" value="${quantidade}">
                <input type="hidden" name="itens[${itemIndex}][valor_unitario]" value="${valorUnitario}">
                <input type="hidden" name="itens[${itemIndex}][desconto]" value="${desconto}">
                <input type="hidden" name="itens[${itemIndex}][garantia_dias]" value="${garantiaDias}">
            </td>
        `;

        containerItens.appendChild(novaLinha);
        itemIndex++;

        resetBuilder();
        atualizarValorTotalGeral();
    });

    containerItens.addEventListener('click', (e) => {
        if (e.target.classList.contains('btn-remover-item')) {
            e.target.closest('tr').remove();
            if (containerItens.querySelectorAll('tr:not(#linha-vazia)').length === 0) {
                linhaVazia.style.display = 'table-row';
            }
            atualizarValorTotalGeral();
        }
    });

    function resetBuilder() {
        typeSelect.value = '';
        itemIdSelect.innerHTML = '<option value="">Selecione o tipo primeiro</option>';
        itemIdSelect.disabled = true;
        descInput.value = '';
        qtdInput.value = '1';
        valorUnitInput.value = '';
        descontoInput.value = '0.00';
        garantiaInput.value = '';
    }

    function atualizarValorTotalGeral() {
        let totalAcumulado = 0;
        containerItens.querySelectorAll('tr:not(#linha-vazia)').forEach(linha => {
            totalAcumulado += parseFloat(linha.getAttribute('data-item-total')) || 0;
        });
        valorGeralOsEl.textContent = "R$ " + totalAcumulado.toFixed(2).replace('.', ',');
    }
});