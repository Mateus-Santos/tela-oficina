{{-- =========================================================
     1. IDENTIFICAÇÃO DA NOTA / O.S.
========================================================= --}}

<div class="card mb-4">

    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-file-earmark-text"></i>
            Identificação da Nota / O.S.
        </h5>
    </div>

    <div class="card-body">

        <div class="row g-3">

            {{-- PLACA --}}
            <div class="col-md-4">

                <label for="placa_input" class="form-label">
                    <i class="bi bi-car-front"></i>
                    Placa
                </label>

                <input
                    type="text"
                    name="placa"
                    id="placa_input"
                    class="form-control"
                    value="{{ old('placa', isset($nota) ? $nota->placa : '') }}"
                    maxlength="10"
                    autocomplete="off"
                    placeholder="ABC1D23"
                >

            </div>


            {{-- CLIENTE --}}
            <div class="col-md-4">

                <label for="cliente_nome" class="form-label">
                    <i class="bi bi-person"></i>
                    Cliente
                </label>

                <input
                    type="text"
                    id="cliente_nome"
                    class="form-control"
                    value="{{ old('cliente_nome', isset($nota) && $nota->cliente && $nota->cliente->pessoa ? $nota->cliente->pessoa->nome : '') }}"
                    placeholder="Digite o nome do cliente"
                    autocomplete="off"
                >

                <input
                    type="hidden"
                    name="cliente_id"
                    id="cliente_id"
                    value="{{ old('cliente_id', isset($nota) ? $nota->cliente_id : '') }}"
                >

                <small class="text-muted">
                    Cliente não é obrigatório para venda de balcão.
                </small>

            </div>


            {{-- VEÍCULO --}}
            <div class="col-md-4">

                <label for="veiculo_cliente_id" class="form-label">
                    <i class="bi bi-car-front-fill"></i>
                    Veículo
                </label>

                <select
                    name="veiculo_cliente_id"
                    id="veiculo_cliente_id"
                    class="form-select"
                >

                    <option value="">
                        Nenhum veículo selecionado
                    </option>

                    @if(isset($veiculosCliente) && $veiculosCliente->count())

                        @foreach($veiculosCliente as $veiculo)

                            <option
                                value="{{ $veiculo->id }}"
                                {{ old('veiculo_cliente_id', isset($nota) ? $nota->veiculo_cliente_id : '') == $veiculo->id ? 'selected' : '' }}
                            >
                                {{ $veiculo->placa }}

                                @if(isset($veiculo->marca) && $veiculo->marca)
                                    - {{ $veiculo->marca }}
                                @endif

                                @if(isset($veiculo->modelo) && $veiculo->modelo)
                                    {{ $veiculo->modelo }}
                                @endif
                            </option>

                        @endforeach

                    @endif

                </select>

            </div>


            {{-- KM --}}
            <div class="col-md-4">

                <label for="km" class="form-label">
                    <i class="bi bi-speedometer2"></i>
                    KM atual
                </label>

                <input
                    type="number"
                    name="km"
                    id="km"
                    class="form-control"
                    value="{{ old('km', isset($nota) ? $nota->km : '') }}"
                    min="0"
                    step="1"
                    placeholder="Ex.: 85000"
                >

            </div>


            {{-- INTERVALO TROCA DE ÓLEO --}}
            <div class="col-md-4">

                <label for="km_diferenca_troca_oleo" class="form-label">
                    <i class="bi bi-arrow-repeat"></i>
                    Intervalo da troca de óleo
                </label>

                <input
                    type="number"
                    name="km_diferenca_troca_oleo"
                    id="km_diferenca_troca_oleo"
                    class="form-control"
                    value="{{ old('km_diferenca_troca_oleo', isset($nota) ? $nota->km_diferenca_troca_oleo : '') }}"
                    min="0"
                    step="1"
                    placeholder="Ex.: 10000"
                >

            </div>


            {{-- PRÓXIMA TROCA --}}
            <div class="col-md-4">

                <label for="km_proxima_troca_oleo" class="form-label">
                    <i class="bi bi-calendar-check"></i>
                    Próxima troca de óleo
                </label>

                <input
                    type="number"
                    name="km_proxima_troca_oleo"
                    id="km_proxima_troca_oleo"
                    class="form-control"
                    value="{{ old('km_proxima_troca_oleo', isset($nota) ? $nota->km_proxima_troca_oleo : '') }}"
                    min="0"
                    step="1"
                    readonly
                >

                <small
                    id="km_diferenca_troca_oleo_mensagem"
                    class="form-text text-muted"
                ></small>

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     2. ITENS DA NOTA / O.S.
========================================================= --}}

<div class="card mb-4">

    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">

        <h5 class="mb-0">
            <i class="bi bi-cart3"></i>
            Itens da Nota / O.S.
        </h5>

        <button
            type="button"
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#modalAdicionarItem"
        >
            <i class="bi bi-plus-circle"></i>
            Adicionar item
        </button>

    </div>


    <div class="card-body">

        <div class="table-responsive">

            <table
                class="table table-bordered table-hover align-middle"
                id="tabela-itens-os"
            >

                <thead>

                    <tr>

                        <th style="min-width: 220px;">
                            Tipo
                        </th>

                        <th style="min-width: 220px;">
                            Descrição
                        </th>

                        <th style="width: 110px;">
                            Qtd.
                        </th>

                        <th style="width: 150px;">
                            Valor unit.
                        </th>

                        <th style="width: 140px;">
                            Desconto
                        </th>

                        <th style="width: 130px;">
                            Garantia
                        </th>

                        <th style="width: 150px;">
                            Total
                        </th>

                        <th style="width: 70px;">
                            Ações
                        </th>

                    </tr>

                </thead>


                <tbody id="container-itens-dinamicos">

                    @if(isset($itens) && $itens->count())

                        @foreach($itens as $index => $item)

                            @php
                                $itemableType = $item->itemable_type ?? '';
                                $itemableId = $item->itemable_id ?? '';

                                $descricaoItem = $item->descricao ?? '';

                                $quantidadeItem = $item->quantidade ?? 1;

                                $valorUnitarioItem = $item->valor_unitario ?? 0;

                                $descontoItem = $item->desconto ?? 0;

                                $garantiaDiasItem = $item->garantia_dias ?? 0;

                                $totalItem = ($quantidadeItem * $valorUnitarioItem) - $descontoItem;
                            @endphp

                            <tr
                                class="item-os"
                                data-index="{{ $index }}"
                                data-itemable-type="{{ $itemableType }}"
                                data-itemable-id="{{ $itemableId }}"
                            >

                                {{-- ID DO ITEM --}}
                                <input
                                    type="hidden"
                                    name="itens[{{ $index }}][id]"
                                    value="{{ $item->id }}"
                                >


                                {{-- TIPO --}}
                                <input
                                    type="hidden"
                                    name="itens[{{ $index }}][itemable_type]"
                                    value="{{ $itemableType }}"
                                    class="itemable-type"
                                >


                                {{-- ID DO PRODUTO / O.S. --}}
                                <input
                                    type="hidden"
                                    name="itens[{{ $index }}][itemable_id]"
                                    value="{{ $itemableId }}"
                                    class="itemable-id"
                                >


                                {{-- DESCRIÇÃO --}}
                                <td>

                                    <input
                                        type="text"
                                        name="itens[{{ $index }}][descricao]"
                                        class="form-control item-descricao"
                                        value="{{ $descricaoItem }}"
                                        required
                                    >

                                </td>


                                {{-- QUANTIDADE --}}
                                <td>

                                    <input
                                        type="number"
                                        name="itens[{{ $index }}][quantidade]"
                                        class="form-control item-quantidade"
                                        value="{{ $quantidadeItem }}"
                                        min="1"
                                        step="1"
                                        required
                                    >

                                </td>


                                {{-- VALOR UNITÁRIO --}}
                                <td>

                                    <input
                                        type="text"
                                        name="itens[{{ $index }}][valor_unitario]"
                                        class="form-control item-valor-unitario"
                                        value="{{ number_format($valorUnitarioItem, 2, ',', '.') }}"
                                        inputmode="decimal"
                                        required
                                    >

                                </td>


                                {{-- DESCONTO --}}
                                <td>

                                    <input
                                        type="text"
                                        name="itens[{{ $index }}][desconto]"
                                        class="form-control item-desconto"
                                        value="{{ number_format($descontoItem, 2, ',', '.') }}"
                                        inputmode="decimal"
                                    >

                                </td>


                                {{-- GARANTIA --}}
                                <td>

                                    <div class="input-group">

                                        <input
                                            type="number"
                                            name="itens[{{ $index }}][garantia_dias]"
                                            class="form-control item-garantia"
                                            value="{{ $garantiaDiasItem }}"
                                            min="0"
                                            step="1"
                                        >

                                        <span class="input-group-text">
                                            dias
                                        </span>

                                    </div>

                                </td>


                                {{-- TOTAL --}}
                                <td>

                                    <input
                                        type="text"
                                        class="form-control item-total"
                                        value="{{ number_format($totalItem, 2, ',', '.') }}"
                                        readonly
                                    >

                                </td>


                                {{-- AÇÕES --}}
                                <td class="text-center">

                                    <button
                                        type="button"
                                        class="btn btn-danger btn-sm btn-remover-item"
                                        title="Remover item"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>

                                </td>

                            </tr>

                        @endforeach

                    @endif

                </tbody>

            </table>

        </div>


        {{-- MENSAGEM QUANDO NÃO HÁ ITENS --}}
        <div
            id="mensagem-sem-itens"
            class="alert alert-info text-center mb-0"
            style="{{ isset($itens) && $itens->count() ? 'display: none;' : '' }}"
        >
            <i class="bi bi-info-circle"></i>
            Nenhum item adicionado à nota.
        </div>

    </div>

</div>



{{-- =========================================================
     3. RESUMO FINANCEIRO
========================================================= --}}

<div class="card mb-4">

    <div class="card-header">

        <h5 class="mb-0">
            <i class="bi bi-calculator"></i>
            Resumo financeiro
        </h5>

    </div>


    <div class="card-body">

        <div class="row g-3">

            {{-- PEÇAS --}}
            <div class="col-md-6">

                <div class="border rounded p-3 h-100">

                    <h6 class="mb-3">
                        <i class="bi bi-box-seam"></i>
                        Peças / Produtos
                    </h6>

                    <div class="d-flex justify-content-between mb-2">

                        <span>
                            Total bruto:
                        </span>

                        <strong id="resumo-pecas-bruto">
                            R$ 0,00
                        </strong>

                    </div>


                    <div class="d-flex justify-content-between mb-2">

                        <span>
                            Descontos:
                        </span>

                        <strong id="resumo-pecas-desconto">
                            R$ 0,00
                        </strong>

                    </div>


                    <hr>


                    <div class="d-flex justify-content-between">

                        <span>
                            Total líquido:
                        </span>

                        <strong id="resumo-pecas-liquido">
                            R$ 0,00
                        </strong>

                    </div>

                </div>

            </div>


            {{-- SERVIÇOS --}}
            <div class="col-md-6">

                <div class="border rounded p-3 h-100">

                    <h6 class="mb-3">
                        <i class="bi bi-tools"></i>
                        Serviços / O.S.
                    </h6>

                    <div class="d-flex justify-content-between mb-2">

                        <span>
                            Total bruto:
                        </span>

                        <strong id="resumo-servicos-bruto">
                            R$ 0,00
                        </strong>

                    </div>


                    <div class="d-flex justify-content-between mb-2">

                        <span>
                            Descontos:
                        </span>

                        <strong id="resumo-servicos-desconto">
                            R$ 0,00
                        </strong>

                    </div>


                    <hr>


                    <div class="d-flex justify-content-between">

                        <span>
                            Total líquido:
                        </span>

                        <strong id="resumo-servicos-liquido">
                            R$ 0,00
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        {{-- TOTAL DE DESCONTOS --}}
        <div class="row mt-3">

            <div class="col-12">

                <div class="d-flex justify-content-between">

                    <span>
                        <i class="bi bi-percent"></i>
                        Total de descontos:
                    </span>

                    <strong id="resumo-total-descontos">
                        R$ 0,00
                    </strong>

                </div>

            </div>

        </div>


        {{-- TOTAL FINAL --}}
        <div class="row mt-3">

            <div class="col-12">

                <div class="border rounded p-3">

                    <div class="d-flex justify-content-between align-items-center">

                        <span class="fs-5">
                            <i class="bi bi-cash-stack"></i>
                            TOTAL DA NOTA
                        </span>

                        <strong
                            id="valor-geral-os"
                            class="fs-4"
                        >
                            R$ 0,00
                        </strong>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     4. DESCONTOS GERAIS
========================================================= --}}

<div class="card mb-4">

    <div class="card-header">

        <h5 class="mb-0">
            <i class="bi bi-percent"></i>
            Descontos
        </h5>

    </div>


    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

            <div>

                <strong>
                    Desconto geral
                </strong>

                <div class="text-muted">
                    Aplique um desconto sobre o valor total da nota.
                </div>

            </div>


            <button
                type="button"
                class="btn btn-outline-primary"
                data-bs-toggle="modal"
                data-bs-target="#modalDescontos"
            >
                <i class="bi bi-percent"></i>
                Gerenciar descontos
            </button>

        </div>

    </div>

</div>



{{-- =========================================================
     5. BOTÕES
========================================================= --}}

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">

    <a
        href="{{ route('notasitem.index') }}"
        class="btn btn-secondary"
    >
        <i class="bi bi-arrow-left"></i>
        Voltar
    </a>


    <button
        type="submit"
        class="btn btn-success"
        id="btn-salvar-nota"
    >
        <i class="bi bi-check-circle"></i>
        Salvar nota
    </button>

</div>
