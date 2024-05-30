@extends('layout')

@section('content')

<div class="container cadastro">
    <h1>LISTAR PEÇAS</h1>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Montadora</th>
                <th scope="col">Nome</th>
                <th scope="col">Veículos</th>
                <th scope="col">Motor</th>
                <th scope="col">Descrição Peça</th>
                <th scope="col">Marcas</th>
                <th scope="col">Departamentos</th>
                <th scope="col">Produtos</th>
                <th scope="col">Vulvula</th>
                <th scope="col">Quantidade</th>
                <th scope="col">Ano</th>
            </tr>
    </thead>
        <tbody>
            @foreach($pecas as $peca)
            <tr>
            <td>{{ $peca->id_peca }}</td>
            <td>{{ $peca->montadora }}</td>
            <td>{{ $peca->nome }}</td>
            <td>{{ $peca->veiculos }}</td>
            <td>{{ $peca->motor }}</td>
            <td>{{ $peca->descricao_peca }}</td>
            <td>{{ $peca->marcas }}</td>
            <td>{{ $peca->departamentos }}</td>
            <td>{{ $peca->produtos }}</td>
            <td>{{ $peca->vulvula }}</td>
            <td>{{ $peca->quantidade }}</td>
            <td>{{ $peca->ano }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection