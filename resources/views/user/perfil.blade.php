@extends('layouts.layout')

@section('content')
    <section class="container cadastro py-4">
        <h1 class="mb-4">
            <i class="bi bi-person-gear"></i>
            MEU PERFIL
        </h1>

        @if ($errors->any())
            <div class="alert alert-danger">
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
            action="{{ route('perfil.update') }}"
            method="POST"
            class="row g-3"
        >
            @csrf
            @method('PUT')

            <div class="campos">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label
                            class="form-label"
                            for="nome"
                        >
                            Nome Completo *
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="nome"
                            name="nome"
                            value="{{ old(
                                'nome',
                                $user->pessoa?->nome
                            ) }}"
                            maxlength="255"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label
                            class="form-label"
                            for="email"
                        >
                            E-mail *
                        </label>

                        <input
                            type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            value="{{ old(
                                'email',
                                $user->email
                            ) }}"
                            maxlength="255"
                            required
                        >
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label
                            class="form-label"
                            for="cpf"
                        >
                            CPF
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="cpf"
                            name="cpf"
                            value="{{ old(
                                'cpf',
                                $user->pessoa?->cpf
                            ) }}"
                            maxlength="14"
                        >
                    </div>

                    <div class="col-md-3">
                        <label
                            class="form-label"
                            for="rg"
                        >
                            RG
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="rg"
                            name="rg"
                            value="{{ old(
                                'rg',
                                $user->pessoa?->rg
                            ) }}"
                            maxlength="20"
                        >
                    </div>

                    <div class="col-md-3">
                        <label
                            class="form-label"
                            for="telefone_1"
                        >
                            Telefone Principal
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="telefone_1"
                            name="telefone_1"
                            value="{{ old(
                                'telefone_1',
                                $user->pessoa?->telefone_1
                            ) }}"
                            maxlength="16"
                        >
                    </div>

                    <div class="col-md-3">
                        <label
                            class="form-label"
                            for="telefone_2"
                        >
                            Telefone Secundário
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="telefone_2"
                            name="telefone_2"
                            value="{{ old(
                                'telefone_2',
                                $user->pessoa?->telefone_2
                            ) }}"
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
                            Data de Nascimento
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="data_nascimento"
                            name="data_nascimento"
                            value="{{ old(
                                'data_nascimento',
                                $user->pessoa?->data_nascimento
                            ) }}"
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">
                            Perfil
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            value="{{ ucfirst($user->perfil) }}"
                            disabled
                        >

                        <div class="form-text">
                            O perfil de acesso não pode ser alterado nesta tela.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col text-center">
                <button
                    type="submit"
                    class="btn btn-success"
                >
                    <i class="bi bi-check-circle"></i>
                    Salvar Alterações
                </button>
            </div>
        </form>
    </section>
@endsection

@section('scripts')
    @vite(['resources/js/cadUser.js'])
@endsection
