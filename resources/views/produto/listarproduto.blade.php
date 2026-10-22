@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="LISTA DE PRODUTOS"
        icon="bi-box-seam"
        create-route="produtos.create"
        create-text="Novo Produto"
        create-icon="bi-plus-lg"
    />

    <x-filtros-container
        action="{{ route('produtos.index') }}"
        id="filtros-produtos"
        :collapsible="true"
        :expanded="request()->hasAny([
            'codigo',
            'codigo_fabricante',
            'codigo_barras',
            'nome',
            'marca',
            'status',
            'estoque',
            'imagem'
        ])"
    >

        <x-slot:primary>

            <div class="row g-3 align-items-end">

                {{-- Código do produto --}}
                <div class="col-12 col-md-3">

                    <label
                        for="codigo"
                        class="form-label"
                    >
                        Código do produto
                    </label>

                    <input
                        type="text"
                        name="codigo"
                        id="codigo"
                        class="filtros-container__input"
                        placeholder="Ex.: PRD-00001235"
                        value="{{ request('codigo') }}"
                        autocomplete="off"
                    >

                </div>

                {{-- Código do fabricante --}}
                <div class="col-12 col-md-3">

                    <label
                        for="codigo_fabricante"
                        class="form-label"
                    >
                        Código do fabricante
                    </label>

                    <input
                        type="text"
                        name="codigo_fabricante"
                        id="codigo_fabricante"
                        class="filtros-container__input"
                        placeholder="Digite o código do fabricante"
                        value="{{ request('codigo_fabricante') }}"
                        autocomplete="off"
                    >

                </div>

                {{-- Código de barras --}}
                <div class="col-12 col-md-3">

                    <label
                        for="codigo_barras"
                        class="form-label"
                    >
                        Código de barras
                    </label>

                    <input
                        type="text"
                        name="codigo_barras"
                        id="codigo_barras"
                        class="filtros-container__input"
                        placeholder="Digite o código de barras"
                        value="{{ request('codigo_barras') }}"
                        autocomplete="off"
                    >

                </div>

                {{-- Nome --}}
                <div class="col-12 col-md-3">

                    <label
                        for="nome"
                        class="form-label"
                    >
                        Nome do produto
                    </label>

                    <input
                        type="text"
                        name="nome"
                        id="nome"
                        class="filtros-container__input"
                        placeholder="Nome do produto"
                        value="{{ request('nome') }}"
                        autocomplete="off"
                    >

                </div>

                {{-- Imagem --}}
                <div class="col-12 col-md-3">

                    <label
                        for="imagem"
                        class="form-label"
                    >
                        Imagem
                    </label>

                    <select
                        name="imagem"
                        id="imagem"
                        class="filtros-container__input"
                    >
                        <option value="">
                            Todos
                        </option>

                        <option
                            value="com_imagem"
                            @selected(request('imagem') === 'com_imagem')
                        >
                            Com imagem
                        </option>

                        <option
                            value="sem_imagem"
                            @selected(request('imagem') === 'sem_imagem')
                        >
                            Sem imagem
                        </option>

                    </select>

                </div>

            </div>

            <div class="row mt-3">

                <div class="col-12">

                    <div class="filtros-container__actions">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-search"></i>
                            Filtrar
                        </button>

                        <a
                            href="{{ route('produtos.index') }}"
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

            <div class="row g-3">

                {{-- Marca --}}
                <div class="col-12 col-md-4">

                    <label
                        for="marca"
                        class="form-label"
                    >
                        Marca
                    </label>

                    <input
                        type="text"
                        name="marca"
                        id="marca"
                        class="filtros-container__input"
                        placeholder="Digite a marca"
                        value="{{ request('marca') }}"
                        autocomplete="off"
                    >

                </div>

                {{-- Status --}}
                <div class="col-12 col-md-4">

                    <label
                        for="status"
                        class="form-label"
                    >
                        Status
                    </label>

                    <select
                        name="status"
                        id="status"
                        class="filtros-container__input"
                    >
                        <option value="">
                            Todos
                        </option>

                        <option
                            value="1"
                            @selected(request('status') === '1')
                        >
                            Ativos
                        </option>

                        <option
                            value="0"
                            @selected(request('status') === '0')
                        >
                            Inativos
                        </option>

                    </select>

                </div>

                {{-- Estoque --}}
                <div class="col-12 col-md-4">

                    <label
                        for="estoque"
                        class="form-label"
                    >
                        Situação do estoque
                    </label>

                    <select
                        name="estoque"
                        id="estoque"
                        class="filtros-container__input"
                    >
                        <option value="">
                            Todos
                        </option>

                        <option
                            value="com_estoque"
                            @selected(request('estoque') === 'com_estoque')
                        >
                            Com estoque
                        </option>

                        <option
                            value="sem_estoque"
                            @selected(request('estoque') === 'sem_estoque')
                        >
                            Sem estoque
                        </option>

                        <option
                            value="estoque_baixo"
                            @selected(request('estoque') === 'estoque_baixo')
                        >
                            Estoque baixo
                        </option>

                    </select>

                </div>

            </div>

        </x-slot:advanced>

    </x-filtros-container>

    {{-- RESULTADO DA BUSCA --}}
    @if($produtos->isEmpty())

        @php
            $temFiltros = request()->hasAny([
                'nome',
                'codigo_barras',
                'codigo_fabricante',
                'marca',
                'status',
                'estoque',
                'imagem',
            ]);
        @endphp

        <div class="alert alert-{{ $temFiltros ? 'warning' : 'info' }}">

            <i
                class="bi {{ $temFiltros
                    ? 'bi-exclamation-triangle'
                    : 'bi-info-circle' }}"
            ></i>

            {{ $temFiltros
                ? 'Nenhum produto encontrado com os filtros informados.'
                : 'Nenhum produto cadastrado.' }}

        </div>

    @endif

    {{-- LISTAGEM --}}
    @foreach($produtos as $produto)

        @php
            $catalogo =
                $dadosCatalogoProdutos[
                    $produto->id
                ] ?? [];

            $imagemLocal =
                $produto
                    ->imagens
                    ->first()
                    ?->caminho;

            $imagemUrl =
                $imagemLocal
                    ? asset(
                        'storage/'.$imagemLocal
                    )
                    : (
                        $catalogo[
                            'imagem_url'
                        ] ?? null
                    );

            $fonteImagem =
                $imagemLocal
                    ? 'Jaime'
                    : (
                        $catalogo['fonte']
                        ?? null
                    );
        @endphp

        <article class="produto-container">

            <div class="produto-cabecalho">

                <div class="produto-cabecalho-identificacao">

                    <span class="produto-referencia">
                        Código {{ $produto->codigo }}
                        ·
                        Ref. {{ $produto->codigo_fabricante }}
                    </span>

                    @if($produto->status)

                        <span class="badge text-bg-success">
                            Ativo
                        </span>

                    @else

                        <span class="badge text-bg-secondary">
                            Inativo
                        </span>

                    @endif

                    @if(
                        $catalogo[
                            'possui_catalogo'
                        ] ?? false
                    )

                        <span class="badge text-bg-info">
                            <i class="bi bi-cloud-arrow-down me-1"></i>
                            {{ $catalogo['fonte']
                                ?: 'Catálogo externo' }}
                        </span>

                    @endif

                </div>

                <span class="produto-id">
                    Produto #{{ $produto->id }}
                </span>

            </div>

            <div class="produto-item">

                {{-- IMAGEM --}}
                <div class="produto-media">

                    @if($imagemUrl)

                        <img
                            class="produto-item-img"
                            src="{{ $imagemUrl }}"
                            alt="{{ $produto->nome }}"
                            loading="lazy"
                            @if(!$imagemLocal)
                                referrerpolicy="no-referrer"
                            @endif
                        >

                        @if($fonteImagem)

                            <span class="produto-imagem-origem">
                                {{ $fonteImagem }}
                            </span>

                        @endif

                    @else

                        <div class="produto-item-img produto-sem-imagem">

                            <i class="bi bi-image"></i>

                            <span>
                                Sem imagem
                            </span>

                        </div>

                    @endif

                </div>

                {{-- CONTEÚDO --}}
                <div class="produto-conteudo">

                    <div class="produto-identificacao">

                        <div class="produto-marca-logo">

                            @if(
                                $produto
                                    ->marcaRelacionada
                                    ?->logo_path
                            )

                                <img
                                    src="{{ asset(
                                        'storage/'
                                        .$produto
                                            ->marcaRelacionada
                                            ->logo_path
                                    ) }}"
                                    alt="Logo {{ $produto->marcaRelacionada->nome }}"
                                >

                            @elseif(
                                $produto
                                    ->marcaRelacionada
                                    ?->logo_url
                            )

                                <img
                                    src="{{ $produto->marcaRelacionada->logo_url }}"
                                    alt="Logo {{ $produto->marcaRelacionada->nome }}"
                                >

                            @else

                                <i class="bi bi-tag"></i>

                            @endif

                        </div>

                        <div class="produto-identificacao-info">

                            <span class="produto-marca">
                                {{ $produto
                                    ->marcaRelacionada
                                    ?->nome
                                    ?: 'Marca não informada' }}
                            </span>

                            <strong class="produto-nome">
                                {{ $produto->nome }}
                            </strong>

                        </div>

                    </div>

                    <div class="produto-informacoes">

                        {{-- CÓDIGO --}}
                        <div class="produto-barcode-card">

                            <span class="produto-campo-label">
                                <i class="bi bi-upc-scan"></i>
                                Código de barras
                            </span>

                            @if($produto->codigo_barras)

                                <div
                                    class="produto-barcode"
                                    data-barcode="{{ $produto->codigo_barras }}"
                                ></div>

                            @else

                                <strong class="produto-campo-vazio">
                                    Não informado
                                </strong>

                            @endif

                        </div>

                        {{-- PREÇO DE VENDA --}}
                        <section class="produto-origem-card">

                            <div class="produto-origem-titulo">

                                <span>
                                    <i class="bi bi-tag"></i>
                                    PREÇO DE VENDA
                                </span>

                                <small>
                                    {{ $catalogo[
                                        'origem_preco'
                                    ] ?? 'Cadastro Jaime' }}
                                </small>

                            </div>

                            @php
                                $precoVenda =
                                    (float) (
                                        $catalogo[
                                            'preco_venda'
                                        ]
                                        ?? $produto->preco_uni
                                    );
                            @endphp

                            <div class="produto-metricas produto-metricas-uma-coluna">

                                <div class="produto-metrica">

                                    <span>
                                        Valor
                                    </span>

                                    <strong>
                                        R$ {{ number_format(
                                            $precoVenda,
                                            2,
                                            ',',
                                            '.'
                                        ) }}
                                    </strong>

                                </div>

                            </div>

                        </section>

                        {{-- ESTOQUE --}}
                        <section class="produto-origem-card produto-origem-catalogo">

                            <div class="produto-origem-titulo">

                                <span>
                                    <i class="bi bi-boxes"></i>
                                    ESTOQUE
                                </span>

                                <small>
                                    Disponibilidade
                                </small>

                            </div>

                            <div class="produto-metricas">

                                <div class="produto-metrica">

                                    <span>
                                        Jaime
                                    </span>

                                    <strong>
                                        {{ $produto->quantidade }}
                                    </strong>

                                </div>

                                <div class="produto-metrica">

                                    <span>
                                        Fornecedor
                                    </span>

                                    <strong>
                                        @if(
                                            (
                                                $catalogo[
                                                    'estoque_observado'
                                                ] ?? null
                                            ) !== null
                                        )
                                            {{ $catalogo[
                                                'estoque_observado'
                                            ] }}
                                        @else
                                            —
                                        @endif
                                    </strong>

                                </div>

                            </div>

                        </section>

                    </div>

                    <div class="produto-descricao">

                        <span class="produto-campo-label">
                            Descrição
                        </span>

                        <p>
                            {{ $produto->descricao
                                ?: 'Não informada' }}
                        </p>

                    </div>

                </div>

            </div>

            <div class="produto-acoes">

                <a
                    href="{{ route(
                        'produtos.show',
                        $produto->id
                    ) }}"
                    class="btn btn-info"
                >
                    <i class="bi bi-eye"></i>
                    Visualizar
                </a>

                @if(auth()->user()?->isAdmin())

                    <a
                        href="{{ route(
                            'produtos.edit',
                            $produto->id
                        ) }}"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-pencil"></i>
                        Editar
                    </a>

                    <form
                        action="{{ route(
                            'produtos.destroy',
                            $produto->id
                        ) }}"
                        method="POST"
                        onsubmit="return confirm('Deseja excluir este produto?');"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="btn btn-danger"
                        >
                            <i class="bi bi-trash"></i>
                            Excluir
                        </button>

                    </form>

                @endif

            </div>

        </article>

    @endforeach

    {{-- PAGINAÇÃO --}}
    @if($produtos->hasPages())

        <div class="d-flex justify-content-center mt-4">
            {{ $produtos->links() }}
        </div>

    @endif

</div>

@endsection

@section('scripts')

@vite(['resources/js/produto/barcode.js'])

@endsection
