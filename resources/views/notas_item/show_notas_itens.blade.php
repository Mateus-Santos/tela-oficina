@extends('layouts.layout')

@section('content')

<div class="container cadastro">
    <h1>DETALHES DA NOTA #{{ $nota->id }}</h1>

    {{-- Informações Gerais da Nota --}}
    <table class="table">
        <thead>
            <tr>
                <th scope="col">ID Nota</th>
                <th scope="col">Status</th>
                <th scope="col">Data Criação</th>
                <th scope="col">Cliente</th>
                <th scope="col">Veículo / Placa</th>
                <th scope="col">PDF</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $nota->id }}</td>
                <td>{{ $nota->status }}</td>
                <td>{{ $nota->created_at ? $nota->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                <td>{{ $nota->cliente?->pessoa?->nome ?? 'Cliente Geral / Balcão' }}</td>
                <td>{{ $nota->veiculoscliente?->placa ?? 'N/A' }}</td>
                <td>
                    <a href="{{ route('notas.pdf', $nota->id) }}" target="_blank" class="btn btn-danger">
                        <i class="bi bi-printer"></i> PDF
                    </a>
                </td>
            </tr>
        </tbody>
    </table>

    <hr class="my-4">

    {{-- Seção de Itens da Nota --}}
    @if($itens->isEmpty())
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <span>Esta nota ainda não possui itens cadastrados.</span>
            @if(auth()->user() && auth()->user()->permitions != 2)
                <a class="btn btn-success" href="/notasitem/{{ $nota->id }}/edit">
                    <i class="bi bi-plus-circle"></i> Adicionar Item
                </a>
            @endif
        </div>
    @else
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>ITENS DA NOTA</h2>
            @if(auth()->user() && auth()->user()->permitions != 2)
                <a class="btn btn-success" href="/notasitem/{{ $nota->id }}/edit">
                    <i class="bi bi-plus-circle"></i> Adicionar Item
                </a>
            @endif
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Tipo</th>
                    <th scope="col">Descrição / Nome</th>
                    <th scope="col">Qtd.</th>
                    <th scope="col">Preço Unit.</th>
                    <th scope="col">Desconto</th>
                    <th scope="col">Subtotal</th>
                    @if(auth()->user() && auth()->user()->permitions != 2)
                        <th scope="col">Ações</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($itens as $item)
                <tr>
                    <th scope="row">{{ $item->id }}</th>

                    {{-- Identifica se é Produto ou Ordem de Serviço --}}
                    <td>
                        @if(str_contains($item->itemable_type, 'Produto'))
                            <span class="badge bg-primary">Produto</span>
                        @else
                            <span class="badge bg-info text-dark">O.S.</span>
                        @endif
                    </td>

                    {{-- Busca a descrição ou o nome do item carregado --}}
                    <td>
                        {{ $item->descricao ?? $item->itemable?->nome ?? $item->itemable?->descricao ?? 'Item sem nome' }}
                    </td>

                    <td>{{ $item->quantidade }}</td>
                    <td>R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                    <td>R$ {{ number_format($item->desconto, 2, ',', '.') }}</td>
                    <td>R$ {{ number_format(($item->quantidade * $item->valor_unitario) - $item->desconto, 2, ',', '.') }}</td>

                    @if(auth()->user() && auth()->user()->permitions != 2)
                    <td>
                        <form action="/notasitem/{{ $item->id }}" method="POST" onsubmit="return confirm('Deseja realmente remover este item da nota?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Resumo Financeiro da Nota --}}
        <div class="d-flex justify-content-end align-items-center gap-2 mt-3">
            <strong>Valor Total da Nota:</strong>
            <input type="text" class="form-control w-auto text-end font-weight-bold" value="R$ {{ number_format($valorTotal, 2, ',', '.') }}" readonly disabled>
        </div>
    @endif
</div>

@endsection
