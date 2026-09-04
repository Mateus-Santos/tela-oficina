@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="CADASTRAR SETOR DE SERVIÇO"
        icon="bi-diagram-3"
    />

    @if ($errors->any())
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Verifique os campos abaixo:</strong>

            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">

            <form
                action="{{ route('setor-servicos.store') }}"
                method="POST"
            >
                @csrf

                <div class="row g-3">

                    <div class="col-12 col-md-6">
                        <label for="setor" class="form-label">
                            <i class="bi bi-diagram-3"></i>
                            Setor
                        </label>

                        <input
                            type="text"
                            name="setor"
                            id="setor"
                            class="form-control @error('setor') is-invalid @enderror"
                            value="{{ old('setor') }}"
                            placeholder="Ex.: Motor, Freios, Suspensão..."
                            maxlength="255"
                            required
                            autofocus
                        >

                        @error('setor')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="nivel" class="form-label">
                            <i class="bi bi-bar-chart-steps"></i>
                            Nível
                        </label>

                        <input
                            type="text"
                            name="nivel"
                            id="nivel"
                            class="form-control @error('nivel') is-invalid @enderror"
                            value="{{ old('nivel') }}"
                            placeholder="Ex.: Básico, Intermediário, Avançado"
                            maxlength="255"
                            required
                        >

                        @error('nivel')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">

                    <a
                        href="{{ route('setor-servicos.index') }}"
                        class="btn btn-secondary"
                    >
                        <i class="bi bi-arrow-left"></i>
                        Voltar
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-check-lg"></i>
                        Cadastrar Setor
                    </button>

                </div>

            </form>

        </div>
    </div>

</div>

@endsection
