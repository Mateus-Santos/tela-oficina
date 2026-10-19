@extends('layouts.layout')

@section('content')
    <div class="container cadastro">
        <h1>CADASTRO DE USUÁRIO</h1>

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
            action="{{ route('users.store') }}"
            method="POST"
            class="row g-3"
        >
            @csrf

            <div class="campos">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label
                            class="form-label"
                            for="nome"
                        >
                            Nome Completo:*
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="nome"
                            name="nome"
                            value="{{ old('nome') }}"
                            placeholder="Digite o nome completo."
                            maxlength="255"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label
                            class="form-label"
                            for="email"
                        >
                            E-mail:*
                        </label>

                        <input
                            type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Digite o e-mail."
                            maxlength="255"
                            required
                        >
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label
                            class="form-label"
                            for="rg"
                        >
                            RG:
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="rg"
                            name="rg"
                            value="{{ old('rg') }}"
                            placeholder="00.000.000-0"
                            maxlength="20"
                        >
                    </div>

                    <div class="col-md-3">
                        <label
                            class="form-label"
                            for="cpf"
                        >
                            CPF:
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="cpf"
                            name="cpf"
                            value="{{ old('cpf') }}"
                            placeholder="000.000.000-00"
                            maxlength="14"
                        >
                    </div>

                    <div class="col-md-3">
                        <label
                            class="form-label"
                            for="telefone_1"
                        >
                            Telefone Principal:
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="telefone_1"
                            name="telefone_1"
                            value="{{ old('telefone_1') }}"
                            placeholder="(00) 00000-0000"
                            maxlength="16"
                        >
                    </div>

                    <div class="col-md-3">
                        <label
                            class="form-label"
                            for="telefone_2"
                        >
                            Telefone Secundário:
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="telefone_2"
                            name="telefone_2"
                            value="{{ old('telefone_2') }}"
                            placeholder="(00) 00000-0000"
                            maxlength="16"
                        >
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label
                            class="form-label"
                            for="data_nascimento"
                        >
                            Data de Nascimento:
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="data_nascimento"
                            name="data_nascimento"
                            value="{{ old('data_nascimento') }}"
                        >
                    </div>
                </div>

                <div class="col text-center">
                    <a
                        href="{{ route('users.index') }}"
                        class="btn btn-secondary"
                    >
                        Voltar
                    </a>

                    <button
                        type="submit"
                        class="btn btn-success"
                    >
                        Cadastrar
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/cadUser.js'])
@endsection
