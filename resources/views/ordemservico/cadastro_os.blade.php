@extends('layouts.layout')

@vite(['resources/js/validateForm.js'])

@section('content')

<div class="container cadastro">

    <h1>
        <i class="bi bi-clipboard2-plus"></i>
        CADASTRAR ORDEM DE SERVIÇO
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

        <form
            action="{{ route('ordemservicos.store') }}"
            method="POST"
        >

            @csrf

            <livewire:ordem-servico.seletor-cliente-veiculo
                :cliente-selecionado-id="
                    old('cliente_id')
                        ? (int) old('cliente_id')
                        : null
                "
                :veiculo-selecionado-id="
                    old('veiculo_cliente_id')
                        ? (int) old('veiculo_cliente_id')
                        : null
                "
            />

            <div class="row g-3 mb-4">

                {{-- SETOR --}}
                <div class="col-12 col-md-4">

                    <label
                        class="form-label"
                        for="setor_servico_id"
                    >
                        Setor de Serviço:*
                    </label>

                    <select
                        class="form-control @error('setor_servico_id') is-invalid @enderror"
                        id="setor_servico_id"
                        name="setor_servico_id"
                        required
                    >

                        <option value="">
                            Selecione...
                        </option>

                        @foreach($setorservicos as $setorservico)

                            <option
                                value="{{ $setorservico->id }}"
                                @selected(
                                    old('setor_servico_id')
                                    == $setorservico->id
                                )
                            >
                                {{ $setorservico->setor }}
                            </option>

                        @endforeach

                    </select>

                    @error('setor_servico_id')

                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>

                    @enderror

                </div>

            </div>

            <div class="row g-3 mb-4">

                {{-- DESCRIÇÃO --}}
                <div class="col-12 col-lg-8">

                    <label
                        class="form-label"
                        for="descricao"
                    >
                        Descrição:*
                    </label>

                    <input
                        type="text"
                        class="form-control @error('descricao') is-invalid @enderror"
                        id="descricao"
                        name="descricao"
                        value="{{ old('descricao') }}"
                        placeholder="Descrição do diagnóstico"
                        maxlength="250"
                        required
                    >

                    @error('descricao')

                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>

                    @enderror

                </div>

            </div>

            <div class="row g-3 mb-4">

                {{-- VALOR --}}
                <div class="col-12 col-md-4">

                    <label
                        class="form-label"
                        for="valor"
                    >
                        Valor (R$):*
                    </label>

                    <input
                        type="text"
                        class="form-control @error('valor') is-invalid @enderror"
                        id="valor"
                        name="valor"
                        value="{{ old('valor') }}"
                        placeholder="Ex.: 150,00"
                        required
                    >

                    @error('valor')

                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>

                    @enderror

                </div>

            </div>

            <div class="text-center mt-4">

                <button
                    type="submit"
                    class="btn btn-success"
                >
                    <i class="bi bi-check-lg me-1"></i>
                    Cadastrar OS
                </button>

            </div>

        </form>

    </div>

</div>

@endsection
