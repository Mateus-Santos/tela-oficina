@extends('layouts.layout')

@section('content')

<div class="container cadastro">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">
            <i class="bi bi-receipt"></i>
            DETALHES DA NOTA #{{ $nota->id }}
        </h1>

    @if($nota->status === 'Aberto' && auth()->user() && auth()->user()->permitions != 2)
        <form
            action="{{ route('notas.finalizar', $nota->id) }}"
            method="POST"
            onsubmit="return confirm('Deseja finalizar esta nota? Os produtos serão baixados do estoque e a nota não poderá mais ser editada.');"
        >
            @csrf

            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-circle"></i>
                Finalizar nota
            </button>
        </form>
    @endif
</div>

@if ($errors->has('finalizacao'))
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i>
        {{ $errors->first('finalizacao') }}
    </div>
@endif

@if ($errors->has('nota'))
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i>
        {{ $errors->first('nota') }}
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success">
        <i class="bi bi-check-circle"></i>
        {{ session('success') }}
    </div>
@endif

{{-- ============================================================
     INFORMAÇÕES GERAIS DA NOTA
============================================================ --}}

<table class="table">
    <thead>
        <tr>
            <th scope="col">ID Nota</th>
            <th scope="col">Status</th>
            <th scope="col">Data Criação</th>
            <th scope="col">Cliente</th>
            <th scope="col">Veículo / Placa</th>
            <th scope="col">PDF</th>
        </tr>
    </thead>

    <tbody>
        <tr>
            <td>{{ $nota->id }}</td>

            <td>
                @if($nota->status === 'Aberto')
                    <span class="badge bg-warning text-dark">
                        <i class="bi bi-pencil-square"></i>
                        Aberto
                    </span>
                @elseif($nota->status === 'Finalizado')
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle"></i>
                        Finalizado
                    </span>
                @elseif($nota->status === 'Cancelado')
                    <span class="badge bg-danger">
                        <i class="bi bi-x-circle"></i>
                        Cancelado
                    </span>
                @else
                    <span class="badge bg-secondary">
                        {{ $nota->status }}
                    </span>
                @endif
            </td>

            <td>
                {{ $nota->created_at
                    ? $nota->created_at->format('d/m/Y H:i')
                    : 'N/A'
                }}
            </td>

            <td>
                {{ $nota->cliente?->pessoa?->nome ?? 'Cliente Geral / Balcão' }}
            </td>

            <td>
                {{ $nota->veiculosCliente?->placa ?? 'N/A' }}
            </td>

            <td>
                <a
                    href="{{ route('notas.pdf', $nota->id) }}"
                    target="_blank"
                    class="btn btn-danger"
                    title="Gerar PDF"
                >
                    <i class="bi bi-printer"></i>
                    PDF
                </a>
            </td>
        </tr>
    </tbody>
</table>

@if($nota->status === 'Aberto')
    <div class="alert alert-warning">
        <i class="bi bi-info-circle"></i>
        A nota está aberta. Você pode adicionar, alterar ou remover itens antes da finalização.
        Ao finalizar, os produtos serão baixados do estoque e a nota não poderá mais ser editada.
    </div>
@elseif($nota->status === 'Finalizado')
    <div class="alert alert-success">
        <i class="bi bi-check-circle"></i>
        Esta nota está finalizada. Os produtos foram baixados do estoque e a nota não pode mais ser alterada.
    </div>
@elseif($nota->status === 'Cancelado')
    <div class="alert alert-danger">
        <i class="bi bi-x-circle"></i>
        Esta nota está cancelada e não pode mais ser alterada.
    </div>
@endif

<hr class="my-4">

{{-- ============================================================
     SEÇÃO DE ITENS DA NOTA
============================================================ --}}

@if($itens->isEmpty())
    <div class="alert alert-info d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-info-circle"></i>
            Esta nota ainda não possui itens cadastrados.
        </span>

        @if($nota->status === 'Aberto' && auth()->user() && auth()->user()->permitions != 2)
            <a
                class="btn btn-success"
                href="{{ route('notasitem.edit', $nota->id) }}"
            >
                <i class="bi bi-plus-circle"></i>
                Adicionar Item
            </a>
        @endif
    </div>
@else
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">
            <i class="bi bi-list-ul"></i>
            ITENS DA NOTA
        </h2>

        @if($nota->status === 'Aberto' && auth()->user() && auth()->user()->permitions != 2)
            <a
                class="btn btn-success"
                href="{{ route('notasitem.edit', $nota->id) }}"
            >
                <i class="bi bi-plus-circle"></i>
                Adicionar Item
            </a>
        @endif
    </div>

    {{-- ========================================================
         TABELA DE ITENS
    ========================================================= --}}

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Tipo</th>
                    <th scope="col">Descrição / Nome</th>
                    <th scope="col">Qtd.</th>
                    <th scope="col">Preço Unit.</th>
                    <th scope="col">Desconto</th>
                    <th scope="col">Subtotal</th>

                    @if($nota->status === 'Aberto' && auth()->user() && auth()->user()->permitions != 2)
                        <th scope="col">Ações</th>
                    @endif
                </tr>
            </thead>

            <tbody>
                @foreach($itens as $item)
                    @php
                        $valorUnitario = (float) ($item->valor_unitario ?? 0);
                        $desconto = (float) ($item->desconto ?? 0);
                        $quantidade = (int) ($item->quantidade ?? 0);
                        $subtotalItem = max(
                            0,
                            ($quantidade * $valorUnitario) - $desconto
                        );
                    @endphp

                    <tr>
                        {{-- ID --}}
                        <th scope="row">
                            {{ $item->id }}
                        </th>

                        {{-- TIPO --}}
                        <td>
                            @if($item->itemable instanceof \App\Models\Produto)
                                <span class="badge bg-primary">
                                    <i class="bi bi-box-seam"></i>
                                    Produto
                                </span>
                            @elseif($item->itemable instanceof \App\Models\OrdemServico)
                                <span class="badge bg-info text-dark">
                                    <i class="bi bi-wrench-adjustable"></i>
                                    O.S.
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    <i class="bi bi-question-circle"></i>
                                    Outro
                                </span>
                            @endif
                        </td>

                        {{-- DESCRIÇÃO --}}
                        <td>
                            {{ $item->descricao
                                ?? $item->itemable?->nome
                                ?? $item->itemable?->descricao
                                ?? 'Item sem nome'
                            }}
                        </td>

                        {{-- QUANTIDADE --}}
                        <td>
                            {{ $quantidade }}
                        </td>

                        {{-- VALOR UNITÁRIO --}}
                        <td>
                            R$ {{ number_format(
                                $valorUnitario,
                                2,
                                ',',
                                '.'
                            ) }}
                        </td>

                        {{-- DESCONTO --}}
                        <td>
                            R$ {{ number_format(
                                $desconto,
                                2,
                                ',',
                                '.'
                            ) }}
                        </td>

                        {{-- SUBTOTAL --}}
                        <td>
                            R$ {{ number_format(
                                $subtotalItem,
                                2,
                                ',',
                                '.'
                            ) }}
                        </td>

                        {{-- AÇÕES --}}
                        @if($nota->status === 'Aberto' && auth()->user() && auth()->user()->permitions != 2)
                            <td>
                                <form
                                    action="{{ route('notasitem.destroy', $item->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Deseja realmente remover este item da nota?');"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn btn-danger"
                                        title="Remover item"
                                    >
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ========================================================
         RESUMO FINANCEIRO
    ========================================================= --}}

    <div class="d-flex justify-content-end align-items-center gap-2 mt-3">
        <strong>
            Valor Total da Nota:
        </strong>

        <input
            type="text"
            class="form-control w-auto text-end fw-bold"
            value="R$ {{ number_format($valorTotal, 2, ',', '.') }}"
            readonly
            disabled
        >
    </div>
@endif


</div>
@endsection
