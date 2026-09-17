@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    {{-- =========================================================
         CABEÇALHO
    ========================================================== --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h1 class="mb-1">
                <i class="bi bi-receipt"></i>
                NOTA #{{ $nota->id }}
            </h1>
            <div class="text-muted">
                Detalhes da nota / ordem de serviço
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            @if($nota->status === 'Aberto' && auth()->user() && auth()->user()->permitions != 2)
                <a
                    href="{{ route('notasitem.edit', $nota->id) }}"
                    class="btn btn-primary"
                >
                    <i class="bi bi-pencil-square"></i>
                    Editar nota
                </a>
            @endif

            <a
                href="{{ route('notas.pdf', $nota->id) }}"
                target="_blank"
                class="btn btn-danger"
            >
                <i class="bi bi-file-earmark-pdf"></i>
                PDF
            </a>
        </div>
    </div>

    {{-- =========================================================
         MENSAGENS
    ========================================================== --}}
    @if(session('success'))
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->has('finalizacao'))
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i>
            {{ $errors->first('finalizacao') }}
        </div>
    @endif

    @if($errors->has('cancelamento'))
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i>
            {{ $errors->first('cancelamento') }}
        </div>
    @endif

    @if($errors->has('nota'))
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i>
            {{ $errors->first('nota') }}
        </div>
    @endif

    {{-- =========================================================
         STATUS E AÇÕES
    ========================================================== --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">
                <i class="bi bi-info-circle"></i>
                Status da nota
            </h5>

            @if($nota->status === 'Aberto')
                <span class="badge bg-warning text-dark fs-6">
                    <i class="bi bi-pencil-square"></i>
                    Aberto
                </span>
            @elseif($nota->status === 'Finalizado')
                <span class="badge bg-success fs-6">
                    <i class="bi bi-check-circle"></i>
                    Finalizado
                </span>
            @elseif($nota->status === 'Cancelado')
                <span class="badge bg-danger fs-6">
                    <i class="bi bi-x-circle"></i>
                    Cancelado
                </span>
            @else
                <span class="badge bg-secondary fs-6">
                    {{ $nota->status }}
                </span>
            @endif
        </div>

        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    @if($nota->status === 'Aberto')
                        <strong>Nota aberta</strong>
                        <div class="text-muted">
                            Esta nota pode ser alterada antes da finalização.
                        </div>
                    @elseif($nota->status === 'Finalizado')
                        <strong>Nota finalizada</strong>
                        <div class="text-muted">
                            Esta nota não pode mais ser editada.
                        </div>
                    @elseif($nota->status === 'Cancelado')
                        <strong>Nota cancelada</strong>
                        <div class="text-muted">
                            Esta nota não pode mais ser alterada.
                        </div>
                    @endif
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    @if($nota->status === 'Aberto' && auth()->user() && auth()->user()->permitions != 2)
                        <form
                            action="{{ route('notas.finalizar', $nota->id) }}"
                            method="POST"
                            onsubmit="return confirm('Deseja finalizar esta nota? Os produtos serão baixados do estoque e a nota não poderá mais ser editada.');"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="btn btn-success"
                            >
                                <i class="bi bi-check-circle"></i>
                                Finalizar nota
                            </button>
                        </form>
                    @endif

                    @if($nota->status === 'Finalizado' && auth()->user() && auth()->user()->permitions != 2)
                        <form
                            action="{{ route('notas.cancelar', $nota->id) }}"
                            method="POST"
                            onsubmit="return confirm('Deseja cancelar esta nota? Os produtos serão devolvidos ao estoque e esta operação não poderá ser desfeita.');"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="btn btn-danger"
                            >
                                <i class="bi bi-x-circle"></i>
                                Cancelar nota
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================
         INFORMAÇÕES DA NOTA
    ========================================================== --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-file-earmark-text"></i>
                Informações da nota
            </h5>
        </div>

        <div class="card-body">
            <div class="row g-3">
                {{-- ID --}}
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <small class="text-muted d-block">
                            Número
                        </small>
                        <strong>
                            #{{ $nota->id }}
                        </strong>
                    </div>
                </div>

                {{-- TIPO --}}
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <small class="text-muted d-block">
                            Tipo
                        </small>
                        <strong>
                            {{ $nota->tipo ?? 'N/A' }}
                        </strong>
                    </div>
                </div>

                {{-- DATA --}}
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <small class="text-muted d-block">
                            Criada em
                        </small>
                        <strong>
                            {{ $nota->created_at ? $nota->created_at->format('d/m/Y H:i') : 'N/A' }}
                        </strong>
                    </div>
                </div>

                {{-- CLIENTE --}}
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <small class="text-muted d-block">
                            Cliente
                        </small>
                        <strong>
                            {{ $nota->cliente?->pessoa?->nome ?? 'Cliente Geral / Balcão' }}
                        </strong>
                    </div>
                </div>

                {{-- PLACA --}}
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <small class="text-muted d-block">
                            Placa
                        </small>
                        <strong>
                            {{ $nota->veiculosCliente?->placa ?? 'N/A' }}
                        </strong>
                    </div>
                </div>

                {{-- KM --}}
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <small class="text-muted d-block">
                            KM atual
                        </small>
                        <strong>
                            {{ $nota->km !== null ? number_format($nota->km, 0, ',', '.') . ' km' : 'N/A' }}
                        </strong>
                    </div>
                </div>

                {{-- PRÓXIMA TROCA --}}
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <small class="text-muted d-block">
                            Próxima troca de óleo
                        </small>
                        <strong>
                            {{ $nota->km_proxima_troca_oleo !== null ? number_format($nota->km_proxima_troca_oleo, 0, ',', '.') . ' km' : 'N/A' }}
                        </strong>
                    </div>
                </div>

                {{-- QUANTIDADE DE ITENS --}}
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <small class="text-muted d-block">
                            Itens
                        </small>
                        <strong>
                            {{ $nota->itens->count() }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================
         ITENS
    ========================================================== --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">
                <i class="bi bi-list-ul"></i>
                Itens da nota
            </h5>

            @if($nota->status === 'Aberto' && auth()->user() && auth()->user()->permitions != 2)
                <a
                    href="{{ route('notasitem.edit', $nota->id) }}"
                    class="btn btn-primary btn-sm"
                >
                    <i class="bi bi-pencil-square"></i>
                    Editar itens
                </a>
            @endif
        </div>

        <div class="card-body">
            @if($nota->itens->isEmpty())
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i>
                    Esta nota ainda não possui itens cadastrados.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>
                                    Tipo
                                </th>
                                <th>
                                    Descrição
                                </th>
                                <th>
                                    Qtd.
                                </th>
                                <th>
                                    Valor unit.
                                </th>
                                <th>
                                    Desconto
                                </th>
                                <th>
                                    Total
                                </th>
                                <th>
                                    Garantia
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($nota->itens as $item)
                                @php
                                    $valorUnitario = (float) ($item->valor_unitario ?? 0);
                                    $desconto = (float) ($item->desconto ?? 0);
                                    $quantidade = (float) ($item->quantidade ?? 0);
                                    $totalItem = max(0, ($quantidade * $valorUnitario) - $desconto);
                                @endphp

                                <tr>
                                    <td>
                                        @if($item->itemable_type === 'App\Models\Produto')
                                            <span class="badge bg-primary">
                                                <i class="bi bi-box-seam"></i>
                                                Produto
                                            </span>
                                        @elseif($item->itemable_type === 'App\Models\OrdemServico')
                                            <span class="badge bg-warning text-dark">
                                                <i class="bi bi-tools"></i>
                                                O.S.
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-question-circle"></i>
                                                Item
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $item->descricao ?? $item->itemable?->nome ?? $item->itemable?->descricao ?? 'Item sem descrição' }}
                                    </td>

                                    <td>
                                        {{ $quantidade }}
                                    </td>

                                    <td>
                                        R$ {{ number_format($valorUnitario, 2, ',', '.') }}
                                    </td>

                                    <td>
                                        R$ {{ number_format($desconto, 2, ',', '.') }}
                                    </td>

                                    <td>
                                        <strong>
                                            R$ {{ number_format($totalItem, 2, ',', '.') }}
                                        </strong>
                                    </td>

                                    <td>
                                        @if(($item->garantia_dias ?? 0) > 0)
                                            {{ $item->garantia_dias }} dias
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- =========================================================
         RESUMO FINANCEIRO
    ========================================================== --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-calculator"></i>
                Resumo financeiro
            </h5>
        </div>

        <div class="card-body">
            @php
                $subtotal = (float) ($nota->subtotal ?? 0);
                $descontoTotal = (float) ($nota->desconto ?? 0);
                $totalNota = (float) ($nota->total ?? 0);
            @endphp

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <small class="text-muted d-block">
                            Subtotal
                        </small>
                        <strong class="fs-5">
                            R$ {{ number_format($subtotal, 2, ',', '.') }}
                        </strong>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <small class="text-muted d-block">
                            Descontos
                        </small>
                        <strong class="fs-5">
                            R$ {{ number_format($descontoTotal, 2, ',', '.') }}
                        </strong>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <small class="text-muted d-block">
                            Total da nota
                        </small>
                        <strong class="fs-4">
                            R$ {{ number_format($totalNota, 2, ',', '.') }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================
         RODAPÉ
    ========================================================== --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <a
            href="{{ route('notas.index') }}"
            class="btn btn-secondary"
        >
            <i class="bi bi-arrow-left"></i>
            Voltar para notas
        </a>

        @if($nota->status === 'Aberto' && auth()->user() && auth()->user()->permitions != 2)
            <a
                href="{{ route('notasitem.edit', $nota->id) }}"
                class="btn btn-primary"
            >
                <i class="bi bi-pencil-square"></i>
                Editar nota
            </a>
        @endif
    </div>
</div>
@endsection
