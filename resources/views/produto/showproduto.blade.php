@extends('layouts.layout')

@section('content')

<section class="container cadastro">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="bi bi-box-seam"></i> VISUALIZAR PRODUTO
        </h1>

        <div class="d-flex gap-2">
            <a
                href="{{ route('produtos.index') }}"
                class="btn btn-secondary"
            >
                <i class="bi bi-arrow-left"></i>
                Voltar
            </a>

            @if(auth()->user() && auth()->user()->permitions === 1)
                <a
                    href="{{ route('produtos.edit', $produto->id) }}"
                    class="btn btn-primary"
                >
                    <i class="bi bi-pencil"></i>
                    Editar
                </a>
            @endif
        </div>
    </div>

    {{-- Informações principais --}}
    <div class="card mb-4">
        <div class="card-header">
            <strong>
                <i class="bi bi-info-circle"></i>
                Informações do produto
            </strong>
        </div>

        <div class="card-body">
            <div class="row g-4">
                {{-- Imagens --}}
                <div class="col-12 col-md-4">
                    @php
                        $fotos = $produto->anexosVinculos
                            ->where('tipo', 'foto')
                            ->sortBy('created_at')
                            ->map(fn ($vinculo) => $vinculo->anexo)
                            ->filter()
                            ->values();
                    @endphp

                    @if($produto->img || $fotos->isNotEmpty())
                        <div id="produtoImagensCarousel" class="carousel slide" data-bs-ride="false">
                            <div class="carousel-inner rounded">
                                @if($produto->img)
                                    <div class="carousel-item active">
                                        <img
                                            src="{{ asset('storage/' . $produto->img) }}"
                                            class="d-block w-100"
                                            alt="{{ $produto->nome }}"
                                            style="height: 280px; object-fit: contain;"
                                        >
                                    </div>
                                @endif

                                @foreach($fotos as $foto)
                                    <div class="carousel-item {{ !$produto->img && $loop->first ? 'active' : '' }}">
                                        <img
                                            src="{{ asset('storage/' . $foto->arquivo) }}"
                                            class="d-block w-100"
                                            alt="{{ $foto->nome_original }}"
                                            style="height: 280px; object-fit: contain;"
                                        >
                                    </div>
                                @endforeach
                            </div>

                            @if(($produto->img ? 1 : 0) + $fotos->count() > 1)
                                <button
                                    class="carousel-control-prev"
                                    type="button"
                                    data-bs-target="#produtoImagensCarousel"
                                    data-bs-slide="prev"
                                >
                                    <span class="carousel-control-prev-icon"></span>
                                    <span class="visually-hidden">Anterior</span>
                                </button>

                                <button
                                    class="carousel-control-next"
                                    type="button"
                                    data-bs-target="#produtoImagensCarousel"
                                    data-bs-slide="next"
                                >
                                    <span class="carousel-control-next-icon"></span>
                                    <span class="visually-hidden">Próxima</span>
                                </button>
                            @endif
                        </div>
                    @else
                        <div class="d-flex flex-column align-items-center justify-content-center bg-light rounded p-5">
                            <i class="bi bi-image fs-1 text-secondary"></i>
                            <span class="text-muted mt-2">
                                Sem imagens
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Dados principais --}}
                <div class="col-12 col-md-8">
                    <div class="row g-3">
                        <div class="col-12">
                            <span class="text-muted d-block">Nome</span>
                            <strong>{{ $produto->nome }}</strong>
                        </div>

                        <div class="col-12 col-md-6">
                            <span class="text-muted d-block">Marca</span>
                            <strong>
                                {{ $produto->marcaRelacionada?->nome ?: 'Não informada' }}
                            </strong>
                        </div>

                        <div class="col-12 col-md-6">
                            <span class="text-muted d-block">Código do fabricante</span>
                            <strong>{{ $produto->codigo_fabricante }}</strong>
                        </div>

                        <div class="col-12 col-md-6">
                            <span class="text-muted d-block">Código de barras</span>
                            <strong>
                                {{ $produto->codigo_barras ?: 'Não informado' }}
                            </strong>
                        </div>

                        <div class="col-12 col-md-6">
                            <span class="text-muted d-block">ID do produto</span>
                            <strong>{{ $produto->id }}</strong>
                        </div>

                        <div class="col-12">
                            <span class="text-muted d-block">Descrição</span>
                            <span>{{ $produto->descricao ?: 'Não informada' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Estoque e valores --}}
    <div class="card mb-4">
        <div class="card-header">
            <strong>
                <i class="bi bi-boxes"></i>
                Estoque e valores
            </strong>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <span class="text-muted d-block">Estoque atual</span>
                    <strong>{{ $produto->quantidade }}</strong>
                </div>

                <div class="col-12 col-md-4">
                    <span class="text-muted d-block">Estoque mínimo</span>
                    <strong>{{ $produto->estoque_minimo }}</strong>
                </div>

                <div class="col-12 col-md-4">
                    <span class="text-muted d-block">Preço unitário</span>
                    <strong>
                        R$ {{ number_format($produto->preco_uni, 2, ',', '.') }}
                    </strong>
                </div>

                <div class="col-12 col-md-4">
                    <span class="text-muted d-block">Status</span>

                    @if($produto->status)
                        <span class="badge bg-success">
                            Ativo
                        </span>
                    @else
                        <span class="badge bg-secondary">
                            Inativo
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Dados fiscais e comerciais --}}
    <div class="card mb-4">
        <div class="card-header">
            <strong>
                <i class="bi bi-receipt"></i>
                Dados fiscais e comerciais
            </strong>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <span class="text-muted d-block">NCM</span>
                    <strong>{{ $produto->ncm ?: 'Não informado' }}</strong>
                </div>

                <div class="col-12 col-md-4">
                    <span class="text-muted d-block">CEST</span>
                    <strong>{{ $produto->cest ?: 'Não informado' }}</strong>
                </div>

                <div class="col-12 col-md-4">
                    <span class="text-muted d-block">EX-TIPI</span>
                    <strong>{{ $produto->ex_tipi ?: 'Não informado' }}</strong>
                </div>

                <div class="col-12 col-md-4">
                    <span class="text-muted d-block">Origem da mercadoria</span>
                    <strong>
                        {{ $produto->origem_mercadoria !== null
                            ? $produto->origem_mercadoria
                            : 'Não informado' }}
                    </strong>
                </div>

                <div class="col-12 col-md-4">
                    <span class="text-muted d-block">Unidade comercial</span>
                    <strong>
                        {{ $produto->unidade_comercial ?: 'Não informado' }}
                    </strong>
                </div>

                <div class="col-12 col-md-4">
                    <span class="text-muted d-block">Unidade tributável</span>
                    <strong>
                        {{ $produto->unidade_tributavel ?: 'Não informado' }}
                    </strong>
                </div>

                <div class="col-12 col-md-4">
                    <span class="text-muted d-block">Fator de conversão</span>
                    <strong>
                        {{ $produto->fator_conversao !== null
                            ? number_format((float) $produto->fator_conversao, 6, ',', '.')
                            : 'Não informado' }}
                    </strong>
                </div>

                <div class="col-12 col-md-4">
                    <span class="text-muted d-block">Peso líquido</span>
                    <strong>
                        {{ $produto->peso_liquido !== null
                            ? number_format((float) $produto->peso_liquido, 3, ',', '.') . ' kg'
                            : 'Não informado' }}
                    </strong>
                </div>

                <div class="col-12 col-md-4">
                    <span class="text-muted d-block">Peso bruto</span>
                    <strong>
                        {{ $produto->peso_bruto !== null
                            ? number_format((float) $produto->peso_bruto, 3, ',', '.') . ' kg'
                            : 'Não informado' }}
                    </strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Fornecedor --}}
    <div class="card mb-4">
        <div class="card-header">
            <strong>
                <i class="bi bi-truck"></i>
                Fornecedor
            </strong>
        </div>

        <div class="card-body">
            @if($produto->fornecedor)
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <span class="text-muted d-block">Nome</span>
                        <strong>{{ $produto->fornecedor->nome }}</strong>
                    </div>

                    <div class="col-12 col-md-6">
                        <span class="text-muted d-block">CNPJ</span>
                        <strong>
                            {{ $produto->fornecedor->cnpj ?: 'Não informado' }}
                        </strong>
                    </div>
                </div>
            @else
                <span class="text-muted">
                    Nenhum fornecedor vinculado.
                </span>
            @endif
        </div>
    </div>

    {{-- Aplicações --}}
    <div class="card mb-4">
        <div class="card-header">
            <strong>
                <i class="bi bi-car-front"></i>
                Aplicações
            </strong>
        </div>

        <div class="card-body">
            @if($produto->veiculos->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Montadora</th>
                                <th>Veículo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($produto->veiculos as $veiculo)
                                <tr>
                                    <td>
                                        {{ $veiculo->montadora->nome ?? 'Não informado' }}
                                    </td>
                                    <td>
                                        {{ $veiculo->nome }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <span class="text-muted">
                    Nenhuma aplicação cadastrada.
                </span>
            @endif
        </div>
    </div>
</section>

@endsection
