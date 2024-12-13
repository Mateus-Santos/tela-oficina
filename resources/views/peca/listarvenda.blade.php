@extends('layouts.layout')

@section('content')

<div class="container cadastro">
    <h1>LISTAR VENDAS</h1>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">id</th>
                <th scope="col">Valor Total</th>
                <th scope="col">Quantidade</th>
                <th scope="col">Valor unitário</th>
                <th scope="col">Desconto</th>
                <th scope="col">Juros</th>
                <th scope="col">Valor Pagto</th>
                <th scope="col">Data Venda</th>
                <th scope="col">Data Vencimento</th>
                <th scope="col">Data Pagamento</th>
                <th scope="col">Editar</th>
                <th scope="col">Excluir</th>
            </tr>
    </thead>
        <tbody>
            @foreach($vendas as $venda)
            <tr>
            <th scope="row">{{$venda->id}}</th>
            <td>{{ $venda->valor_total }}</td>
            <td>{{ $venda->quantidade }}</td>
            <td>{{ $venda->valor_uni }}</td>
            <td>{{ $venda->desconto }}</td>
            <td>{{ $venda->juros }}</td>
            <td>{{ $venda->valor_pagto }}</td>
            <td>{{ $venda->data_venda }}</td>
            <td>{{ $venda->data_venc }}</td>
            <td>{{ $venda->data_pagto }}</td>
            <td><a href="/vendas/edit/{{$venda->id}}" class="btn btn-info"><i class="bi bi-pencil-square"></i>Editar</a></td>
            <td>
                <form action="/vendas/{{$venda->id}}" method="post">
                    @csrf
                    @method('DELETE')
                    <button href="" class="btn btn-danger delete-btn"><i class="bi bi-trash3"></i>Deletar</button>
                </form>
            </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection