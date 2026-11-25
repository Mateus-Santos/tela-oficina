@extends('layouts.layout')

@section('content')

<div class="container cadastro feux-moderno">

    <x-list-header
        title="MOVIMENTAÇÕES DE ESTOQUE"
        icon="bi-arrow-left-right"
        create-route="estoque.index"
        create-text="Voltar ao Estoque"
        create-icon="bi-boxes"
    />

    @if(request()->filled('produto_id'))
        <div class="alert alert-info d-flex align-items-center justify-content-between gap-2 flex-wrap" role="status">
            <span>Movimentações do produto #{{ request('produto_id') }}</span>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('estoque.index', ['produto_id' => request('produto_id')]) }}">Voltar ao estoque deste produto</a>
        </div>
    @endif

    <x-filtros-container
        action="{{ route('estoque.movimentacoes.index') }}"
        id="filtros-movimentacoes"
        :collapsible="true"
        :expanded="request()->hasAny([
            'tipo',
            'data_inicio',
            'data_fim'
        ])"
    >

        <x-slot:primary>
            @if(request()->filled('produto_id'))
                <input type="hidden" name="produto_id" value="{{ request('produto_id') }}">
            @endif

            <div class="row g-3 align-items-end">

                <div class="col-12 col-md-8">

                    <label
                        for="produto"
                        class="form-label"
                    >
                        Produto
                    </label>

                    <input
                        type="text"
                        name="produto"
                        id="produto"
                        class="filtros-container__input"
                        placeholder="Nome, código do fabricante ou código de barras"
                        value="{{ request('produto') }}"
                        autocomplete="off"
                    >

                </div>

                <div class="col-12 col-md-4">

                    <div class="filtros-container__actions">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-search"></i>
                            Filtrar
                        </button>

                        <a
                            href="{{ route('estoque.movimentacoes.index') }}"
                            class="btn btn-secondary"
                            title="Limpar filtros"
                        >
                            <i class="bi bi-x-lg"></i>
                            Limpar
                        </a>

                    </div>

                </div>

            </div>

        </x-slot:primary>

        <x-slot:advanced>
            @if(request()->filled('produto_id'))
                <input type="hidden" name="produto_id" value="{{ request('produto_id') }}">
            @endif

            <div class="row g-3">

                <div class="col-12 col-md-4">

                    <label
                        for="tipo"
                        class="form-label"
                    >
                        Tipo
                    </label>

                    <select
                        name="tipo"
                        id="tipo"
                        class="filtros-container__input"
                    >

                        <option value="">
                            Todos
                        </option>

                        <option
                            value="entrada"
                            @selected(request('tipo') === 'entrada')
                        >
                            Entrada
                        </option>

                        <option
                            value="saida"
                            @selected(request('tipo') === 'saida')
                        >
                            Saída
                        </option>

                        <option
                            value="ajuste"
                            @selected(request('tipo') === 'ajuste')
                        >
                            Ajuste
                        </option>

                    </select>

                </div>

                <div class="col-12 col-md-4">

                    <label
                        for="data_inicio"
                        class="form-label"
                    >
                        Data inicial
                    </label>

                    <input
                        type="date"
                        name="data_inicio"
                        id="data_inicio"
                        class="form-control"
                        value="{{ request('data_inicio') }}"
                    >

                </div>

                <div class="col-12 col-md-4">

                    <label
                        for="data_fim"
                        class="form-label"
                    >
                        Data final
                    </label>

                    <input
                        type="date"
                        name="data_fim"
                        id="data_fim"
                        class="form-control"
                        value="{{ request('data_fim') }}"
                    >

                </div>

            </div>

        </x-slot:advanced>

    </x-filtros-container>

    @if($movimentacoes->isEmpty())

        @php
            $temFiltros = request()->hasAny([
                'produto',
                'produto_id',
                'tipo',
                'data_inicio',
                'data_fim',
            ]);
        @endphp

        <div class="alert alert-{{ $temFiltros ? 'warning' : 'info' }}">

            <i class="bi {{ $temFiltros
                ? 'bi-exclamation-triangle'
                : 'bi-info-circle' }}">
            </i>

            {{ $temFiltros
                ? 'Nenhuma movimentação encontrada com os filtros informados.'
                : 'Nenhuma movimentação de estoque registrada.' }}

        </div>

    @else

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>
                        <th>
                            Data
                        </th>

                        <th>
                            Produto
                        </th>

                        <th class="text-center">
                            Tipo
                        </th>

                        <th class="text-center">
                            Movimentação
                        </th>

                        <th class="text-center">
                            Saldo
                        </th>

                        <th class="text-end">
                            Valor unitário
                        </th>

                        <th>
                            Usuário
                        </th>

                        <th>
                            Origem
                        </th>
                    </tr>

                </thead>

                <tbody>

                    @foreach($movimentacoes as $movimentacao)

                        @php
                            $produto = $movimentacao->produto;

                            $imagemProduto =
                                $produto
                                    ?->imagens
                                    ?->first()
                                    ?->caminho;

                            $saldoAnterior =
                                (float) $movimentacao->saldo_anterior;

                            $saldoPosterior =
                                (float) $movimentacao->saldo_posterior;

                            $diferenca =
                                $saldoPosterior - $saldoAnterior;

                            if ($movimentacao->tipo === 'entrada') {
                                $sinal = '+';
                                $classeQuantidade = 'text-success';
                            } elseif ($movimentacao->tipo === 'saida') {
                                $sinal = '-';
                                $classeQuantidade = 'text-danger';
                            } elseif ($diferenca > 0) {
                                $sinal = '+';
                                $classeQuantidade = 'text-success';
                            } elseif ($diferenca < 0) {
                                $sinal = '-';
                                $classeQuantidade = 'text-danger';
                            } else {
                                $sinal = '';
                                $classeQuantidade = 'text-muted';
                            }

                            $origem = $movimentacao->origem;
                        @endphp

                        <tr>

                            <td class="text-nowrap">

                                <div class="fw-semibold">
                                    {{ $movimentacao->created_at->format('d/m/Y') }}
                                </div>

                                <small class="text-muted">
                                    {{ $movimentacao->created_at->format('H:i') }}
                                </small>

                            </td>

                            <td>

                                @if($produto)

                                    <div class="d-flex align-items-center gap-2">

                                        @if($imagemProduto)

                                            <img
                                                src="{{ asset('storage/'.$imagemProduto) }}"
                                                alt="{{ $produto->nome }}"
                                                class="rounded border flex-shrink-0"
                                                style="
                                                    width: 48px;
                                                    height: 48px;
                                                    object-fit: contain;
                                                    background: #fff;
                                                "
                                            >

                                        @else

                                            <div
                                                class="rounded border bg-light d-flex align-items-center justify-content-center text-muted flex-shrink-0"
                                                style="
                                                    width: 48px;
                                                    height: 48px;
                                                "
                                            >
                                                <i class="bi bi-image"></i>
                                            </div>

                                        @endif

                                        <div>

                                            <div class="fw-semibold">
                                                {{ $produto->nome }}
                                            </div>

                                            @if($produto->marcaRelacionada?->nome)

                                                <div class="small text-muted">
                                                    {{ $produto->marcaRelacionada->nome }}
                                                </div>

                                            @endif

                                            <div class="small">

                                                @if($produto->codigo)

                                                    <span class="me-2">
                                                        <span class="text-muted">
                                                            Código:
                                                        </span>

                                                        <strong>
                                                            {{ $produto->codigo }}
                                                        </strong>
                                                    </span>

                                                @endif

                                                @if($produto->codigo_fabricante)

                                                    <span class="me-2">
                                                        <span class="text-muted">
                                                            Fab.:
                                                        </span>

                                                        {{ $produto->codigo_fabricante }}
                                                    </span>

                                                @endif

                                                @if($produto->codigo_barras)

                                                    <span>
                                                        <span class="text-muted">
                                                            Barras:
                                                        </span>

                                                        {{ $produto->codigo_barras }}
                                                    </span>

                                                @endif

                                            </div>

                                        </div>

                                    </div>

                                @else

                                    <span class="text-muted">
                                        <i class="bi bi-box-seam me-1"></i>
                                        Produto removido
                                    </span>

                                @endif

                            </td>

                            <td class="text-center">

                                @switch($movimentacao->tipo)

                                    @case('entrada')

                                        <span class="badge bg-success">

                                            <i class="bi bi-arrow-down-circle me-1"></i>

                                            Entrada

                                        </span>

                                        @break

                                    @case('saida')

                                        <span class="badge bg-danger">

                                            <i class="bi bi-arrow-up-circle me-1"></i>

                                            Saída

                                        </span>

                                        @break

                                    @case('ajuste')

                                        <span class="badge bg-warning text-dark">

                                            <i class="bi bi-sliders me-1"></i>

                                            Ajuste

                                        </span>

                                        @break

                                    @default

                                        <span class="badge bg-secondary">
                                            {{ ucfirst($movimentacao->tipo) }}
                                        </span>

                                @endswitch

                            </td>

                            <td class="text-center">

                                <strong class="{{ $classeQuantidade }}">

                                    {{ $sinal }}{{ number_format(
                                        (float) $movimentacao->quantidade,
                                        3,
                                        ',',
                                        '.'
                                    ) }}

                                </strong>

                                @if($movimentacao->tipo === 'ajuste')

                                    <div class="small text-muted mt-1">

                                        @if($diferenca > 0)
                                            Aumentou o estoque
                                        @elseif($diferenca < 0)
                                            Reduziu o estoque
                                        @endif

                                    </div>

                                @endif

                            </td>

                            <td class="text-center text-nowrap">

                                <span class="text-muted">
                                    {{ number_format(
                                        $saldoAnterior,
                                        3,
                                        ',',
                                        '.'
                                    ) }}
                                </span>

                                <i class="bi bi-arrow-right mx-1"></i>

                                <strong>
                                    {{ number_format(
                                        $saldoPosterior,
                                        3,
                                        ',',
                                        '.'
                                    ) }}
                                </strong>

                            </td>

                            <td class="text-end text-nowrap">

                                @if($movimentacao->valor_unitario !== null)

                                    R$ {{ number_format(
                                        (float) $movimentacao->valor_unitario,
                                        2,
                                        ',',
                                        '.'
                                    ) }}

                                @else

                                    <span class="text-muted">
                                        —
                                    </span>

                                @endif

                            </td>

                            <td>

                                <div class="text-nowrap">

                                    <i class="bi bi-person me-1 text-muted"></i>

                                    {{ $movimentacao->usuario?->name
                                        ?? 'Sistema' }}

                                </div>

                            </td>

                            <td>

                                {{-- O ID mostrado e o da Compra/Nota; nunca o ID tecnico do item. --}}
                                @if($origem instanceof \App\Models\CompraItem)
                                    @if($origem->compra)
                                        <a class="fw-semibold" href="{{ route('compras.show', $origem->compra->id) }}">
                                            <i class="bi bi-cart-check me-1"></i>Compra #{{ $origem->compra->id }}
                                        </a>
                                    @else
                                        <span class="text-muted">Compra indisponível</span>
                                    @endif
                                @elseif($origem instanceof \App\Models\CompraDevolucaoItem)
                                    @if($origem->devolucao?->compra)
                                        <a class="fw-semibold" href="{{ route('compras.show', $origem->devolucao->compra->id) }}">
                                            <i class="bi bi-arrow-return-left me-1"></i>Devolução da Compra #{{ $origem->devolucao->compra->id }}
                                        </a>
                                    @else
                                        <span class="text-muted">Compra da devolução indisponível</span>
                                    @endif
                                @elseif($origem instanceof \App\Models\NotasItem)
                                    @if($origem->nota)
                                        <a class="fw-semibold" href="{{ route('notas.show', $origem->nota->id) }}">
                                            <i class="bi bi-receipt me-1"></i>Nota #{{ $origem->nota->id }}
                                        </a>
                                    @else
                                        <span class="text-muted">Nota indisponível</span>
                                    @endif
                                @elseif($origem instanceof \App\Models\Compra)
                                    <a class="fw-semibold" href="{{ route('compras.show', $origem->id) }}">Compra #{{ $origem->id }}</a>
                                @elseif($origem instanceof \App\Models\Nota)
                                    <a class="fw-semibold" href="{{ route('notas.show', $origem->id) }}">Nota #{{ $origem->id }}</a>
                                @elseif($origem)
                                    <span class="text-muted">{{ class_basename($origem) }} #{{ $origem->id }}</span>
                                @elseif($movimentacao->origem_type && $movimentacao->origem_id)
                                    <span class="text-muted">Registro de origem indisponível</span>
                                @else
                                    <span class="text-muted"><i class="bi bi-person-gear me-1"></i>Manual</span>
                                @endif

                                @if($movimentacao->observacoes)

                                    <div
                                        class="small text-muted mt-1"
                                        title="{{ $movimentacao->observacoes }}"
                                    >
                                        <i class="bi bi-chat-left-text me-1"></i>

                                        {{ \Illuminate\Support\Str::limit(
                                            $movimentacao->observacoes,
                                            70
                                        ) }}
                                    </div>

                                @endif

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        @if($movimentacoes->hasPages())

            <div class="d-flex justify-content-center mt-4">
                {{ $movimentacoes->links() }}
            </div>

        @endif

    @endif

</div>

@endsection
