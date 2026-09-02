@extends('layouts.layout')

@section('content')

<section class="container cadastro">

    <h1>
        <i class="bi bi-person-gear"></i>
        EDITAR CLIENTE
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
        action="{{ route('clientes.update', $cliente->id) }}"
        method="POST"
        class="row g-3"
    >
        @csrf
        @method('PUT')

        <div class="campos">

            {{-- Nome / E-mail --}}
            <div class="row mb-3">

                <div class="col-md-6">
                    <label class="form-label" for="nome">
                        <i class="bi bi-person"></i>
                        Nome Completo:*
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="nome"
                        name="nome"
                        value="{{ old('nome', $cliente->pessoa?->nome) }}"
                        placeholder="Digite o nome completo"
                        maxlength="100"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="email">
                        <i class="bi bi-envelope"></i>
                        E-mail:
                    </label>

                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        value="{{ old('email', $usuario?->email) }}"
                        placeholder="email@dominio.com"
                        maxlength="150"
                    >
                </div>

            </div>

            {{-- Telefones --}}
            <div class="row mb-3">

                <div class="col-md-6">
                    <label class="form-label" for="telefone_1">
                        <i class="bi bi-telephone"></i>
                        Telefone Principal:*
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="telefone_1"
                        name="telefone_1"
                        value="{{ old('telefone_1', $cliente->pessoa?->telefone_1) }}"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="telefone_2">
                        <i class="bi bi-telephone-plus"></i>
                        Telefone Secundário:
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="telefone_2"
                        name="telefone_2"
                        value="{{ old('telefone_2', $cliente->pessoa?->telefone_2) }}"
                    >
                </div>

            </div>

            {{-- CPF / RG --}}
            <div class="row mb-3">

                <div class="col-md-6">
                    <label class="form-label" for="cpf">
                        <i class="bi bi-person-vcard"></i>
                        CPF:*
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="cpf"
                        name="cpf"
                        value="{{ old('cpf', $cliente->pessoa?->cpf) }}"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="rg">
                        <i class="bi bi-card-text"></i>
                        RG:*
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="rg"
                        name="rg"
                        value="{{ old('rg', $cliente->pessoa?->rg) }}"
                        required
                    >
                </div>

            </div>

            {{-- Data de nascimento / Pontos --}}
            <div class="row mb-3">

                <div class="col-md-6">
                    <label class="form-label" for="data_nascimento">
                        <i class="bi bi-calendar-event"></i>
                        Data de Nascimento:*
                    </label>

                    <input
                        type="date"
                        class="form-control"
                        id="data_nascimento"
                        name="data_nascimento"
                        value="{{ old('data_nascimento', $cliente->pessoa?->data_nascimento) }}"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="pontos">
                        <i class="bi bi-star"></i>
                        Pontos:
                    </label>

                    <input
                        type="number"
                        class="form-control"
                        id="pontos"
                        name="pontos"
                        value="{{ old('pontos', $cliente->pontos ?? 0) }}"
                        min="0"
                        required
                    >
                </div>

            </div>

            {{-- Confirmação --}}
            <div class="row mb-3">

                <div class="col">
                    <input
                        type="checkbox"
                        class="form-check-input"
                        id="confirmation"
                        required
                    >

                    <label for="confirmation" class="form-check-label">
                        Confirmo que as informações fornecidas são verdadeiras.
                    </label>
                </div>

            </div>

        </div>

        {{-- Botões --}}
        <div class="col text-center">

            <a
                href="{{ route('clientes.show', $cliente->id) }}"
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
                Salvar Alterações
            </button>

        </div>

    </form>

</section>

@endsection
