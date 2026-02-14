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
                <th scope="col">Listar Serviços</th>
                <th scope="col">Excluir</th>
            </tr>
    </thead>
        <tbody>
            @foreach($ordem_servicos as $ordem_servico)
            <tr>
            <td>{{ $ordem_servico->id }}</td>
            <td>{{ $ordem_servico->status }}</td>
            <td>{{ $ordem_servico->data_abertura }}</td>
            <td>{{ $ordem_servico->veiculoCliente->cliente->user->name }}</td>
            <td>{{ $ordem_servico->veiculoCliente->placa}}</td>
            <td>{{ $ordem_servico->descricao }}</td>
            <td><a href="/ordemservicos/{{$ordem_servico->id}}" class="btn btn-success"><i class="bi bi-list-task"></i></a></td>
            <td>
                <form action="/ordemservicos/{{$ordem_servico->id}}" method="post">
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
