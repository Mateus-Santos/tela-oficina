@extends('layouts.layout')

@section('content')

<div class="container cadastro">

```
<div class="d-flex justify-content-between align-items-center mb-3">

    <h1>LISTAR MONTADORAS</h1>

    <a
        href="{{ route('montadoras.create') }}"
        class="btn btn-primary"
    >
        <i class="bi bi-plus-lg"></i> Nova Montadora
    </a>

</div>

{{-- Mensagens de sucesso/erro --}}

@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

{{-- Filtros --}}

<div class="filtros-container">

    <form
        method="GET"
        action="{{ route('montadoras.index') }}"
        class="filtros-container__form"
    >

        {{-- Busca por nome --}}

        <input
            type="text"
            name="nome"
            class="filtros-container__input"
            placeholder="Nome da montadora..."
            value="{{ request('nome') }}"
        >

        {{-- Botão filtrar --}}

        <button
            class="btn btn-warning"
            type="submit"
        >
            <i class="bi bi-funnel"></i> Filtrar
        </button>

        {{-- Limpar filtros --}}

        <a
            href="{{ route('montadoras.index') }}"
            class="btn btn-secondary"
        >
            <i class="bi bi-filter"></i> Limpar Filtros
        </a>

    </form>

</div>

{{-- Tabela de Montadoras --}}

<table class="table table-striped table-hover align-middle">

    <thead>

        <tr>
            <th scope="col">ID</th>
            <th scope="col">MONTADORA</th>
            <th scope="col" class="text-center">EDITAR</th>
            <th scope="col" class="text-center">EXCLUIR</th>
        </tr>

    </thead>

    <tbody>

        @forelse ($montadoras as $montadora)

            <tr>

                <th scope="row">
                    {{ $montadora->id }}
                </th>

                <td>
                    {{ $montadora->nome }}
                </td>

                <td class="text-center">

                    <a
                        href="{{ route('montadoras.edit', $montadora->id) }}"
                        class="btn btn-sm btn-info text-white"
                        title="Editar"
                    >
                        <i class="bi bi-pencil-square"></i>
                    </a>

                </td>

                <td class="text-center">

                    <form
                        action="{{ route('montadoras.destroy', $montadora->id) }}"
                        method="POST"
                        onsubmit="return confirm('Tem certeza que deseja excluir esta Montadora?');"
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
                    colspan="4"
                    class="text-center text-muted py-4"
                >

                    @if (request()->filled('nome'))
                        Nenhuma montadora encontrada com o filtro informado.
                    @else
                        Nenhuma montadora cadastrada até o momento.
                    @endif

                </td>

            </tr>

        @endforelse

    </tbody>

</table>

{{-- Paginação --}}

@if ($montadoras->hasPages())

    <div class="d-flex justify-content-center mt-4">
        {{ $montadoras->links() }}
    </div>

@endif
```

</div>

@endsection
