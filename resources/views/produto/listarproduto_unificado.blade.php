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

    <p class="text-body-secondary mb-3">
        {{ number_format($produtos->total(), 0, ',', '.') }} produtos encontrados.
        Itens sem cadastro de estoque sao referencias disponiveis para consulta, nao saldo fisico.
    </p>

    @forelse($produtos as $item)
        <article class="produto-container mb-3">
            <div class="produto-cabecalho">
                <div class="produto-cabecalho-identificacao">
                    <span class="produto-referencia">
                        @if($item->codigo) Código {{ $item->codigo }} · @endif
                        Ref. {{ $item->referencia ?: 'Não informada' }}
                    </span>
                    @if($item->operacional)
                        <span class="badge {{ $item->status ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $item->status ? 'Ativo' : 'Inativo' }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="produto-item">
                <div class="produto-media">
                    @if($item->imagem)
                        <img class="produto-item-img" src="{{ $item->imagem }}" alt="{{ $item->nome }}" loading="lazy" referrerpolicy="no-referrer">
                    @else
                        <div class="produto-item-img produto-sem-imagem"><i class="bi bi-image"></i><span>Sem imagem</span></div>
                    @endif
                </div>
                <div class="produto-conteudo">
                    <div class="produto-identificacao">
                        <div class="produto-identificacao-info">
                            <span class="produto-marca">{{ $item->marca ?: 'Marca não informada' }}</span>
                            <strong class="produto-nome">{{ $item->nome }}</strong>
                        </div>
                    </div>
                    <div class="produto-informacoes">
                        <div class="produto-barcode-card">
                            <span class="produto-campo-label">Código de barras</span>
                            @if($item->codigo_barras)
                                <div class="produto-barcode" data-barcode="{{ $item->codigo_barras }}"></div>
                            @else
                                <strong>Não informado</strong>
                            @endif
                        </div>
                        <section class="produto-origem-card">
                            <div class="produto-origem-titulo">
                                <span><i class="bi bi-tag"></i> PREÇO DE VENDA</span>
                            </div>
                            <div class="produto-metricas produto-metricas-uma-coluna">
                                <div class="produto-metrica" @if($item->sugerido !== null) title="Preço de referência: R$ {{ number_format((float) $item->sugerido, 2, ',', '.') }}" @endif>
                                    <strong>{{ $item->preco !== null ? 'R$ '.number_format((float) $item->preco, 2, ',', '.') : 'A consultar' }}</strong>
                                </div>
                            </div>
                        </section>
                        <section class="produto-origem-card">
                            <div class="produto-origem-titulo"><span><i class="bi bi-boxes"></i> DISPONIBILIDADE</span></div>
                            <strong>{{ $item->operacional ? $item->quantidade : 'Sob consulta' }}</strong>
                        </section>
                    </div>
                    @if($item->descricao)
                        <div class="produto-descricao"><span class="produto-campo-label">Descrição</span>
                            <p>{{ \Illuminate\Support\Str::limit($item->descricao, 180) }}</p>
                        </div>
                    @endif
                </div>
            </div>
            <div class="produto-acoes">
                <a href="{{ $item->url }}" class="btn btn-info"><i class="bi bi-eye"></i> Visualizar</a>
                @if($item->catalogo_id)
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal"
                        data-bs-target="#modalCatalogoSimilares"
                        data-url-similares="{{ route('produtos.catalogo.similares', $item->catalogo_id) }}">
                        <i class="bi bi-intersect"></i> Similares
                    </button>
                @endif
                @if(!$item->operacional && auth()->user()?->isAdmin())
                    <a class="btn btn-primary" href="{{ route('produtos.catalogo.edit', $item->catalogo_id) }}">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                @endif
                @if($item->operacional && auth()->user()?->isAdmin())
                    <a href="{{ route('produtos.edit', $item->id) }}" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                    <form action="{{ route('produtos.destroy', $item->id) }}" method="POST"
                          onsubmit="return confirm('Deseja excluir este produto?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Excluir
                        </button>
                    </form>
                @endif
            </div>
        </article>
    @empty
        <div class="alert alert-info">Nenhum produto encontrado com os filtros informados.</div>
    @endforelse

    @if($produtos->hasPages())
        <div class="d-flex justify-content-center mt-4">{{ $produtos->links() }}</div>
    @endif
</div>
@include('produto.partials.similares-modal')
@endsection

@section('scripts')
@vite(['resources/js/produto/barcode.js'])
@endsection
