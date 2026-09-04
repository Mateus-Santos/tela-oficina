@extends('layouts.layout')

@section('content')

<section class="container cadastro">

<h1>
    <i class="bi bi-tags-fill"></i>
    CATEGORIAS FINANCEIRAS
</h1>

@if (session('success'))
    <div class="alert alert-success mensseger_error_container">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger mensseger_error_container">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger mensseger_error_container">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="d-flex justify-content-end mb-4">

    <a
        href="{{ route('categorias-financeiras.create') }}"
        class="btn btn-success"
    >
        <i class="bi bi-plus-circle"></i>
        Cadastrar Categoria
    </a>

</div>

<div class="table-responsive">

    <table class="table table-striped table-hover align-middle">

        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Tipo</th>
                <th>Status</th>
                <th>Cadastro</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>

        <tbody>

            @forelse ($categorias as $categoria)

                <tr>

                    <td>
                        {{ $categoria->id }}
                    </td>

                    <td>
                        <i class="bi bi-tag me-1"></i>
                        {{ $categoria->nome }}
                    </td>

                    <td>

                        @if ($categoria->tipo === 'entrada')

                            <span class="badge bg-success">
                                <i class="bi bi-arrow-down-circle"></i>
                                Entrada
                            </span>

                        @else

                            <span class="badge bg-danger">
                                <i class="bi bi-arrow-up-circle"></i>
                                Saída
                            </span>

                        @endif

                    </td>

                    <td>

                        @if ($categoria->ativo)

                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i>
                                Ativo
                            </span>

                        @else

                            <span class="badge bg-secondary">
                                <i class="bi bi-x-circle"></i>
                                Inativo
                            </span>

                        @endif

                    </td>

                    <td>
                        {{ $categoria->created_at?->format('d/m/Y') }}
                    </td>

                    <td class="text-center">

                        <div class="d-flex justify-content-center gap-2">

                            <a
                                href="{{ route('categorias-financeiras.show', $categoria) }}"
                                class="btn btn-sm btn-info"
                                title="Visualizar"
                            >
                                <i class="bi bi-eye"></i>
                            </a>

                            <a
                                href="{{ route('categorias-financeiras.edit', $categoria) }}"
                                class="btn btn-sm btn-primary"
                                title="Editar"
                            >
                                <i class="bi bi-pencil-square"></i>
                            </a>

                            <form
                                action="{{ route('categorias-financeiras.destroy', $categoria) }}"
                                method="POST"
                                class="d-inline"
                                onsubmit="return confirm('Tem certeza que deseja excluir esta categoria financeira?');"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="btn btn-sm btn-danger"
                                    title="Excluir"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>

                            </form>

                        </div>

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="6" class="text-center py-4">

                        <i class="bi bi-tags fs-3 d-block mb-2"></i>

                        Nenhuma categoria financeira cadastrada.

                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>

</div>

@if ($categorias->hasPages())

    <div class="d-flex justify-content-center mt-4">
        {{ $categorias->links() }}
    </div>

@endif


</section>

@endsection
