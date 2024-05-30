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
                <th scope="col">Usuario Pessoa</th>
                <th scope="col">Nome Pessoa</th>
            </tr>
    </thead>
        <tbody>
            @foreach($colaboradors as $colaborador)
            <tr>
            <th scope="row">1</th>
            <td>{{ $colaborador->id_colaborador }}</td>
            <td>{{ $colaborador->chave_pix }}</td>
            <td>{{ $colaborador->id_user }}</td>
            <td>{{ $colaborador->id_pessoa }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection