@extends('layouts.layout')

@section('content')
<section class="container cadastro">
    <h1>
        <i class="bi bi-gear"></i> EDITAR PRODUTO
    </h1>

    {{-- Erros --}}
    @if ($errors->any())
        <div class="alert alert-danger mensseger_error_container">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Sucesso --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form
        id="form-produto"
        action="{{ route('produtos.update', $produto->id) }}"
        enctype="multipart/form-data"
        method="POST"
        class="row g-3"
    >
        @csrf
        @method('PUT')

        <livewire:produto.form-produto :produto="$produto" />

        {{-- Botão --}}
        <div class="col text-center">
            <button type="submit" class="btn btn-primary">
                Editar
            </button>
        </div>
    </form>
</section>
@endsection

@section('scripts')
@vite(['resources/js/cadProduto.js'])
@endsection
