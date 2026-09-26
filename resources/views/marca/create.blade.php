@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="mb-1">Cadastrar Marca</h1>
            <p class="text-muted mb-0">
                Cadastre uma nova marca para utilização nos produtos.
            </p>
        </div>

        <a
            href="{{ route('marcas.index') }}"
            class="btn btn-outline-secondary"
        >
            <i class="bi bi-arrow-left me-1"></i>
            Voltar
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form
                action="{{ route('marcas.store') }}"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf

                @include('marca._form')

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a
                        href="{{ route('marcas.index') }}"
                        class="btn btn-secondary"
                    >
                        <i class="bi bi-x-lg me-1"></i>
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="btn btn-success"
                    >
                        <i class="bi bi-check-lg me-1"></i>
                        Cadastrar Marca
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
