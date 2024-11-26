@extends('layouts.layout')

@section('content')

<div class="container cadastro">
    <h1>LISTAR CLIENTE</h1>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Usuario</th>
                <th scope="col">Pontos</th>
                <th scope="col">Editar</th>
                <th scope="col">Excluir</th>
            </tr>
    </thead>
        <tbody>
            @foreach($clientes as $cliente)
            <tr>
                <td>{{ $cliente->id_cliente }}</td>
                <td>{{ $cliente->user->email }}</td>
                <td>{{ $cliente->pontos }}</td>
                <td><a href="/users/{{ $cliente->user->id }}/edit" class="btn btn-info"><i class="bi bi-pencil-square"></i>Editar</a></td>
                <td>
                    <form action="" method="post">
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