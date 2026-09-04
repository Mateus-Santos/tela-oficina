@extends('layouts.layout')

@section('content')

<section class="container cadastro">


<h1>
    <i class="bi bi-tags-fill"></i>
    CADASTRO DE CATEGORIA FINANCEIRA
</h1>

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

<form
    action="{{ route('categorias-financeiras.store') }}"
    method="POST"
    class="row g-3"
>

    @csrf

    <div class="campos">

        <div class="row mb-3">

            <div class="col-md-8">

                <label for="nome" class="form-label">
                    <i class="bi bi-tag"></i>
                    Nome
                </label>

                <input
                    type="text"
                    id="nome"
                    name="nome"
                    class="form-control"
                    value="{{ old('nome') }}"
                    placeholder="Ex.: Serviços, Venda de Peças..."
                    maxlength="255"
                    required
                >

            </div>

            <div class="col-md-4">

                <label for="tipo" class="form-label">
                    <i class="bi bi-arrow-left-right"></i>
                    Tipo
                </label>

                <select
                    id="tipo"
                    name="tipo"
                    class="form-select"
                    required
                >

                    <option value="">
                        Selecione o tipo
                    </option>

                    <option
                        value="entrada"
                        {{ old('tipo') === 'entrada' ? 'selected' : '' }}
                    >
                        Entrada
                    </option>

                    <option
                        value="saida"
                        {{ old('tipo') === 'saida' ? 'selected' : '' }}
                    >
                        Saída
                    </option>

                </select>

            </div>

        </div>

        <div class="row mb-3">

            <div class="col-md-4">

                <label for="ativo" class="form-label">
                    <i class="bi bi-toggle-on"></i>
                    Status
                </label>

                <select
                    id="ativo"
                    name="ativo"
                    class="form-select"
                >

                    <option
                        value="1"
                        {{ old('ativo', '1') == '1' ? 'selected' : '' }}
                    >
                        Ativo
                    </option>

                    <option
                        value="0"
                        {{ old('ativo') === '0' ? 'selected' : '' }}
                    >
                        Inativo
                    </option>

                </select>

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

        <button
            type="submit"
            class="btn btn-success"
        >
            <i class="bi bi-check-circle"></i>
            Cadastrar
        </button>

    </div>

</form>


</section>

@endsection
