@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="VISUALIZAR FORNECEDOR"
        icon="bi-building"
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

    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <h2 class="h5 mb-4">
                <i class="bi bi-building"></i>
                Dados do fornecedor
            </h2>

            <div class="row g-4">

                <div class="col-12 col-md-6">
                    <small class="text-muted d-block">
                        Nome
                    </small>

                    <strong>
                        {{ $fornecedor->nome }}
                    </strong>
                </div>

                <div class="col-12 col-md-6">
                    <small class="text-muted d-block">
                        CNPJ
                    </small>

                    <span>
                        {{ $fornecedor->cnpj ?: 'Não informado' }}
                    </span>
                </div>

                <div class="col-12 col-md-6">
                    <small class="text-muted d-block">
                        Telefone
                    </small>

                    @if ($fornecedor->telefone)
                        <span>
                            <i class="bi bi-telephone"></i>
                            {{ $fornecedor->telefone }}
                        </span>
                    @else
                        <span class="text-muted">
                            Não informado
                        </span>
                    @endif
                </div>

                <div class="col-12 col-md-6">
                    <small class="text-muted d-block">
                        E-mail
                    </small>

                    @if ($fornecedor->email)
                        <span>
                            <i class="bi bi-envelope"></i>
                            {{ $fornecedor->email }}
                        </span>
                    @else
                        <span class="text-muted">
                            Não informado
                        </span>
                    @endif
                </div>

            </div>

        </div>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-12 col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">

                    <i class="bi bi-box-seam fs-2"></i>

                    <h3 class="h6 mt-2">
                        Produtos vinculados
                    </h3>

                    <span class="badge bg-primary fs-6">
                        {{ $fornecedor->produtos_count }}
                    </span>

                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">

                    <i class="bi bi-cart-check fs-2"></i>

                    <h3 class="h6 mt-2">
                        Compras vinculadas
                    </h3>

                    <span class="badge bg-success fs-6">
                        {{ $fornecedor->compras_count }}
                    </span>

                </div>
            </div>
        </div>

    </div>

    <div class="d-flex justify-content-end gap-2">

        <a
            href="{{ route('fornecedores.index') }}"
            class="btn btn-secondary"
        >
            <i class="bi bi-arrow-left"></i>
            Voltar
        </a>

        <a
            href="{{ route('fornecedores.edit', $fornecedor) }}"
            class="btn btn-primary"
        >
            <i class="bi bi-pencil"></i>
            Editar
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
                    class="btn btn-danger"
                >
                    <i class="bi bi-trash"></i>
                    Excluir
                </button>
            </form>
        @endif

    </div>

</div>

@endsection
