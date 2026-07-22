@extends('layouts.layout')

@section('content')

<div class="container cadastro">
    <h1>LISTAR NOTAS</h1>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">STATUS</th>
                <th scope="col">Cliente</th>
                <th scope="col">Placa Veículo</th>
                <th scope="col">Detalhes</th>
                <th scope="col">Excluir</th>
            </tr>
    </thead>
        <tbody>
            @foreach($notas as $nota)
            <tr>
            <td>{{ $nota->id }}</td>
            <td>
                @livewire('status-nota-selector', ['nota' => $nota], key($nota->id))
            </td>
            <td>{{ $nota->cliente?->pessoa?->nome ?? 'Cliente Geral / Balcão' }}</td>
            <td>{{ $nota->veiculoscliente->placa }}</td>
            <td><a href="/notasitens/{{$nota->id}}" class="btn btn-success"><i class="bi bi-list-task"></i></a></td>
            <td>
                <form action="/notasitens/{{$nota->id}}" method="post">
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