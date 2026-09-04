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
        :expanded="request()->filled('codigo_fabricante')"
    >
        <x-slot:primary>
            <div class="row g-3 align-items-end">

                <div class="col-12 col-md-5">
                    <label for="nome" class="form-label">Nome</label>
                    <input
                        type="text"
                        name="nome"
                        id="nome"
                        class="filtros-container__input"
                        placeholder="Nome do produto"
                        value="{{ request('nome') }}"
                    >
                </div>

                <div class="col-12 col-md-5">
                    <label for="codigo_barras" class="form-label">Código de barras</label>
                    <input
                        type="text"
                        name="codigo_barras"
                        id="codigo_barras"
                        class="filtros-container__input"
                        placeholder="Código de barras"
                        value="{{ request('codigo_barras') }}"
                    >
                </div>

                <div class="col-12 col-md-2">
                    <div class="filtros-container__actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrar
                        </button>

                        <a
                            href="{{ route('produtos.index') }}"
                            class="btn btn-secondary"
                            title="Limpar filtros"
                        >
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </div>

            </div>
        </x-slot:primary>

        <x-slot:advanced>
            <div class="row g-3">

                <div class="col-12 col-md-6">
                    <label for="codigo_fabricante" class="form-label">
                        Código do fabricante
                    </label>

                    <input
                        type="text"
                        name="codigo_fabricante"
                        id="codigo_fabricante"
                        class="filtros-container__input"
                        list="lista-codigos-fabricante"
                        placeholder="Digite o código do fabricante"
                        value="{{ request('codigo_fabricante') }}"
                    >

                    <datalist id="lista-codigos-fabricante">
                        @foreach($produtos as $produto)
                            @if($produto->codigo_fabricante)
                                <option value="{{ $produto->codigo_fabricante }}">
                            @endif
                        @endforeach
                    </datalist>
                </div>

            </div>
        </x-slot:advanced>
    </x-filtros-container>

    @if($produtos->isEmpty())
        <div class="alert alert-{{ request()->hasAny(['nome', 'codigo_barras', 'codigo_fabricante']) ? 'warning' : 'info' }}">
            <i class="bi {{ request()->hasAny(['nome', 'codigo_barras', 'codigo_fabricante']) ? 'bi-exclamation-triangle' : 'bi-info-circle' }}"></i>

            {{ request()->hasAny(['nome', 'codigo_barras', 'codigo_fabricante'])
                ? 'Nenhum produto encontrado com os filtros informados.'
                : 'Nenhum produto cadastrado.' }}
        </div>
    @endif

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
                    <a>Estoque: {{ $produto->quantidade }}</a>
                    <a>Valor: R$ {{ $produto->preco_uni }}</a>
                </div>

                <div class="produto-description">
                    <a><span>Descrição</span></a>
                    <a>{{ $produto->descricao }}</a>
                </div>

            </div>

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

    @if(method_exists($produtos, 'hasPages') && $produtos->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $produtos->links() }}
        </div>
    @endif

</div>

@endsection
