@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <x-list-header
        title="SAÍDA DE ESTOQUE"
        icon="bi-box-arrow-right"
        create-route="estoque.index"
        create-text="Voltar"
        create-icon="bi-arrow-left"
    />

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="mb-4">
                <h5 class="mb-1">
                    <i class="bi bi-box-seam"></i>
                    {{ $produto->nome }}
                </h5>

                <div class="text-muted">
                    Código do fabricante:
                    <strong>{{ $produto->codigo_fabricante }}</strong>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="border rounded p-3">
                        <small class="text-muted d-block">Estoque atual</small>
                        <strong class="fs-4">
                            {{ number_format($produto->quantidade, 3, ',', '.') }}
                        </strong>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border rounded p-3">
                        <small class="text-muted d-block">Estoque mínimo</small>
                        <strong class="fs-4">
                            {{ number_format($produto->estoque_minimo, 3, ',', '.') }}
                        </strong>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border rounded p-3">
                        <small class="text-muted d-block">Preço unitário</small>
                        <strong class="fs-4">
                            R$ {{ number_format($produto->preco_uni, 2, ',', '.') }}
                        </strong>
                    </div>
                </div>
            </div>

            <form
                method="POST"
                action="{{ route('estoque.registrarSaida', $produto) }}"
            >
                @csrf

                <div class="mb-3">
                    <label for="quantidade" class="form-label">
                        Quantidade da saída
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="number"
                        name="quantidade"
                        id="quantidade"
                        class="form-control @error('quantidade') is-invalid @enderror"
                        value="{{ old('quantidade') }}"
                        min="0.001"
                        step="0.001"
                        required
                    >

                    @error('quantidade')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="form-text">
                        Máximo disponível:
                        {{ number_format($produto->quantidade, 3, ',', '.') }}
                    </div>
                </div>

                <div class="mb-4">
                    <label for="observacoes" class="form-label">
                        Observação
                    </label>

                    <textarea
                        name="observacoes"
                        id="observacoes"
                        class="form-control @error('observacoes') is-invalid @enderror"
                        rows="4"
                        maxlength="1000"
                        placeholder="Informe o motivo da saída, se necessário..."
                    >{{ old('observacoes') }}</textarea>

                    @error('observacoes')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <a
                        href="{{ route('estoque.index') }}"
                        class="btn btn-outline-secondary"
                    >
                        <i class="bi bi-x-lg"></i>
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="btn btn-danger"
                    >
                        <i class="bi bi-box-arrow-right"></i>
                        Registrar saída
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
