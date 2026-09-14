@extends('layouts.layout')

@section('content')

<section class="container cadastro">

    <h1>
        <i class="bi bi-gear"></i> CADASTRO DE PRODUTOS
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
        action="{{ route('produtos.store') }}"
        enctype="multipart/form-data"
        method="POST"
        class="row g-3"
    >
        @csrf

        @include('produto._form')

        {{-- Botão --}}
        <div class="col text-center">
            <button type="submit" class="btn btn-success">
                Cadastrar
            </button>
        </div>

    </form>

</section>

@endsection

@section('scripts')
@vite(['resources/js/cadProduto.js'])
@endsection
