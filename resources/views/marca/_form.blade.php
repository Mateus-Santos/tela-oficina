<div class="row g-3">
    <div class="col-md-6">
        <label for="nome" class="form-label">
            Nome da Marca:*
        </label>

        <input
            type="text"
            id="nome"
            name="nome"
            class="form-control @error('nome') is-invalid @enderror"
            value="{{ old('nome', $marca->nome ?? '') }}"
            maxlength="150"
            required
            autofocus
        >

        @error('nome')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="logo_url" class="form-label">
            URL da Logo:
        </label>

        <input
            type="url"
            id="logo_url"
            name="logo_url"
            class="form-control @error('logo_url') is-invalid @enderror"
            value="{{ old('logo_url', $marca->logo_url ?? '') }}"
            placeholder="https://exemplo.com/logo.png"
        >

        @error('logo_url')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror

        <div class="form-text">
            Opcional. Pode ser usada quando a imagem estiver hospedada externamente.
        </div>
    </div>

    <div class="col-md-6">
        <label for="logo" class="form-label">
            Arquivo da Logo:
        </label>

        @if(isset($marca) && $marca->logo_path)
            <div class="mb-2">
                <img
                    src="{{ asset('storage/' . $marca->logo_path) }}"
                    alt="Logo {{ $marca->nome }}"
                    class="img-thumbnail"
                    style="max-width: 140px; max-height: 100px;"
                >
            </div>
        @endif

        <input
            type="file"
            id="logo"
            name="logo"
            class="form-control @error('logo') is-invalid @enderror"
            accept="image/*"
        >

        @error('logo')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror

        <div class="form-text">
            Opcional. Máximo de 2 MB.
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label d-block">
            Status:
        </label>

        <div class="form-check form-switch mt-2">
            <input
                type="hidden"
                name="ativo"
                value="0"
            >

            <input
                type="checkbox"
                class="form-check-input"
                id="ativo"
                name="ativo"
                value="1"
                @checked((bool) old('ativo', $marca->ativo ?? true))
            >

            <label class="form-check-label" for="ativo">
                Marca ativa
            </label>
        </div>

        @error('ativo')
            <div class="text-danger small mt-1">
                {{ $message }}
            </div>
        @enderror
    </div>

    @if(isset($marca) && $marca->logo_path)
        <div class="col-12">
            <div class="form-check">
                <input
                    type="hidden"
                    name="remover_logo"
                    value="0"
                >

                <input
                    type="checkbox"
                    class="form-check-input"
                    id="remover_logo"
                    name="remover_logo"
                    value="1"
                    @checked((bool) old('remover_logo'))
                >

                <label class="form-check-label" for="remover_logo">
                    Remover logo atual
                </label>
            </div>
        </div>
    @endif
</div>
