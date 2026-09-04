@extends('layouts.layout')

@section('content')

<section class="container cadastro">

    <x-list-header
        title="LISTAR CLIENTES"
        icon="bi-people"
        create-route="clientes.create"
        create-text="Novo Cliente"
        create-icon="bi-plus-lg"
    />

    @if ($errors->any())
        <div class="alert alert-danger mensseger_error_container">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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

    <x-filtros-container
        action="{{ route('clientes.index') }}"
        id="filtros-clientes"
        :collapsible="false"
    >
        <div class="row g-3 align-items-end">

            <div class="col-12 col-md-6">
                <label for="nome" class="form-label">
                    <i class="bi bi-person"></i> Nome
                </label>

                <input
                    type="text"
                    id="nome"
                    name="nome"
                    class="filtros-container__input"
                    placeholder="Nome do cliente"
                    value="{{ request('nome') }}"
                >
            </div>

            <div class="col-12 col-md-6">
                <div class="filtros-container__actions">

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                        Filtrar
                    </button>

                    <a
                        href="{{ route('clientes.index') }}"
                        class="btn btn-secondary"
                        title="Limpar filtros"
                    >
                        <i class="bi bi-x-lg"></i>
                    </a>

                </div>
            </div>

        </div>
    </x-filtros-container>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">

            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">NOME</th>
                    <th scope="col">PONTOS</th>
                    <th scope="col">AÇÕES</th>
                </tr>
            </thead>

            <tbody>

                @forelse ($clientes as $cliente)

                    <tr>

                        <td>
                            {{ str_pad($cliente->id, 6, '0', STR_PAD_LEFT) }}
                        </td>

                        <td>
                            {{ $cliente->pessoa?->nome ?? 'Sem nome cadastrado' }}
                        </td>

                        <td>
                            {{ $cliente->pessoa?->cliente?->pontos ?? 'Sem pontos cadastrados' }}
                        </td>

                        <td>
                            <div class="d-flex gap-1">

                                <a
                                    href="{{ route('clientes.edit', $cliente->id) }}"
                                    class="btn btn-warning btn-sm"
                                    title="Editar cliente"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <form
                                    action="{{ route('clientes.destroy', $cliente->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Tem certeza que deseja excluir este cliente?');"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn btn-danger btn-sm"
                                        title="Excluir cliente"
                                    >
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>

                            </div>
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="4" class="text-center">
                            <i class="bi bi-info-circle"></i>
                            @if (request()->filled('nome'))
                                Nenhum cliente encontrado para o filtro informado.
                            @else
                                Nenhum cliente cadastrado.
                            @endif
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>
    </div>

    @if (method_exists($clientes, 'hasPages') && $clientes->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $clientes->links() }}
        </div>
    @endif

</section>

@endsection
