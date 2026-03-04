@extends('layouts.layout')

@section('content')

<div class="container cadastro">
    <h1>Listar O.S itens</h1>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Descrição</th>
                <th scope="col">Excluir</th>
            </tr>
    </thead>
        <tbody>
            @foreach($os_itens as $os_item)
            <tr>
            <td>{{ $os_item->id }}</td>
            <td>{{ $os_item->descricao }}</td>
            <td><a href="/ordemservicos/{{$os_item->id}}" class="btn btn-success"><i class="bi bi-list-task"></i></a></td>
            <td>
                <form action="/ordemservicos/{{$os_item->id}}" method="post">
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
