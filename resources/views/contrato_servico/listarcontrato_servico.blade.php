@extends('layouts.layout')

@section('content')

<div class="container cadastro">
    <h1>LISTAR CONTRATOS</h1>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Status</th>
                <th scope="col">Data Abertura</th>
                <th scope="col">Cliente</th>
                <th scope="col">Veículo</th>
                <th scope="col">E-mail</th>
                <th scope="col">Descrição</th>
                <th scope="col">Listar Manutencoes</th>
                <th scope="col">Editar</th>
                <th scope="col">Excluir</th>
            </tr>
    </thead>
        <tbody>
            @foreach($contratos as $contrato_servico)
            <tr>
            <td>{{ $contrato_servico->id }}</td>
            <td>{{ $contrato_servico->status }}</td>
            <td>{{ $contrato_servico->data_abertura }}</td>
            <td>{{ $contrato_servico->veiculo->user->name }}</td>
            <td>{{ $contrato_servico->veiculo->placa }}</td>
            <td>{{ $contrato_servico->veiculo->user->email }}</td>
            <td>{{ $contrato_servico->descricao }}</td>
            <td><a href="/contratoservico/{{$contrato_servico->id}}/manutencoes" class="btn btn-success"><i class="bi bi-list-task"></i></a></td>
            <td><a href="/contratoservico/edit/{{$contrato_servico->id}}" class="btn btn-info"><i class="bi bi-pencil-square"></i></a></td>
            <td>
                <form action="/contratoservico/{{$contrato_servico->id}}" method="post">
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