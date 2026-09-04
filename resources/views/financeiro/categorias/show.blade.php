@extends('layouts.layout')

@section('content')

<section class="container cadastro">


<h1>
    <i class="bi bi-tags-fill"></i>
    CATEGORIA FINANCEIRA
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

<div class="campos">

    <div class="row mb-3">

        <div class="col-md-2">

            <label class="form-label">
                <i class="bi bi-hash"></i>
                ID
            </label>

            <div class="form-control bg-light">
                {{ $categoriaFinanceira->id }}
            </div>

        </div>

        <div class="col-md-6">

            <label class="form-label">
                <i class="bi bi-tag"></i>
                Nome
            </label>

            <div class="form-control bg-light">
                {{ $categoriaFinanceira->nome }}
            </div>

        </div>

        <div class="col-md-4">

            <label class="form-label">
                <i class="bi bi-arrow-left-right"></i>
                Tipo
            </label>

            <div class="form-control bg-light">

                @if ($categoriaFinanceira->tipo === 'entrada')

                    <span class="text-success">
                        <i class="bi bi-arrow-down-circle-fill"></i>
                        Entrada
                    </span>

                @else

                    <span class="text-danger">
                        <i class="bi bi-arrow-up-circle-fill"></i>
                        Saída
                    </span>

                @endif

            </div>

        </div>

    </div>

    <div class="row mb-3">

        <div class="col-md-4">

            <label class="form-label">
                <i class="bi bi-toggle-on"></i>
                Status
            </label>

            <div class="form-control bg-light">

                @if ($categoriaFinanceira->ativo)

                    <span class="text-success">
                        <i class="bi bi-check-circle-fill"></i>
                        Ativo
                    </span>

                @else

                    <span class="text-secondary">
                        <i class="bi bi-x-circle-fill"></i>
                        Inativo
                    </span>

                @endif

            </div>

        </div>

        <div class="col-md-4">

            <label class="form-label">
                <i class="bi bi-calendar-plus"></i>
                Cadastrado em
            </label>

            <div class="form-control bg-light">
                {{ $categoriaFinanceira->created_at?->format('d/m/Y H:i') }}
            </div>

        </div>

        <div class="col-md-4">

            <label class="form-label">
                <i class="bi bi-calendar-check"></i>
                Última atualização
            </label>

            <div class="form-control bg-light">
                {{ $categoriaFinanceira->updated_at?->format('d/m/Y H:i') }}
            </div>

        </div>

    </div>

</div>

<div class="col text-center">

    <a
        href="{{ route('categorias-financeiras.index') }}"
        class="btn btn-secondary"
    >
        <i class="bi bi-arrow-left"></i>
        Voltar
    </a>

    <a
        href="{{ route('categorias-financeiras.edit', $categoriaFinanceira) }}"
        class="btn btn-primary"
    >
        <i class="bi bi-pencil-square"></i>
        Editar
    </a>

    <form
        action="{{ route('categorias-financeiras.destroy', $categoriaFinanceira) }}"
        method="POST"
        class="d-inline"
        onsubmit="return confirm('Tem certeza que deseja excluir esta categoria financeira?');"
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

</div>


</section>

@endsection
