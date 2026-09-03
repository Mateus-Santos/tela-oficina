@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="VISUALIZAR COMPRA"
        icon="bi-cart-check"
    />

    @if (session('success'))
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i>
            {{ session('error') }}
        </div>
    @endif


    {{-- CABEÇALHO DA COMPRA --}}
    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">

                <div>

                    <h2 class="h5 mb-1">
                        <i class="bi bi-receipt"></i>
                        Nota Fiscal #{{ $compra->numero_nf }}
                    </h2>

                    @if ($compra->serie_nf)
                        <div class="text-muted">
                            Série {{ $compra->serie_nf }}
                        </div>
                    @endif

                </div>

                @php
                    $statusConfig = match ($compra->status) {
                        'pendente' => [
                            'class' => 'bg-warning text-dark',
                            'icon' => 'bi-clock',
                            'label' => 'Pendente',
                        ],
                        'conferindo' => [
                            'class' => 'bg-info text-dark',
                            'icon' => 'bi-search',
                            'label' => 'Conferindo',
                        ],
                        'aprovada' => [
                            'class' => 'bg-success',
                            'icon' => 'bi-check-circle',
                            'label' => 'Aprovada',
                        ],
                        'cancelada' => [
                            'class' => 'bg-danger',
                            'icon' => 'bi-x-circle',
                            'label' => 'Cancelada',
                        ],
                        default => [
                            'class' => 'bg-secondary',
                            'icon' => 'bi-question-circle',
                            'label' => ucfirst($compra->status),
                        ],
                    };
                @endphp

                <span class="badge {{ $statusConfig['class'] }} fs-6">

                    <i class="bi {{ $statusConfig['icon'] }}"></i>

                    {{ $statusConfig['label'] }}

                </span>

            </div>

        </div>

    </div>


    {{-- DADOS DA NF --}}
    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <h2 class="h5 mb-3">
                <i class="bi bi-file-earmark-text"></i>
                Dados da nota fiscal
            </h2>

            <div class="row g-3">

                <div class="col-12 col-md-4">

                    <div class="text-muted small">
                        Fornecedor
                    </div>

                    <div class="fw-semibold">
                        <i class="bi bi-truck"></i>
                        {{ $compra->fornecedor->nome ?? 'Não informado' }}
                    </div>

                </div>

                <div class="col-12 col-md-2">

                    <div class="text-muted small">
                        Número
                    </div>

                    <div class="fw-semibold">
                        {{ $compra->numero_nf }}
                    </div>

                </div>

                <div class="col-12 col-md-2">

                    <div class="text-muted small">
                        Série
                    </div>

                    <div class="fw-semibold">
                        {{ $compra->serie_nf ?: '-' }}
                    </div>

                </div>

                <div class="col-12 col-md-2">

                    <div class="text-muted small">
                        Emissão
                    </div>

                    <div class="fw-semibold">
                        {{ $compra->data_emissao?->format('d/m/Y') ?? '-' }}
                    </div>

                </div>

                <div class="col-12 col-md-2">

                    <div class="text-muted small">
                        Entrada
                    </div>

                    <div class="fw-semibold">
                        {{ $compra->data_entrada?->format('d/m/Y') ?? '-' }}
                    </div>

                </div>

                @if ($compra->chave_nf)

                    <div class="col-12">

                        <div class="text-muted small">
                            Chave de acesso
                        </div>

                        <div class="fw-semibold text-break">
                            <i class="bi bi-upc-scan"></i>
                            {{ $compra->chave_nf }}
                        </div>

                    </div>

                @endif

            </div>

        </div>

    </div>


    {{-- ITENS --}}
    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">

                <h2 class="h5 mb-0">
                    <i class="bi bi-box-seam"></i>
                    Produtos da compra
                </h2>

                <span class="badge bg-secondary">
                    <i class="bi bi-boxes"></i>
                    {{ $compra->itens->count() }}
                    {{ $compra->itens->count() === 1 ? 'item' : 'itens' }}
                </span>

            </div>

            @if ($compra->itens->isNotEmpty())

                <div class="table-responsive">

                    <table class="table table-striped table-hover align-middle mb-0">

                        <thead>

                            <tr>
                                <th scope="col">PRODUTO</th>
                                <th scope="col">DESCRIÇÃO</th>
                                <th scope="col">QUANTIDADE</th>
                                <th scope="col">CONFERIDA</th>
                                <th scope="col">VALOR UNITÁRIO</th>
                                <th scope="col">DESCONTO</th>
                                <th scope="col">TOTAL</th>
                            </tr>

                        </thead>

                        <tbody>

                            @foreach ($compra->itens as $item)

                                <tr>

                                    <td>

                                        <strong>
                                            {{ $item->produto->nome ?? 'Produto não encontrado' }}
                                        </strong>

                                        @if ($item->produto?->codigo_fabricante)

                                            <br>

                                            <small class="text-muted">
                                                Código:
                                                {{ $item->produto->codigo_fabricante }}
                                            </small>

                                        @endif

                                    </td>

                                    <td>
                                        {{ $item->descricao }}
                                    </td>

                                    <td>
                                        {{ number_format((float) $item->quantidade, 3, ',', '.') }}
                                    </td>

                                    <td>

                                        @if ($item->quantidade_conferida !== null)

                                            @php
                                                $quantidade = (float) $item->quantidade;
                                                $conferida = (float) $item->quantidade_conferida;
                                                $conferenteIgual = abs($quantidade - $conferida) < 0.0001;
                                            @endphp

                                            <span class="badge {{ $conferenteIgual ? 'bg-success' : 'bg-warning text-dark' }}">

                                                <i class="bi {{ $conferenteIgual ? 'bi-check-circle' : 'bi-exclamation-triangle' }}"></i>

                                                {{ number_format($conferida, 3, ',', '.') }}

                                            </span>

                                        @else

                                            <span class="text-muted">

                                                <i class="bi bi-dash-circle"></i>
                                                Não conferida

                                            </span>

                                        @endif

                                    </td>

                                    <td>
                                        R$
                                        {{ number_format((float) $item->valor_unitario, 2, ',', '.') }}
                                    </td>

                                    <td>
                                        R$
                                        {{ number_format((float) $item->desconto, 2, ',', '.') }}
                                    </td>

                                    <td>

                                        <strong>
                                            R$
                                            {{ number_format((float) $item->valor_total, 2, ',', '.') }}
                                        </strong>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @else

                <div class="alert alert-warning mb-0">

                    <i class="bi bi-exclamation-triangle"></i>
                    Esta compra não possui produtos cadastrados.

                </div>

            @endif

        </div>

    </div>


    {{-- RESUMO FINANCEIRO --}}
    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <h2 class="h5 mb-3">
                <i class="bi bi-calculator"></i>
                Resumo financeiro
            </h2>

            <div class="row g-3">

                <div class="col-12 col-md-3">

                    <div class="text-muted small">
                        Produtos
                    </div>

                    <div class="fw-semibold">
                        R$
                        {{ number_format((float) $compra->valor_produtos, 2, ',', '.') }}
                    </div>

                </div>

                <div class="col-12 col-md-3">

                    <div class="text-muted small">
                        Desconto
                    </div>

                    <div class="fw-semibold">
                        R$
                        {{ number_format((float) $compra->desconto, 2, ',', '.') }}
                    </div>

                </div>

                <div class="col-12 col-md-3">

                    <div class="text-muted small">
                        Frete
                    </div>

                    <div class="fw-semibold">
                        R$
                        {{ number_format((float) $compra->frete, 2, ',', '.') }}
                    </div>

                </div>

                <div class="col-12 col-md-3">

                    <div class="text-muted small">
                        Outras despesas
                    </div>

                    <div class="fw-semibold">
                        R$
                        {{ number_format((float) $compra->outras_despesas, 2, ',', '.') }}
                    </div>

                </div>

                <div class="col-12">

                    <hr>

                    <div class="d-flex justify-content-end align-items-center gap-3">

                        <span class="text-muted">
                            Valor total:
                        </span>

                        <strong class="fs-4">
                            R$
                            {{ number_format((float) $compra->valor_total, 2, ',', '.') }}
                        </strong>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- OBSERVAÇÕES --}}
    @if ($compra->observacoes)

        <div class="card shadow-sm mb-4">

            <div class="card-body">

                <h2 class="h5 mb-3">
                    <i class="bi bi-chat-left-text"></i>
                    Observações
                </h2>

                <div class="text-break">
                    {!! nl2br(e($compra->observacoes)) !!}
                </div>

            </div>

        </div>

    @endif


    {{-- ANEXOS --}}
    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <div class="d-flex align-items-center justify-content-between gap-2">

                <h2 class="h5 mb-0">
                    <i class="bi bi-paperclip"></i>
                    Documentos e anexos
                </h2>

                <span class="badge bg-secondary">
                    Em breve
                </span>

            </div>

            <p class="text-muted mb-0 mt-3">

                <i class="bi bi-info-circle"></i>

                Os documentos da compra, como NF, XML, fotos e outros arquivos,
                serão disponibilizados nesta seção.

            </p>

        </div>

    </div>


    {{-- AÇÕES --}}
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mt-4">

        <a
            href="{{ route('compras.index') }}"
            class="btn btn-secondary"
        >
            <i class="bi bi-arrow-left"></i>
            Voltar
        </a>

        <div class="d-flex gap-2">

            @if (in_array($compra->status, ['pendente', 'conferindo'], true))

                <a
                    href="{{ route('compras.edit', $compra) }}"
                    class="btn btn-primary"
                >
                    <i class="bi bi-pencil"></i>
                    Editar
                </a>

            @endif

            @if ($compra->status === 'pendente')

                <button
                    type="button"
                    class="btn btn-success"
                    disabled
                    title="A aprovação será disponibilizada após a implementação da conferência"
                >
                    <i class="bi bi-check-circle"></i>
                    Aprovar
                </button>

            @endif

            @if ($compra->status === 'aprovada')

                <span class="text-success d-flex align-items-center">
                    <i class="bi bi-check-circle me-1"></i>
                    Compra aprovada
                </span>

            @endif

        </div>

    </div>

</div>

@endsection
