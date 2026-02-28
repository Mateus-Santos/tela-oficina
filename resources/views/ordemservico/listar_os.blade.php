@extends('layouts.layout')

@section('content')

<div class="container cadastro">
    <h1>Listar Ordem Serviço</h1>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Status</th>
                <th scope="col">Data Abertura</th>
                <th scope="col">Cliente</th>
                <th scope="col">Veículo</th>
                <th scope="col">Descrição</th>
                <th scope="col">Setor</th>
                <th scope="col">Excluir</th>
            </tr>
    </thead>
        <tbody>
            @foreach($ordemservicos as $ordemservico)
            <tr>
            <td>{{ $ordemservico->id }}</td>
            <td>{{ $ordemservico->status }}</td>
            <td>{{ $ordemservico->data_abertura }}</td>
            <td>{{ $ordemservico->veiculoscliente->cliente->user->name }}</td>
            <td>{{ $ordemservico->veiculoscliente->placa }}</td>
            <td>{{ $ordemservico->descricao }}</td>
            <td>{{ $ordemservico->setorservico->setor }}</td>
            <td>
                <form action="/ordemservicos/{{$ordemservico->id}}" method="post">
                    @csrf
                    @method('DELETE')
                    <button href="" class="btn btn-danger delete-btn"><i class="bi bi-trash3"></i></button>
                </form>
            </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
