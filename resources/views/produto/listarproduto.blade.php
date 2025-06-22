@extends('layouts.layout')

@section('content')

<div class="container cadastro">
    <h1>LISTA DE PRODUTOS</h1>
    <div class="filtros-container">
        <form method="GET" action="{{ route('produtos.index') }}" class="filtros-container__form">
                <input
                    class="filtros-container__input"
                    type="text"
                    name="nome"
                    placeholder="Nome do produto"
                    value="{{ request('nome') }}"
                >

                <input 
                    class="filtros-container__input"
                    type="text"
                    name="codigo_barras"
                    placeholder="Código de barras"
                    value="{{ request('codigo_barras') }}"
                >

                <select class="filtros-container__select" name="ano_modelo">
                    <option value="">Ano</option>
                    @foreach($produtos as $produto)
                        <option value="{{$produto->ano_modelo}}">{{$produto->ano_modelo}}</option>
                    @endforeach
                </select>

                <input 
                    class="filtros-container__input" 
                    list="codigo_fabricante" 
                    name="codigo_fabricante" 
                    placeholder="Digite o código do fabricante..."
                    >

                    <datalist id="codigo_fabricante">
                    @foreach($produtos as $produto)
                        <option value="{{ $produto->codigo_fabricante }}"></option>
                    @endforeach
                    </datalist>

                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <a href="{{ route('produtos.index') }}" class="btn btn-secondary">
                    <i class="bi bi-filter"></i> Limpar Filtros
                </a>
        </form>
    </div>
    @foreach($produtos as $produto)
    <div class="produto-container">
        <h4>Cod. Fabricante: {{$produto->codigo_fabricante}}</h4>
        <div class="produto-item">
            <img class="produto-item-img" src="storage/{{$produto->img}}">
            <div class="produto-atributos">
                <a>ID produto:{{$produto->id}}</a>
                <a>Nome: {{$produto->nome}}</a>
                <a>Estoque: {{$produto->quantidade}}</a>
                <a>Valor: R$ {{$produto->preco_uni}}</a>
            </div>
            <div class="produto-description">
                <a><span>Descrição</span></a>
                <a>{{$produto->descricao}}</a>
            </div>
        </div>
    </div>
    @endforeach
</div>

@endsection