@extends('layouts.layout')

@section('content')
<main class="container mt-5 py-5" style="min-height: 70vh;">

    @php
        $editando = $endereco !== null;
        $estados = [
            'AC','AL','AP','AM','BA','CE','DF',
            'ES','GO','MA','MT','MS','PA','PB',
            'PR','PE','PI','RJ','RN','RS','RO',
            'RR','SC','SP','SE','TO'
        ];
    @endphp

    <a
        href="{{ route('cliente.enderecos.index') }}"
        class="btn btn-outline-secondary mb-4"
    >
        <i class="bi bi-arrow-left me-1"></i>
        Meus endereços
    </a>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">

            <h1 class="h3 fw-bold mb-4">
                {{ $editando ? 'Editar endereço' : 'Cadastrar endereço' }}
            </h1>

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    Confira os campos informados.
                </div>
            @endif

            <form
                method="POST"
                action="{{ $editando
                    ? route('cliente.enderecos.update', $endereco->id)
                    : route('cliente.enderecos.store') }}"
            >
                @csrf

                @if ($editando)
                    @method('PUT')
                @endif

                <div class="row g-3">

                    <div class="col-12 col-md-4">
                        <label for="cep" class="form-label">CEP *</label>
                        <input type="text" id="cep" name="cep"
                            class="form-control @error('cep') is-invalid @enderror"
                            maxlength="9" required
                            value="{{ old('cep', $endereco?->cep) }}"
                            placeholder="44000-000">
                        @error('cep')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="estado" class="form-label">Estado *</label>
                        <select id="estado" name="estado"
                            class="form-select @error('estado') is-invalid @enderror"
                            required>
                            <option value="">Selecione</option>
                            @foreach ($estados as $uf)
                                <option value="{{ $uf }}"
                                    @selected(old('estado', $endereco?->estado) === $uf)>
                                    {{ $uf }}
                                </option>
                            @endforeach
                        </select>
                        @error('estado')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-5">
                        <label for="cidade" class="form-label">Cidade *</label>
                        <input type="text" id="cidade" name="cidade"
                            class="form-control @error('cidade') is-invalid @enderror"
                            maxlength="50" required
                            value="{{ old('cidade', $endereco?->cidade) }}">
                        @error('cidade')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="bairro" class="form-label">Bairro *</label>
                        <input type="text" id="bairro" name="bairro"
                            class="form-control @error('bairro') is-invalid @enderror"
                            maxlength="50" required
                            value="{{ old('bairro', $endereco?->bairro) }}">
                        @error('bairro')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="rua" class="form-label">Rua *</label>
                        <input type="text" id="rua" name="rua"
                            class="form-control @error('rua') is-invalid @enderror"
                            maxlength="100" required
                            value="{{ old('rua', $endereco?->rua) }}">
                        @error('rua')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="numero" class="form-label">Número *</label>
                        <input type="text" id="numero" name="numero"
                            class="form-control @error('numero') is-invalid @enderror"
                            maxlength="20" required
                            value="{{ old('numero', $endereco?->numero) }}">
                        @error('numero')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-8">
                        <label for="ponto_referencia" class="form-label">
                            Ponto de referência (opcional)
                        </label>
                        <input type="text" id="ponto_referencia"
                            name="ponto_referencia"
                            class="form-control @error('ponto_referencia') is-invalid @enderror"
                            maxlength="200"
                            value="{{ old('ponto_referencia', $endereco?->ponto_referencia) }}">
                        @error('ponto_referencia')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-lg me-1"></i>
                        {{ $editando ? 'Salvar alterações' : 'Cadastrar endereço' }}
                    </button>
                </div>
            </form>

        </div>
    </div>

</main>
@endsection
