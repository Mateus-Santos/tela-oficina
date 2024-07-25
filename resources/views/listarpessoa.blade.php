@extends('layout')

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
            @foreach($pessoas as $pessoa)
            <tr>
                <th scope="row">{{$pessoa->id_pessoa}}</th>
                <td>{{ $pessoa->nome }}</td>
                <td>{{ $pessoa->data_nascimento }}</td>
                <td>{{ $pessoa->email }}</td>
                <td>{{ $pessoa->cpf }}</td>
                <td>{{ $pessoa->telefone_1 }}</td>
                <td><a href="/pessoas/{{$pessoa->id_pessoa}}" class="btn btn-success"><i class="bi bi-eye"></i>Detalhes</a></td>
                <td><a href="/pessoas/edit/{{$pessoa->id_pessoa}}" class="btn btn-info"><i class="bi bi-pencil-square"></i>Editar</a></td>
                <td>
                    <form action="/pessoas/{{$pessoa->id_pessoa}}" method="post">
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