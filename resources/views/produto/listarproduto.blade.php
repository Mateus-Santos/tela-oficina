@extends('layouts.layout')

@section('content')

<div class="container cadastro">
    <h1>LISTAR PRODUTOS</h1>
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