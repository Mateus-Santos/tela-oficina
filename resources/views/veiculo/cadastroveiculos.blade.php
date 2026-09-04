@extends('layouts.layout')

@vite(['resources/js/validateForm.js'])

@section('content')

<section class="container cadastro">


<h1>
    <i class="bi bi-gear"></i> CADASTRO DE VEÍCULOS
</h1>

<div class="campos">

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('veiculos.store') }}" method="POST">
        @csrf

        <div class="row mb-3">

            <div class="col-md-6">
                <label class="form-label" for="montadora_id">
                    Montadora:*
                </label>

                <select
                    class="form-control"
                    id="montadora_id"
                    name="montadora_id"
                    required
                >
                    <option value="">Escolher...</option>

                    @foreach ($montadoras as $montadora)
                        <option
                            value="{{ $montadora->id }}"
                            @selected(old('montadora_id') == $montadora->id)
                        >
                            {{ $montadora->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="nome">
                    Veículo:*
                </label>

                <input
                    type="text"
                    class="form-control"
                    id="nome"
                    name="nome"
                    value="{{ old('nome') }}"
                    placeholder="Digite o nome do veículo"
                    maxlength="255"
                >
            </div>

        </div>

        <div class="col text-center mt-4">
            <button
                type="submit"
                class="btn btn-success"
            >
                Cadastrar Veículo
            </button>
        </div>

    </form>

</div>

</section>

@endsection
