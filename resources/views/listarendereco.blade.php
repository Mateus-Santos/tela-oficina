@extends('layout')

@section('content')

<div class="container cadastro">
    <h1>LISTAR ENDEREÇOS</h1>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Pessoa</th>
                <th scope="col">CEP</th>
                <th scope="col">Cidade</th>
                <th scope="col">Bairro</th>
                <th scope="col">Estado</th>
                <th scope="col">Rua</th>
                <th scope="col">Endereco</th>
                <th scope="col">Número</th>
                <th scope="col">Ponto Refêrencia</th>
            </tr>
    </thead>
        <tbody>
            @foreach($enderecos as $endereco)
            <tr>
            <th scope="row">1</th>
            <td>{{ $endereco->id_pessoa }}</td>
            <td>{{ $endereco->cep }}</td>
            <td>{{ $endereco->cidade }}</td>
            <td>{{ $endereco->bairro }}</td>
            <td>{{ $endereco->estado }}</td>
            <td>{{ $endereco->rua }}</td>
            <td>{{ $endereco->endereco }}</td>
            <td>{{ $endereco->numero }}</td>
            <td>{{ $endereco->ponto_referencia }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection