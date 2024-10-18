@extends('layouts.layout')

@section('content')

<div class="container cadastro">
    <h1>LISTAR PESSOAS</h1>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">id</th>
                <th scope="col">Nome Completo</th>
                <th scope="col">Data de Nascimento</th>
                <th scope="col">E-mail</th>
                <th scope="col">CPF</th>
                <th scope="col">Telefone Principal</th>
                <th scope="col">Detalhes</th>
                <th scope="col">Editar</th>
                <th scope="col">Excluir</th>
            </tr>
    </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <th scope="row">{{$user->id}}</th>
                <td>{{ $user->name }}</td>
                <td>{{ $user->data_nascimento }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->cpf }}</td>
                <td>{{ $user->telefone_1 }}</td>
                <td><a href="/users/{{$user->id}}" class="btn btn-success"><i class="bi bi-eye"></i>Detalhes</a></td>
                <td><a href="/users/edit/{{$user->id}}" class="btn btn-info"><i class="bi bi-pencil-square"></i>Editar</a></td>
                <td>
                    <form action="/users/{{$user->id}}" method="post">
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