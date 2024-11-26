@extends('layouts.layout')

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
                <th scope="col">Número</th>
                <th scope="col">Ponto Refêrencia</th>
                <th scope="col">Editar</th>
                <th scope="col">Excluir</th>
            </tr>
    </thead>
        <tbody>
            @foreach($enderecos as $endereco)
            <tr>
            <th scope="row">{{ $endereco->id_endereco }}</th>
            <td>{{ $endereco->pessoa->nome }}</td>
            <td>{{ $endereco->cep }}</td>
            <td>{{ $endereco->cidade }}</td>
            <td>{{ $endereco->bairro }}</td>
            <td>{{ $endereco->estado }}</td>
            <td>{{ $endereco->rua }}</td>
            <td>{{ $endereco->numero }}</td>
            <td>{{ $endereco->ponto_referencia }}</td>
            <td><a href="/enderecos/edit/{{$endereco->id_endereco}}" class="btn btn-info"><i class="bi bi-pencil-square"></i>Editar</a></td>
            <td>
                <form action="/enderecos/{{$endereco->id_endereco}}" method="post">
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