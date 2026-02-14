@extends('layouts.layout')

@section('content')

<div class="container cadastro">
    <h1>Ordem de Serviço</h1>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Status</th>
                <th scope="col">Data Abertura</th>
                <th scope="col">Cliente</th>
                <th scope="col">Veículo</th>
                <th scope="col">Descrição</th>
            </tr>
    </thead>
        <tbody>
            <tr>
            <td>{{ $ordem_servicos->id }}</td>
            <td>{{ $ordem_servicos->status }}</td>
            <td>{{ $ordem_servicos->data_abertura }}</td>
            <td>{{ $ordem_servicos->veiculocliente->cliente->user->name }}</td>
            <td>{{ $ordem_servicos->veiculocliente->placa }}</td>
            <td>{{ $ordem_servicos->descricao }}</td>
            </tr>
        </tbody>
    </table>

    @if($servicos->isEmpty())
    <h1>Contrato não possui serviços cadastrados</h1>
        @if(auth()->user()->permitions != 2)
            <a class="btn btn-success" href="/servicos/create/">Cadastrar Nova Serviço</a>
        @endif
    @else
    <h1> SERVIÇOS CADASTRADOS</h1>
    <div class="row mb-3">
        @if(auth()->user()->permitions != 2)
            <a class="btn btn-success" href="/servicos/create/">Cadastrar Nova Serviço</a>
        @endif
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Setor</th>
                    <th scope="col">Descrição</th>
                    <th scope="col">Valor</th>
                    <th scope="col">Nível</th>
                    @if(auth()->user()->permitions != 2)

                        <th scope="col">Excluir</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($servicos as $servico)
                <tr>
                <th scope="row">{{ $servico->id }}</th>
                <td>{{ $servico->setor }}</td>
                <td>{{ $servico->descricao }}</td>
                    <td>R$ {{ $servico->valor }}</td>
                <td>{{ $servico->nivel }}</td>
                    @if(auth()->user()->permitions != 2)
                <td>
                    <form action="/servicos/{{$servico->id}}" method="post">
                        @csrf
                        @method('DELETE')
                        <button href="" class="btn btn-danger delete-btn"><i class="bi bi-trash3"></i></button>
                    </form>
                </td>
                    @endif
                </tr>
                @endforeach
                <td>
                    <a>Valor total fica por: </a>
                    <input type="text" value="R$ {{ $valor_servico }}" readonly disabled>
                </td>
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
