@extends('layouts.layout')

@section('content')

<div class="container cadastro">
    <h1>LISTAR COLABORADORES</h1>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Nome</th>
                <th scope="col">Chave Pix</th>
                <th scope="col">Conta Banco</th>
                <th scope="col">E-mail Conta</th>
                <th scope="col">Editar</th>
                <th scope="col">Excluir</th>
            </tr>
    </thead>
        <tbody>
            @foreach($colaboradors as $colaborador)
            <tr>
            <td>{{ $colaborador->id_colaborador }}</td>
            <td>{{ $colaborador->pessoa->nome }}</td>
            <td>{{ $colaborador->chave_pix }}</td>
            <td>{{ $colaborador->conta_banco }}</td>
            <td>{{ $colaborador->user->email }}</td>
            <td><a href="/colaboradors/edit/{{$colaborador->id_colaborador}}" class="btn btn-info"><i class="bi bi-pencil-square"></i>Editar</a></td>
            <td>
                <form action="/colaboradors/{{$colaborador->id_colaborador}}" method="post">
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