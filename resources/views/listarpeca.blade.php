@extends('layout')

@section('content')

<div class="container cadastro">
    <h1>LISTAR PEÇAS</h1>
        @foreach($pecas as $peca)
        <div class="peca-container">
            <div class="peca-item">
                <h4>{{ $peca->nome }}</h4>
                <div class="peca-item-img">
                    @if($peca->img == null)
                    img
                    @else
                    <img class="peca-item-img" alt="" src="{{ url("storage/{$peca->img}") }}">
                    @endif
                </div>
                <div class="peca-item-info">
                    <a class="row">ID: {{ $peca->id_peca }}</a>
                    <a class="row">Preço Unitário: {{ $peca->preco_uni }}</a>
                    <a class="row">Montadora: {{ $peca->montadora }}</a>
                    <a class="row">Veículos: {{ $peca->veiculos }}</a>
                    <a class="row">Motor: {{ $peca->motor }}</a>
                    <a class="row">Marcas: {{ $peca->marcas }}</a>
                    <a class="row">Produtos: {{ $peca->produtos }}</a>
                    <a class="row">Vulvula: {{ $peca->vulvula }}</a>
                    <a class="row">Quantidade: {{ $peca->quantidade }}</a>
                    <a class="row">{{ $peca->descricao_peca }}</a>
                    <a href="/pecas/edit/{{$peca->id_peca}}" class="btn btn-info"><i class="bi bi-pencil-square"></i>Editar</a>
                    <form action="/pecas/{{$peca->id_peca}}" method="post">
                        @csrf
                        @method('DELETE')
                        <button href="" class="btn btn-danger delete-btn"><i class="bi bi-trash3"></i>Deletar</button>
                    </form>
                </div>
                
            </div>
        </div>
        @endforeach
</div>

@endsection