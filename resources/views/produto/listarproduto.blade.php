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
                    <label for="codigo_fabricante" class="form-label">Código do fabricante</label>
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
                    <label for="codigo_barras" class="form-label">Código de barras</label>
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
                    <label for="nome" class="form-label">Nome do produto</label>
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
                    <label for="marca" class="form-label">Marca</label>
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
                    <label for="status" class="form-label">Status</label>
                    <select
                        name="status"
                        id="status"
                        class="filtros-container__input"
                    >
                        <option value="">Todos</option>
                        <option value="1" @selected(request('status') === '1')>Ativos</option>
                        <option value="0" @selected(request('status') === '0')>Inativos</option>
                    </select>
                </div>

                {{-- Estoque --}}
                <div class="col-12 col-md-4">
                    <label for="estoque" class="form-label">Situação do estoque</label>
                    <select
                        name="estoque"
                        id="estoque"
                        class="filtros-container__input"
                    >
                        <option value="">Todos</option>
                        <option value="com_estoque" @selected(request('estoque') === 'com_estoque')>
                            Com estoque
                        </option>
                        <option value="sem_estoque" @selected(request('estoque') === 'sem_estoque')>
                            Sem estoque
                        </option>
                        <option value="estoque_baixo" @selected(request('estoque') === 'estoque_baixo')>
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

                <img
                    class="produto-item-img"
                    src="{{ asset('storage/' . $produto->img) }}"
                    alt="{{ $produto->nome }}"
                >

                <div class="produto-atributos">
                    <a>ID produto: {{ $produto->id }}</a>
                    <a>Nome: {{ $produto->nome }}</a>
                    <a>Marca: {{ $produto->marca }}</a>
                    <a>
                        Código de barras:
                        {{ $produto->codigo_barras ?: 'Não informado' }}
                    </a>
                    <a>Estoque: {{ $produto->quantidade }}</a>
                    <a>
                        Valor: R$ {{ number_format($produto->preco_uni, 2, ',', '.') }}
                    </a>
                </div>

                <div class="produto-description">
                    <a><span>Descrição</span></a>
                    <a>{{ $produto->descricao }}</a>
                </div>

            </div>

            {{-- Ações --}}
            @if(auth()->user() && auth()->user()->permitions === 1)
                <div class="produto-acoes">

                    <a
                        href="{{ route('produtos.edit', $produto->id) }}"
                        class="btn btn-primary btn-edit"
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

                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i>
                            Excluir
                        </button>
                    </form>

                </div>
            @endif

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
