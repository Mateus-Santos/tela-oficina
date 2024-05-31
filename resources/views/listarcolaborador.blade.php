@extends('layout')

@section('content')

<div class="container cadastro">
    <h1>LISTAR COLABORADORES</h1>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Chave Pix</th>
                <th scope="col">Conta Banco</th>
                <th scope="col">E-mail Conta</th>
                <th scope="col">Nome</th>
            </tr>
    </thead>
        <tbody>
            @foreach($colaboradors as $colaborador)
            <tr>
            <th scope="row">1</th>
            <td>{{ $colaborador->id_colaborador }}</td>
            <td>{{ $colaborador->chave_pix }}</td>
            <td>{{ $colaborador->users->email }}</td>
            <td>{{ $colaborador->pessoa->nome }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection