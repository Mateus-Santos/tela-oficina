@extends('layouts.layout')

@section('content')

<div class="container cadastro">
    <h1>DETALHES CONTRATO</h1>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Status</th>
                <th scope="col">Data Abertura</th>
                <th scope="col">Cliente</th>
                <th scope="col">Veículo</th>
                <th scope="col">E-mail</th>
                <th scope="col">Descrição</th>
            </tr>
    </thead>
        <tbody>
            <tr>
            <td>{{ $contrato_servico->id }}</td>
            <td>{{ $contrato_servico->status }}</td>
            <td>{{ $contrato_servico->data_abertura }}</td>
            <td>{{ $contrato_servico->veiculo->user->name }}</td>
            <td>{{ $contrato_servico->veiculo->placa }}</td>
            <td>{{ $contrato_servico->veiculo->user->email }}</td>
            <td>{{ $contrato_servico->descricao }}</td>
            </tr>
        </tbody>
    </table>

    @if($manutencoes->isEmpty())
    <h1>Contrato não possui manutenções cadastrados</h1>
        @if(auth()->user()->permitions != 2)
            <a class="btn btn-success" href="/manutencoes/create/">Cadastrar Nova Manutenção</a>
        @endif
    @else
    <h1> MANUTENÇÕES CADASTRADOS</h1>
    <div class="row mb-3">
        @if(auth()->user()->permitions != 2)
            <a class="btn btn-success" href="/manutencoes/create/">Cadastrar Nova Manutenção</a>
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
                @foreach($manutencoes as $manutencao)
                <tr>
                <th scope="row">{{ $manutencao->id_manutencao }}</th>
                <td>{{ $manutencao->setor }}</td>
                <td>{{ $manutencao->descricao }}</td>
                    <td>R$ {{ $manutencao->valor }}</td>
                <td>{{ $manutencao->nivel }}</td>
                    @if(auth()->user()->permitions != 2)
                <td>
                    <form action="/manutencoes/{{$manutencao->id_manutencao}}" method="post">
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
                    <input type="text" value="R$ {{$valor_manutencao}}" readonly disabled>
                </td>
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
