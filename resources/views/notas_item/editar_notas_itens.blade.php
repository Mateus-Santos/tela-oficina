@extends('layouts.layout')

@section('content')

@if ($errors->any()) <div class="alert alert-danger"> <ul class="mb-0">
@foreach ($errors->all() as $error) <li>{{ $error }}</li>
@endforeach </ul> </div>
@endif

@php

$ordensServicoVeiculo = collect();


if ($nota->veiculoscliente) {
    $ordensServicoVeiculo = $nota->veiculoscliente
        ->ordensServico()
        ->orderBy('created_at', 'desc')
        ->get();
}


@endphp

<script>
    window.ordensServicoVeiculo = @json(
        $ordensServicoVeiculo->map(function ($os) {
            return [
                'id' => $os->id,
                'descricao' => $os->descricao ?: 'OS #' . $os->id . ' (' . ($os->status ?? 'Aberta') . ')',
            ];
        })->values()
    );
</script>

<div class="container cadastro">


<h1 class="mb-4">EDITAR NOTA FISCAL #{{ $nota->id }}</h1>

<form action="{{ route('notasitem.update', $nota->id) }}"
      method="POST"
      id="form-os-itens">

    @csrf
    @method('PUT')

    {{-- ============================================================
         1. IDENTIFICAÇÃO DO CLIENTE / VEÍCULO
    ============================================================ --}}
    <div class="card mb-4 shadow-sm">

        <div class="card-header bg-dark text-white">
            1. Identificação do Cliente / Veículo
        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-4">

                    <label class="form-label">
                        Placa veículo (Opcional para balcão):
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="placa_input"
                        placeholder="Digite a placa"
                        value="{{ $nota->veiculoscliente?->placa }}"
                    >

                    <input
                        type="hidden"
                        name="veiculo_cliente_id"
                        id="veiculo_cliente_id"
                        value="{{ $nota->veiculo_cliente_id }}"
                    >

                </div>

                <div class="col-md-4">

                    <label class="form-label">
                        KM Atual:
                    </label>

                    <input
                        type="number"
                        name="km"
                        id="km"
                        class="form-control"
                        placeholder="Ex: 85000"
                        min="0"
                        value="{{ old('km', $nota->km) }}"
                    >

                </div>

                <div class="col-md-4">

                    <label class="form-label">
                        Cliente:
                    </label>

                    <input
                        type="text"
                        id="cliente_nome"
                        class="form-control"
                        placeholder="Nome do cliente"
                        readonly
                        required
                        value="{{ $nota->cliente?->pessoa?->nome ?? 'Cliente Balcão' }}"
                    >

                    <input
                        type="hidden"
                        name="cliente_id"
                        id="cliente_id"
                        value="{{ $nota->cliente_id }}"
                    >

                </div>

            </div>

        </div>
    </div>


    {{-- ============================================================
         2. ADICIONAR NOVO ITEM
    ============================================================ --}}
    <div class="card mb-4 shadow-sm border-primary">

        <div class="card-header bg-primary text-white">
            2. Adicionar Novo Item
        </div>

        <div class="card-body">

            <div class="row mb-3">

                <div class="col-md-3">

                    <label class="form-label">
                        Tipo de Item
                    </label>

                    <select
                        id="builder_type"
                        class="form-control"
                    >
                        <option value="">Selecione...</option>

                        <option value="App\Models\Produto">
                            Produto (Autopeça)
                        </option>

                        <option value="App\Models\OrdemServico">
                            Serviço (Ordem de Serviço)
                        </option>
                    </select>

                </div>

                <div class="col-md-4">

                    <label class="form-label">
                        Item Relacionado
                    </label>

                    <select
                        id="builder_item_id"
                        class="form-control"
                        disabled
                    >
                        <option value="">
                            Selecione o tipo primeiro
                        </option>
                    </select>

                </div>

                <div class="col-md-5">

                    <label class="form-label">
                        Descrição Exibida na Nota*
                    </label>

                    <input
                        type="text"
                        id="builder_descricao"
                        class="form-control"
                        placeholder="Ex: Mão de Obra Mecânica Geral"
                    >

                </div>

            </div>


            <div class="row mb-3">

                <div class="col-md-2">

                    <label class="form-label">
                        Quantidade
                    </label>

                    <input
                        type="number"
                        id="builder_quantidade"
                        class="form-control"
                        value="1"
                        min="1"
                        step="1"
                    >

                </div>

                <div class="col-md-3">

                    <label class="form-label">
                        Valor Unitário (R$)
                    </label>

                    <input
                        type="number"
                        id="builder_valor_unitario"
                        class="form-control"
                        step="0.01"
                        min="0"
                    >

                </div>

                <div class="col-md-3">

                    <label class="form-label">
                        Desconto Item (R$)
                    </label>

                    <input
                        type="number"
                        id="builder_desconto"
                        class="form-control"
                        value="0.00"
                        step="0.01"
                        min="0"
                    >

                </div>

                <div class="col-md-2">

                    <label class="form-label">
                        Garantia (Dias)
                    </label>

                    <input
                        type="number"
                        id="builder_garantia"
                        class="form-control"
                        min="0"
                        placeholder="Ex: 90"
                    >

                </div>

                <div class="col-md-2 d-flex align-items-end">

                    <button
                        type="button"
                        id="btn-adicionar-item"
                        class="btn btn-primary w-100"
                    >
                        Inserir Item
                    </button>

                </div>

            </div>

        </div>
    </div>


    {{-- ============================================================
         SELECT OCULTO DE PRODUTOS
    ============================================================ --}}
    <select
        id="produtos_estatiticos_local"
        style="display: none;"
    >

        @foreach($produtos as $prod)

            <option
                value="{{ $prod->id }}"
                data-preco="{{ $prod->preco_uni ?? $prod->preco ?? $prod->valor ?? 0 }}"
            >
                {{ $prod->nome ?? $prod->descricao }}
            </option>

        @endforeach

    </select>


    {{-- ============================================================
         3. ITENS DA NOTA
    ============================================================ --}}
    <div class="card mb-4 shadow-sm">

        <div class="card-header bg-success text-white">
            3. Itens da Nota Fiscal
        </div>

        <div class="card-body">

            <table
                class="table table-bordered align-middle"
                id="tabela-itens-os"
            >

                <thead>

                    <tr class="table-secondary">

                        <th width="10%">
                            Tipo
                        </th>

                        <th width="25%">
                            Descrição
                        </th>

                        <th width="10%">
                            Qtd
                        </th>

                        <th width="13%">
                            Val. Unitário (R$)
                        </th>

                        <th width="12%">
                            Desconto (R$)
                        </th>

                        <th width="12%">
                            Total (R$)
                        </th>

                        <th width="10%">
                            Garantia
                        </th>

                        <th width="8%">
                            Ações
                        </th>

                    </tr>

                </thead>


                <tbody id="container-itens-dinamicos">

                    @forelse($nota->notasitem as $index => $item)

                        @php
                            $isProduto = $item->itemable_type === 'App\Models\Produto';

                            $quantidade = (float) ($item->quantidade ?? 0);
                            $valorUnitario = (float) ($item->valor_unitario ?? 0);
                            $desconto = (float) ($item->desconto ?? 0);

                            $valorTotalItem = max(
                                0,
                                ($quantidade * $valorUnitario) - $desconto
                            );
                        @endphp

                        <tr class="item-row">

                            <td>

                                <span class="badge {{ $isProduto ? 'bg-info' : 'bg-warning' }} text-dark">
                                    {{ $isProduto ? 'Produto' : 'Serviço' }}
                                </span>

                                <input
                                    type="hidden"
                                    name="itens[{{ $index }}][id]"
                                    value="{{ $item->id }}"
                                >

                                <input
                                    type="hidden"
                                    name="itens[{{ $index }}][itemable_type]"
                                    value="{{ $item->itemable_type }}"
                                >

                                <input
                                    type="hidden"
                                    name="itens[{{ $index }}][itemable_id]"
                                    value="{{ $item->itemable_id }}"
                                >

                            </td>


                            <td>

                                <input
                                    type="text"
                                    name="itens[{{ $index }}][descricao]"
                                    class="form-control form-control-sm input-desc"
                                    value="{{ $item->descricao }}"
                                    required
                                >

                            </td>


                            <td>

                                <input
                                    type="number"
                                    name="itens[{{ $index }}][quantidade]"
                                    class="form-control form-control-sm input-qtd"
                                    value="{{ $quantidade }}"
                                    min="1"
                                    step="1"
                                    required
                                >

                            </td>


                            <td>

                                <input
                                    type="number"
                                    name="itens[{{ $index }}][valor_unitario]"
                                    class="form-control form-control-sm input-vunit"
                                    value="{{ number_format($valorUnitario, 2, '.', '') }}"
                                    step="0.01"
                                    min="0"
                                    required
                                >

                            </td>


                            <td>

                                <input
                                    type="number"
                                    name="itens[{{ $index }}][desconto]"
                                    class="form-control form-control-sm input-desc-val"
                                    value="{{ number_format($desconto, 2, '.', '') }}"
                                    step="0.01"
                                    min="0"
                                >

                            </td>


                            <td>

                                <input
                                    type="text"
                                    class="form-control form-control-sm input-vtotal fw-bold bg-light"
                                    value="{{ number_format($valorTotalItem, 2, ',', '.') }}"
                                    readonly
                                >

                            </td>


                            <td>

                                <input
                                    type="number"
                                    name="itens[{{ $index }}][garantia_dias]"
                                    class="form-control form-control-sm input-garantia"
                                    value="{{ $item->garantia_dias }}"
                                    min="0"
                                    placeholder="Dias"
                                >

                            </td>


                            <td class="text-center">

                                <button
                                    type="button"
                                    class="btn btn-sm btn-danger btn-remover-item"
                                    title="Remover Item"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>

                            </td>

                        </tr>

                    @empty

                        <tr id="linha-vazia">

                            <td
                                colspan="8"
                                class="text-center text-muted py-4"
                            >
                                Nenhum item adicionado a esta lista ainda.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>
    </div>


    {{-- ============================================================
         4. RESUMO FINANCEIRO
    ============================================================ --}}
    <div class="row mb-4">

        <div class="col-md-7 offset-md-5">

            <div class="card border-primary shadow-sm">

                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">

                    <h5 class="mb-0 fs-6">
                        <i class="bi bi-calculator"></i>
                        Resumo Financeiro Detalhado
                    </h5>

                    <button
                        type="button"
                        class="btn btn-sm btn-light fw-bold text-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#modalDescontos"
                    >
                        <i class="bi bi-percent"></i>
                        Aplicar / Editar Descontos
                    </button>

                </div>


                <div class="card-body p-3">

                    {{-- PEÇAS --}}

                    <div class="bg-light p-2 rounded mb-3 border">

                        <h6
                            class="fw-bold text-primary mb-2 border-bottom pb-1"
                            style="font-size: 0.9rem;"
                        >
                            <i class="bi bi-box-seam"></i>
                            PEÇAS / PRODUTOS
                        </h6>

                        <div class="d-flex justify-content-between mb-1 fs-7">

                            <span class="text-muted">
                                Subtotal Bruto:
                            </span>

                            <span id="resumo-pecas-bruto">
                                R$ 0,00
                            </span>

                        </div>

                        <div class="d-flex justify-content-between mb-1 fs-7 text-danger">

                            <span>
                                (-) Desconto Aplicado:
                            </span>

                            <span id="resumo-pecas-desconto">
                                R$ 0,00
                            </span>

                        </div>

                        <div class="d-flex justify-content-between fw-bold text-dark pt-1 border-top">

                            <span>
                                Subtotal Líquido Peças:
                            </span>

                            <span id="resumo-pecas-liquido">
                                R$ 0,00
                            </span>

                        </div>

                    </div>


                    {{-- SERVIÇOS --}}

                    <div class="bg-light p-2 rounded mb-3 border">

                        <h6
                            class="fw-bold text-warning text-dark mb-2 border-bottom pb-1"
                            style="font-size: 0.9rem;"
                        >
                            <i class="bi bi-tools"></i>
                            MÃO DE OBRA / SERVIÇOS
                        </h6>

                        <div class="d-flex justify-content-between mb-1 fs-7">

                            <span class="text-muted">
                                Subtotal Bruto:
                            </span>

                            <span id="resumo-servicos-bruto">
                                R$ 0,00
                            </span>

                        </div>

                        <div class="d-flex justify-content-between mb-1 fs-7 text-danger">

                            <span>
                                (-) Desconto Aplicado:
                            </span>

                            <span id="resumo-servicos-desconto">
                                R$ 0,00
                            </span>

                        </div>

                        <div class="d-flex justify-content-between fw-bold text-dark pt-1 border-top">

                            <span>
                                Subtotal Líquido Serviços:
                            </span>

                            <span id="resumo-servicos-liquido">
                                R$ 0,00
                            </span>

                        </div>

                    </div>


                    <hr class="my-2">


                    <div class="d-flex justify-content-between mb-1 text-danger fw-bold">

                        <span>
                            TOTAL DE DESCONTOS CONCEDIDOS:
                        </span>

                        <span id="resumo-total-descontos">
                            R$ 0,00
                        </span>

                    </div>


                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">

                        <h5 class="mb-0 text-dark fw-bold">
                            VALOR TOTAL DA NOTA:
                        </h5>

                        <h3
                            class="mb-0 text-success fw-bold"
                            id="valor-geral-os"
                        >
                            R$ 0,00
                        </h3>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <div class="text-center mb-5">

        <button
            type="submit"
            class="btn btn-primary btn-lg px-5"
        >
            Atualizar Nota Fiscal
        </button>

    </div>

</form>
```

</div>

{{-- ================================================================
MODAL DE DESCONTOS
================================================================ --}}

<div
    class="modal fade"
    id="modalDescontos"
    tabindex="-1"
    aria-labelledby="modalDescontosLabel"
    aria-hidden="true"
>

```
<div class="modal-dialog modal-dialog-centered">

    <div class="modal-content">

        <div class="modal-header bg-dark text-white">

            <h5
                class="modal-title"
                id="modalDescontosLabel"
            >
                <i class="bi bi-tags-fill"></i>
                Gerenciador de Descontos
            </h5>

            <button
                type="button"
                class="btn-close btn-close-white"
                data-bs-dismiss="modal"
                aria-label="Close"
            ></button>

        </div>


        <div class="modal-body">

            {{-- DESCONTO PEÇAS --}}

            <div class="card mb-3 bg-light border-0">

                <div class="card-body p-3">

                    <label class="form-label fw-bold text-primary">

                        <i class="bi bi-box-seam"></i>
                        Desconto em Peças / Produtos

                    </label>

                    <div class="row g-2">

                        <div class="col-7">

                            <div class="input-group input-group-sm">

                                <span class="input-group-text">
                                    Val. (R$)
                                </span>

                                <input
                                    type="number"
                                    id="modal-desc-pecas-valor"
                                    class="form-control"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                >

                            </div>

                        </div>


                        <div class="col-5">

                            <div class="input-group input-group-sm">

                                <input
                                    type="number"
                                    id="modal-desc-pecas-porcent"
                                    class="form-control"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    placeholder="0"
                                >

                                <span class="input-group-text">
                                    %
                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- DESCONTO SERVIÇOS --}}

            <div class="card bg-light border-0">

                <div class="card-body p-3">

                    <label class="form-label fw-bold text-warning text-dark">

                        <i class="bi bi-tools"></i>
                        Desconto em Mão de Obra / Serviços

                    </label>

                    <div class="row g-2">

                        <div class="col-7">

                            <div class="input-group input-group-sm">

                                <span class="input-group-text">
                                    Val. (R$)
                                </span>

                                <input
                                    type="number"
                                    id="modal-desc-servicos-valor"
                                    class="form-control"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                >

                            </div>

                        </div>


                        <div class="col-5">

                            <div class="input-group input-group-sm">

                                <input
                                    type="number"
                                    id="modal-desc-servicos-porcent"
                                    class="form-control"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    placeholder="0"
                                >

                                <span class="input-group-text">
                                    %
                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <div class="modal-footer">

            <button
                type="button"
                class="btn btn-secondary"
                data-bs-dismiss="modal"
            >
                Cancelar
            </button>

            <button
                type="button"
                class="btn btn-success fw-bold"
                id="btn-aplicar-descontos-modal"
            >
                <i class="bi bi-check-circle"></i>
                Aplicar Descontos
            </button>

        </div>

    </div>

</div>

</div>

@endsection

@section('scripts')

@vite([
'resources/js/gerenciadorItensOs.js',
'resources/js/cadOsItem.js'
])

@endsection
