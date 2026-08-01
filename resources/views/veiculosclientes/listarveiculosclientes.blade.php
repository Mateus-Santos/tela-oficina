@extends('layouts.layout')

@section('content')

<div class="container cadastro">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>LISTAR VEÍCULOS</h1>
        <a href="{{ route('veiculosclientes.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Novo Veículo</a>
    </div>

    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Placa</th>
                <th scope="col">Ano</th>
                <th scope="col">Montadora</th>
                <th scope="col">Cor</th>
                <th scope="col">Usuário</th>
                <th scope="col" class="text-center">Editar</th>
                <th scope="col" class="text-center">Excluir</th>
            </tr>
        </thead>
        <tbody>
            @forelse($veiculosclientes as $veiculoscliente)
            <tr>
                <th scope="row">{{ $veiculoscliente->id }}</th>
                <td>{{ $veiculoscliente->placa }}</td>
                <td>{{ $veiculoscliente->ano }}</td>
                <td>{{ $veiculoscliente->veiculo?->montadora?->nome ?? 'N/A' }}</td>
                <td>{{ $veiculoscliente->cor }}</td>
                <td>{{ $veiculoscliente->cliente?->pessoa?->nome ?? 'N/A' }}</td>
                
                <td class="text-center">
                    <a href="{{ route('veiculosclientes.edit', $veiculoscliente->id) }}" class="btn btn-sm btn-info text-white" title="Editar">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                </td>

                <td class="text-center">
                    <form action="{{ route('veiculosclientes.destroy', $veiculoscliente->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este veículo?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" title="Excluir">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    Nenhum veículo cadastrado até o momento.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-3">
    {{ $veiculosclientes->links() }}
    </div>
</div>

@endsection