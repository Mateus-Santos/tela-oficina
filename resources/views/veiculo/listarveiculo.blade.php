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
                <th scope="col">Marca</th>
                <th scope="col">Cor</th>
                <th scope="col">Usuário</th>
                <th scope="col">Excluir</th>
            </tr>
    </thead>
        <tbody>
            @foreach($veiculos as $veiculo)
            <tr>
                <th scope="row">{{$veiculo->id_veiculo}}</th>
                <td>{{ $veiculo->placa }}</td>
                <td>{{ $veiculo->ano }}</td>
                <td>{{ $veiculo->marca }}</td>
                <td>{{ $veiculo->cor }}</td>
                <td>{{ $veiculo->user->name }}</td>
                <td>
                    <form action="/veiculos/{{$veiculo->id_veiculo}}" method="post">
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
