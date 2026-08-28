@extends('layouts.layout')

@section('content')

<div class="container cadastro">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>LISTAR VEÍCULOS</h1>

    <a href="{{ route('veiculos.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Novo Veículo
    </a>
</div>

{{-- Filtros --}}
<div class="filtros-container">

    <form
        method="GET"
        action="{{ route('veiculos.index') }}"
        class="filtros-container__form"
    >

        {{-- Busca por veículo --}}
        <input
            type="text"
            name="veiculo"
            class="filtros-container__input"
            placeholder="Nome do veículo..."
            value="{{ request('veiculo') }}"
        >

        {{-- Filtro por montadora --}}
        <select
            name="montadora"
            class="filtros-container__select"
        >
            <option value="">Todas as montadoras</option>

            @foreach($montadoras as $montadora)
                <option
                    value="{{ $montadora->id }}"
                    {{ request('montadora') == $montadora->id ? 'selected' : '' }}
                >
                    {{ $montadora->nome }}
                </option>
            @endforeach

        </select>

        {{-- Botão filtrar --}}
        <button class="btn btn-warning" type="submit">
            <i class="bi bi-funnel"></i> Filtrar
        </button>

        {{-- Limpar filtros --}}
        <a
            href="{{ route('veiculosclientes.index') }}"
            class="btn btn-secondary"
        >
            <i class="bi bi-filter"></i> Limpar Filtros
        </a>

    </form>

</div>

{{-- Tabela de Veículos --}}
<table class="table table-striped table-hover align-middle">

    <thead>
        <tr>
            <th scope="col">ID</th>
            <th scope="col">MONTADORA</th>
            <th scope="col">VEÍCULO</th>
            <th scope="col" class="text-center">EDITAR</th>
            <th scope="col" class="text-center">EXCLUIR</th>
        </tr>
    </thead>

    <tbody>

        @forelse($veiculos as $veiculo)

            <tr>

                <th scope="row">
                    {{ $veiculo->id }}
                </th>

                <td>{{ $veiculo->montadora?->nome ?? 'N/A' }}</td>

                <td>{{ $veiculo->nome ?? 'N/A' }}</td>

                <td class="text-center">

                    <a
                        href="{{ route('veiculosclientes.edit', $veiculo->id) }}"
                        class="btn btn-sm btn-info text-white"
                        title="Editar"
                    >
                        <i class="bi bi-pencil-square"></i>
                    </a>

                </td>

                <td class="text-center">

                    <form
                        action="{{ route('veiculosclientes.destroy', $veiculo->id) }}"
                        method="POST"
                        onsubmit="return confirm('Tem certeza que deseja excluir este Veículo?');"
                    >

                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="btn btn-sm btn-danger"
                            title="Excluir"
                        >
                            <i class="bi bi-trash3"></i>
                        </button>

                    </form>

                </td>

            </tr>

        @empty

            <tr>
                <td
                    colspan="5"
                    class="text-center text-muted py-4"
                >
                    @if(request()->hasAny(['veiculo', 'montadora']))
                        Nenhum veículo encontrado com os filtros informados.
                    @else
                        Nenhum Veículo cadastrado até o momento.
                    @endif
                </td>
            </tr>

        @endforelse

    </tbody>

</table>

{{-- Paginação --}}
@if($veiculos->hasPages())

    <div class="d-flex justify-content-center mt-4">
        {{ $veiculos->links() }}
    </div>

@endif

</div>

@endsection
