@extends('layouts.layout')

@section('content')

<section class="container cadastro">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h1>
            <i class="bi bi-box-seam"></i>
            VISUALIZAR PRODUTO
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

    {{-- INFORMAÇÕES PRINCIPAIS --}}
    <div class="card mb-4">

        <div class="card-header">

            <strong>
                <i class="bi bi-info-circle"></i>
                Informações do produto
            </strong>

        </div>

        <div class="card-body">

            <div class="row g-4">

                {{-- IMAGENS --}}
                <div class="col-12 col-md-4">

                    @php
                        $totalImagens = $produto->imagens->count();
                    @endphp

                    @if($totalImagens > 0)

                        <div
                            id="produtoImagensCarousel"
                            class="carousel slide border rounded bg-light overflow-hidden"
                            data-bs-ride="false"
                        >

                            <div class="carousel-inner">

                                @foreach($produto->imagens as $imagem)

                                    <div
                                        class="carousel-item {{ $loop->first ? 'active' : '' }}"
                                    >

                                        <img
                                            src="{{ asset('storage/' . $imagem->caminho) }}"
                                            class="d-block w-100"
                                            alt="{{ $produto->nome }}"
                                            style="
                                                height:300px;
                                                object-fit:contain;
                                                padding:10px;
                                            "
                                        >

                                    </div>

                                @endforeach

                            </div>

                            @if($totalImagens > 1)

                                <button
                                    class="carousel-control-prev"
                                    type="button"
                                    data-bs-target="#produtoImagensCarousel"
                                    data-bs-slide="prev"
                                >
                                    <span
                                        class="d-flex align-items-center justify-content-center bg-dark bg-opacity-75 rounded-circle"
                                        style="width:42px;height:42px;"
                                    >
                                        <span
                                            class="carousel-control-prev-icon"
                                            aria-hidden="true"
                                        ></span>
                                    </span>

                                    <span class="visually-hidden">
                                        Anterior
                                    </span>
                                </button>

                                <button
                                    class="carousel-control-next"
                                    type="button"
                                    data-bs-target="#produtoImagensCarousel"
                                    data-bs-slide="next"
                                >
                                    <span
                                        class="d-flex align-items-center justify-content-center bg-dark bg-opacity-75 rounded-circle"
                                        style="width:42px;height:42px;"
                                    >
                                        <span
                                            class="carousel-control-next-icon"
                                            aria-hidden="true"
                                        ></span>
                                    </span>

                                    <span class="visually-hidden">
                                        Próxima
                                    </span>
                                </button>

                            @endif

                        </div>

                        @if($totalImagens > 1)

                            <div class="text-center mt-2">

                                <span class="badge text-bg-dark">
                                    <i class="bi bi-images me-1"></i>
                                    {{ $totalImagens }} imagens
                                </span>

                            </div>

                        @endif

                    @else

                        <div class="d-flex flex-column align-items-center justify-content-center bg-light border rounded p-5">

                            <i class="bi bi-image fs-1 text-secondary"></i>

                            <span class="text-dark fw-medium mt-2">
                                Sem imagens
                            </span>

                        </div>

                    @endif

                </div>

                {{-- DADOS PRINCIPAIS --}}
                <div class="col-12 col-md-8">

                    <div class="row g-3">

                        <div class="col-12">

                            <span class="text-body-secondary d-block">
                                Nome
                            </span>

                            <strong class="fs-5">
                                {{ $produto->nome }}
                            </strong>

                        </div>

                        {{-- MARCA + LOGO --}}
                        <div class="col-12 col-md-6">

                            <span class="text-body-secondary d-block mb-1">
                                Marca
                            </span>

                            <div class="d-flex align-items-center gap-3">

                                <div
                                    class="border rounded bg-light d-flex align-items-center justify-content-center"
                                    style="
                                        width:60px;
                                        height:60px;
                                        flex:0 0 60px;
                                        overflow:hidden;
                                    "
                                >

                                    @if($produto->marcaRelacionada?->logo_path)

                                        <img
                                            src="{{ asset('storage/' . $produto->marcaRelacionada->logo_path) }}"
                                            alt="Logo {{ $produto->marcaRelacionada->nome }}"
                                            style="
                                                width:100%;
                                                height:100%;
                                                object-fit:contain;
                                                padding:5px;
                                            "
                                        >

                                    @elseif($produto->marcaRelacionada?->logo_url)

                                        <img
                                            src="{{ $produto->marcaRelacionada->logo_url }}"
                                            alt="Logo {{ $produto->marcaRelacionada->nome }}"
                                            style="
                                                width:100%;
                                                height:100%;
                                                object-fit:contain;
                                                padding:5px;
                                            "
                                        >

                                    @else

                                        <i class="bi bi-tag fs-4 text-secondary"></i>

                                    @endif

                                </div>

                                <strong>
                                    {{ $produto->marcaRelacionada?->nome ?: 'Não informada' }}
                                </strong>

                            </div>

                        </div>

                        <div class="col-12 col-md-6">

                            <span class="text-body-secondary d-block">
                                Código do fabricante
                            </span>

                            <strong>
                                {{ $produto->codigo_fabricante }}
                            </strong>

                        </div>

                        <div class="col-12 col-md-6">

                            <span class="text-body-secondary d-block">
                                Código de barras
                            </span>

                            <strong>
                                {{ $produto->codigo_barras ?: 'Não informado' }}
                            </strong>

                        </div>

                        <div class="col-12 col-md-6">

                            <span class="text-body-secondary d-block">
                                ID do produto
                            </span>

                            <strong>
                                {{ $produto->id }}
                            </strong>

                        </div>

                        <div class="col-12">

                            <span class="text-body-secondary d-block">
                                Descrição
                            </span>

                            <span>
                                {{ $produto->descricao ?: 'Não informada' }}
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    {{-- ESTOQUE E VALORES --}}
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

                    <span class="text-body-secondary d-block">
                        Estoque atual
                    </span>

                    <strong>
                        {{ $produto->quantidade }}
                    </strong>

                </div>

                <div class="col-12 col-md-4">

                    <span class="text-body-secondary d-block">
                        Estoque mínimo
                    </span>

                    <strong>
                        {{ $produto->estoque_minimo }}
                    </strong>

                </div>

                <div class="col-12 col-md-4">

                    <span class="text-body-secondary d-block">
                        Preço unitário
                    </span>

                    <strong>
                        R$ {{ number_format($produto->preco_uni, 2, ',', '.') }}
                    </strong>

                </div>

                <div class="col-12 col-md-4">

                    <span class="text-body-secondary d-block mb-1">
                        Status
                    </span>

                    @if($produto->status)

                        <span class="badge text-bg-success">
                            <i class="bi bi-check-circle me-1"></i>
                            Ativo
                        </span>

                    @else

                        <span class="badge text-bg-secondary">
                            <i class="bi bi-x-circle me-1"></i>
                            Inativo
                        </span>

                    @endif

                </div>

            </div>

        </div>

    </div>

    {{-- DADOS FISCAIS E COMERCIAIS --}}
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

                    <span class="text-body-secondary d-block">
                        NCM
                    </span>

                    <strong>
                        {{ $produto->ncm ?: 'Não informado' }}
                    </strong>

                </div>

                <div class="col-12 col-md-4">

                    <span class="text-body-secondary d-block">
                        CEST
                    </span>

                    <strong>
                        {{ $produto->cest ?: 'Não informado' }}
                    </strong>

                </div>

                <div class="col-12 col-md-4">

                    <span class="text-body-secondary d-block">
                        EX-TIPI
                    </span>

                    <strong>
                        {{ $produto->ex_tipi ?: 'Não informado' }}
                    </strong>

                </div>

                <div class="col-12 col-md-4">

                    <span class="text-body-secondary d-block">
                        Origem da mercadoria
                    </span>

                    <strong>
                        {{ $produto->origem_mercadoria !== null
                            ? $produto->origem_mercadoria
                            : 'Não informado' }}
                    </strong>

                </div>

                <div class="col-12 col-md-4">

                    <span class="text-body-secondary d-block">
                        Unidade comercial
                    </span>

                    <strong>
                        {{ $produto->unidade_comercial ?: 'Não informado' }}
                    </strong>

                </div>

                <div class="col-12 col-md-4">

                    <span class="text-body-secondary d-block">
                        Unidade tributável
                    </span>

                    <strong>
                        {{ $produto->unidade_tributavel ?: 'Não informado' }}
                    </strong>

                </div>

                <div class="col-12 col-md-4">

                    <span class="text-body-secondary d-block">
                        Fator de conversão
                    </span>

                    <strong>
                        {{ $produto->fator_conversao !== null
                            ? number_format(
                                (float) $produto->fator_conversao,
                                6,
                                ',',
                                '.'
                            )
                            : 'Não informado' }}
                    </strong>

                </div>

                <div class="col-12 col-md-4">

                    <span class="text-body-secondary d-block">
                        Peso líquido
                    </span>

                    <strong>
                        {{ $produto->peso_liquido !== null
                            ? number_format(
                                (float) $produto->peso_liquido,
                                3,
                                ',',
                                '.'
                            ) . ' kg'
                            : 'Não informado' }}
                    </strong>

                </div>

                <div class="col-12 col-md-4">

                    <span class="text-body-secondary d-block">
                        Peso bruto
                    </span>

                    <strong>
                        {{ $produto->peso_bruto !== null
                            ? number_format(
                                (float) $produto->peso_bruto,
                                3,
                                ',',
                                '.'
                            ) . ' kg'
                            : 'Não informado' }}
                    </strong>

                </div>

            </div>

        </div>

    </div>

    {{-- FORNECEDOR --}}
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

                        <span class="text-body-secondary d-block">
                            Nome
                        </span>

                        <strong>
                            {{ $produto->fornecedor->nome }}
                        </strong>

                    </div>

                    <div class="col-12 col-md-6">

                        <span class="text-body-secondary d-block">
                            CNPJ
                        </span>

                        <strong>
                            {{ $produto->fornecedor->cnpj ?: 'Não informado' }}
                        </strong>

                    </div>

                </div>

            @else

                <span class="text-dark-emphasis">
                    Nenhum fornecedor vinculado.
                </span>

            @endif

        </div>

    </div>

    {{-- APLICAÇÕES --}}
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

                <span class="text-dark-emphasis">
                    Nenhuma aplicação cadastrada.
                </span>

            @endif

        </div>

    </div>

</section>

@endsection
