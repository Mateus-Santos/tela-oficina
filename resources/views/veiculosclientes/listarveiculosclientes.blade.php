@extends('layouts.layout')

@section('content')

<div class="container cadastro">
    <h1>LISTAR VEÍCULOS</h1>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Placa</th>
                <th scope="col">Ano</th>
                <th scope="col">Montadora</th>
                <th scope="col">Cor</th>
                <th scope="col">Usuário</th>
                <th scope="col">Editar</th>
                <th scope="col">Excluir</th>
            </tr>
    </thead>
        <tbody>
            @foreach($veiculosclientes as $veiculoscliente)
            <tr>
                <th scope="row">{{$veiculoscliente->id}}</th>
                <td>{{ $veiculoscliente->placa }}</td>
                <td>{{ $veiculoscliente->ano }}</td>
                <td>{{ $veiculoscliente->veiculo->montadora->nome}}</td>
                <td>{{ $veiculoscliente->cor }}</td>
                <td>{{ $veiculoscliente->cliente->pessoa->nome }}</td>
                <td><a href="/veiculosclientes/{{$veiculoscliente->id}}/edit/" class="btn btn-info"><i class="bi bi-pencil-square"></i></a></td>
                <td>
                    <form action="/veiculosclientes/{{$veiculoscliente->id}}" method="post">
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
