@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="LISTAR FORNECEDORES"
        icon="bi-building"
        create-route="fornecedores.create"
        create-text="Novo Fornecedor"
        create-icon="bi-plus-lg"
    />

    @if (session('success'))
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i>
            {{ session('error') }}
        </div>
    @endif

    <x-filtros-container
        action="{{ route('fornecedores.index') }}"
        id="filtros-fornecedores"
        :collapsible="false"
    >
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-5">
                <label for="nome" class="form-label">
                    <i class="bi bi-building"></i>
                    Fornecedor
                </label>
                <input
                    type="text"
                    name="nome"
                    id="nome"
                    class="filtros-container__input"
                    value="{{ request('nome') }}"
                    placeholder="Nome do fornecedor"
                >
            </div>

            <div class="col-12 col-md-5">
                <label for="cnpj" class="form-label">
                    <i class="bi bi-card-text"></i>
                    CNPJ
                </label>
                <input
                    type="text"
                    name="cnpj"
                    id="cnpj"
                    class="filtros-container__input"
                    value="{{ request('cnpj') }}"
                    placeholder="CNPJ do fornecedor"
                >
            </div>

            <div class="col-12 col-md-2">
                <div class="filtros-container__actions">
                    <button
                        type="submit"
                        class="btn btn-primary"
                        title="Filtrar fornecedores"
                    >
                        <i class="bi bi-search"></i>
                        Filtrar
                    </button>

                    <a
                        href="{{ route('fornecedores.index') }}"
                        class="btn btn-secondary"
                        title="Limpar filtros"
                    >
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </div>
        </div>
    </x-filtros-container>

    @php
        $possuiFiltros = request()->filled('nome') || request()->filled('cnpj');
    @endphp

    @if ($fornecedores->isEmpty())
        <div class="alert alert-{{ $possuiFiltros ? 'warning' : 'info' }}">
            <i class="bi {{ $possuiFiltros ? 'bi-exclamation-triangle' : 'bi-info-circle' }}"></i>
            {{ $possuiFiltros
                ? 'Nenhum fornecedor encontrado com os filtros informados.'
                : 'Nenhum fornecedor cadastrado.' }}
        </div>
    @endif

    @if ($fornecedores->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">FORNECEDOR</th>
                        <th scope="col">CNPJ</th>
                        <th scope="col">TELEFONE</th>
                        <th scope="col">E-MAIL</th>
                        <th scope="col">PRODUTOS</th>
                        <th scope="col">COMPRAS</th>
                        <th scope="col">AÇÕES</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($fornecedores as $fornecedor)
                        <tr>
                            <td>{{ $fornecedor->id }}</td>

                            <td>
                                <strong>{{ $fornecedor->nome }}</strong>
                            </td>

                            <td>
                                {{ $fornecedor->cnpj ?: 'Não informado' }}
                            </td>

                            <td>
                                @if ($fornecedor->telefone)
                                    <i class="bi bi-telephone"></i>
                                    {{ $fornecedor->telefone }}
                                @else
                                    <span class="text-muted">
                                        <i class="bi bi-dash-circle"></i>
                                        Não informado
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if ($fornecedor->email)
                                    <i class="bi bi-envelope"></i>
                                    {{ $fornecedor->email }}
                                @else
                                    <span class="text-muted">
                                        <i class="bi bi-dash-circle"></i>
                                        Não informado
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if ($fornecedor->produtos_count > 0)
                                    <span class="badge bg-primary">
                                        <i class="bi bi-box-seam"></i>
                                        {{ $fornecedor->produtos_count }}
                                    </span>
                                @else
                                    <span class="text-muted">
                                        <i class="bi bi-dash-circle"></i>
                                        Nenhum
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if ($fornecedor->compras_count > 0)
                                    <span class="badge bg-success">
                                        <i class="bi bi-cart-check"></i>
                                        {{ $fornecedor->compras_count }}
                                    </span>
                                @else
                                    <span class="text-muted">
                                        <i class="bi bi-dash-circle"></i>
                                        Nenhuma
                                    </span>
                                @endif
                            </td>

                            <td>
                                <div class="d-flex gap-1">
                                    <a
                                        href="{{ route('fornecedores.show', $fornecedor) }}"
                                        class="btn btn-success btn-sm"
                                        title="Visualizar fornecedor"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <a
                                        href="{{ route('fornecedores.edit', $fornecedor) }}"
                                        class="btn btn-primary btn-sm"
                                        title="Editar fornecedor"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    @if ($fornecedor->compras_count == 0)
                                        <form
                                            action="{{ route('fornecedores.destroy', $fornecedor) }}"
                                            method="POST"
                                            onsubmit="return confirm('Tem certeza que deseja excluir este fornecedor?');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="btn btn-danger btn-sm"
                                                title="Excluir fornecedor"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button
                                            type="button"
                                            class="btn btn-secondary btn-sm"
                                            disabled
                                            title="Não é possível excluir: existem compras vinculadas"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($fornecedores->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $fornecedores->links() }}
            </div>
        @endif
    @endif

</div>

@endsection
