@extends('layouts.layout')

@section('content')

<div class="container cadastro">
    <h1>LISTAR PEÇAS</h1>
        @foreach($pecas as $peca)
        <div class="produto-container">
            <h4>Código da peça: {{$peca->codigo_fabricante}}</h4>
            <div class="produto-item">
                <img class="produto-item-img" src="storage/{{$peca->img}}">
                <div class="produto-atributos">
                    <a>ID Peça:{{$peca->id_peca}}</a>
                    <a>Nome: {{$peca->nome}}</a>
                    <a>Estoque: {{$peca->quantidade}}</a>
                    <a>Valor: R$ {{$peca->preco_uni}}</a>
                </div>
                <div class="produto-description">
                    <a>{{$peca->descricao_peca}}</a>
                </div>
            </div>
        </div>
        @endforeach
</div>

@endsection