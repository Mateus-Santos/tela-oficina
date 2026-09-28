@extends('layouts.layout')

@section('content')

<section class="container cadastro">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">
            <i class="bi bi-box-seam"></i>
            CADASTRO DE PRODUTO
        </h1>

        <a
            href="{{ route('produtos.index') }}"
            class="btn btn-secondary"
        >
            <i class="bi bi-arrow-left"></i>
            Voltar
        </a>
    </div>

    {{-- Erros --}}
    @if($errors->any())
        <div class="alert alert-danger mensseger_error_container">
            <div class="fw-semibold mb-2">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Verifique os campos abaixo:
            </div>

            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Sucesso --}}
    @if(session('success'))
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-1"></i>
            {{ session('success') }}
        </div>
    @endif

    <form
        id="form-produto"
        action="{{ route('produtos.store') }}"
        method="POST"
        enctype="multipart/form-data"
    >
        @csrf

        <livewire:produto.form-produto />

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a
                href="{{ route('produtos.index') }}"
                class="btn btn-secondary"
            >
                <i class="bi bi-x-lg"></i>
                Cancelar
            </a>

            <button
                type="submit"
                class="btn btn-success"
            >
                <i class="bi bi-check-lg"></i>
                Cadastrar Produto
            </button>
        </div>
    </form>

</section>

@endsection

@section('scripts')
    @vite(['resources/js/produto/cadProduto.js'])
@endsection
