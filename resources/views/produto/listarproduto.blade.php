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
        :expanded="request()->hasAny(['marca', 'status', 'estoque'])"
    >
        <x-slot:primary>

            <div class="row g-3 align-items-end">

                {{-- Código do fabricante --}}
                <div class="col-12 col-md-4">
                    <label for="codigo_fabricante" class="form-label">
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
                <div class="col-12 col-md-4">
                    <label for="codigo_barras" class="form-label">
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
                <div class="col-12 col-md-4">
                    <label for="nome" class="form-label">
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

            </div>

            <div class="row mt-3">

                <div class="col-12">

                    <div class="filtros-container__actions">

                        <button type="submit" class="btn btn-primary">
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
                    <label for="marca" class="form-label">
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
                    <label for="status" class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        id="status"
                        class="filtros-container__input"
                    >
                        <option value="">Todos</option>

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
                    <label for="estoque" class="form-label">
                        Situação do estoque
                    </label>

                    <select
                        name="estoque"
                        id="estoque"
                        class="filtros-container__input"
                    >
                        <option value="">Todos</option>

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

    {{-- Resultado da busca --}}
    @if($produtos->isEmpty())

        <div class="alert alert-{{ request()->hasAny(['nome', 'codigo_barras', 'codigo_fabricante', 'marca', 'status', 'estoque']) ? 'warning' : 'info' }}">

            <i class="bi {{ request()->hasAny(['nome', 'codigo_barras', 'codigo_fabricante', 'marca', 'status', 'estoque']) ? 'bi-exclamation-triangle' : 'bi-info-circle' }}"></i>

            {{ request()->hasAny(['nome', 'codigo_barras', 'codigo_fabricante', 'marca', 'status', 'estoque'])
                ? 'Nenhum produto encontrado com os filtros informados.'
                : 'Nenhum produto cadastrado.' }}

        </div>

    @endif

    {{-- Listagem --}}
    @foreach($produtos as $produto)

        <div class="produto-container">

            <h4>
                Cod. Fabricante: {{ $produto->codigo_fabricante }}
            </h4>

            <div class="produto-item">

                {{-- Imagem --}}
                @if($produto->img)

                    <img
                        class="produto-item-img"
                        src="{{ asset('storage/' . $produto->img) }}"
                        alt="{{ $produto->nome }}"
                    >

                @else

                    <div class="produto-item-img d-flex align-items-center justify-content-center bg-light">

                        <div class="text-center text-muted">

                            <i class="bi bi-image fs-1"></i>

                            <div>
                                Sem imagem
                            </div>

                        </div>

                    </div>

                @endif

                {{-- Atributos --}}
                <div class="produto-atributos">

                    {{-- Identificação do produto --}}
                    <div class="produto-identificacao">

                        {{-- Espaço reservado para futura logo da marca --}}
                        <div class="produto-marca-logo">
                            <i class="bi bi-image"></i>
                        </div>

                        <div class="produto-identificacao-info">

                            <span class="produto-marca">
                                {{ $produto->marcaRelacionada?->nome ?: 'Marca não informada' }}
                            </span>

                            <span class="produto-nome">
                                {{ $produto->nome }}
                            </span>

                        </div>

                    </div>

                    {{-- Resumo do produto --}}
                    <div class="produto-resumo">

                        {{-- Código de barras --}}
                        @if($produto->codigo_barras)

                            <div class="produto-barcode-card">

                                <div class="produto-barcode-header">
                                    <i class="bi bi-upc-scan"></i>
                                    <span>Código de barras</span>
                                </div>

                                <div
                                    class="produto-barcode"
                                    data-barcode="{{ $produto->codigo_barras }}"
                                ></div>

                            </div>

                        @else

                            <div class="produto-barcode-card produto-barcode-sem-codigo">

                                <div class="produto-barcode-header">
                                    <i class="bi bi-upc-scan"></i>
                                    <span>Código de barras</span>
                                </div>

                                <span>
                                    Não informado
                                </span>

                            </div>

                        @endif

                        {{-- Estoque e valor --}}
                        <div class="produto-resumo-dados">

                            <div class="produto-resumo-dado">

                                <span class="produto-resumo-label">
                                    <i class="bi bi-box-seam"></i>
                                    Estoque
                                </span>

                                <strong>
                                    {{ $produto->quantidade }}
                                </strong>

                            </div>

                            <div class="produto-resumo-dado">

                                <span class="produto-resumo-label">
                                    <i class="bi bi-currency-dollar"></i>
                                    Valor
                                </span>

                                <strong>
                                    R$ {{ number_format($produto->preco_uni, 2, ',', '.') }}
                                </strong>

                            </div>

                        </div>

                    </div>

                </div>

                {{-- Descrição --}}
                <div class="produto-description">

                    <a>
                        <span>Descrição</span>
                    </a>

                    <a>
                        {{ $produto->descricao }}
                    </a>

                </div>

            </div>

            {{-- Ações --}}
            <div class="produto-acoes">

                <a
                    href="{{ route('produtos.show', $produto->id) }}"
                    class="btn btn-info"
                    title="Visualizar produto"
                >
                    <i class="bi bi-eye"></i>
                    Visualizar
                </a>

                @if(auth()->user() && auth()->user()->permitions === 1)

                    <a
                        href="{{ route('produtos.edit', $produto->id) }}"
                        class="btn btn-primary btn-edit"
                        title="Editar produto"
                    >
                        <i class="bi bi-pencil"></i>
                        Editar
                    </a>

                    <form
                        action="{{ route('produtos.destroy', $produto->id) }}"
                        method="POST"
                        onsubmit="return confirm('Deseja excluir este produto?');"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="btn btn-danger"
                            title="Excluir produto"
                        >
                            <i class="bi bi-trash"></i>
                            Excluir
                        </button>

                    </form>

                @endif

            </div>

        </div>

    @endforeach

    {{-- Paginação --}}
    @if($produtos->hasPages())

        <div class="d-flex justify-content-center mt-4">
            {{ $produtos->links() }}
        </div>

    @endif

</div>

@endsection

@section('scripts')

@vite(['resources/js/components/barcode.js'])

@endsection
