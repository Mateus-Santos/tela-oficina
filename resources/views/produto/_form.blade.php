<div class="campos">

    {{-- Código de Barras --}}
    <div class="row mb-3">

        <div class="col-md-4">
            <label class="form-label" for="codigo_barras">
                Código de Barras:
            </label>

            <input
                type="text"
                class="form-control"
                id="codigo_barras"
                name="codigo_barras"
                value="{{ old('codigo_barras', $produto->codigo_barras ?? '') }}"
            >
        </div>

        {{-- Marca --}}
        <div class="col-md-3">
            <label class="form-label" for="marca">
                Marca:*
            </label>

            <input
                type="text"
                class="form-control"
                id="marca"
                name="marca"
                value="{{ old('marca', $produto->marca ?? '') }}"
                required
            >
        </div>

    </div>

    {{-- Linha 1 --}}
    <div class="row mb-3">

        {{-- Nome --}}
        <div class="col-md-3">
            <label class="form-label" for="nome">
                Nome:*
            </label>

            <input
                type="text"
                class="form-control"
                id="nome"
                name="nome"
                maxlength="150"
                value="{{ old('nome', $produto->nome ?? '') }}"
                required
            >
        </div>

        {{-- Preço --}}
        <div class="col-md-3">
            <label class="form-label" for="preco_uni">
                Preço Unitário (R$):*
            </label>

            <input
                type="text"
                inputmode="numeric"
                class="form-control"
                id="preco_uni"
                name="preco_uni"
                value="{{ old(
                    'preco_uni',
                    isset($produto)
                        ? number_format($produto->preco_uni, 2, ',', '.')
                        : ''
                ) }}"
                required
            >
        </div>

        {{-- Quantidade --}}
        <div class="col-md-2">
            <label class="form-label" for="quantidade">
                Quantidade:*
            </label>

            <input
                type="number"
                class="form-control"
                id="quantidade"
                name="quantidade"
                min="0"
                value="{{ old('quantidade', $produto->quantidade ?? 0) }}"
                required
            >
        </div>

    </div>

    {{-- Linha 2 --}}
    <div class="row mb-3">

        {{-- Código do fabricante --}}
        <div class="col-md-3">
            <label class="form-label" for="codigo_fabricante">
                Cod. Fabricante:*
            </label>

            <input
                type="text"
                class="form-control"
                id="codigo_fabricante"
                name="codigo_fabricante"
                value="{{ old('codigo_fabricante', $produto->codigo_fabricante ?? '') }}"
                required
            >
        </div>

        {{-- Imagem --}}
        <div class="col-md-4">
            <label class="form-label" for="img">
                Imagem:
            </label>

            {{-- Imagem atual na edição --}}
            @if(isset($produto) && $produto->img)
                <div class="mb-2">
                    <img
                        id="img-current"
                        src="{{ asset('storage/' . $produto->img) }}"
                        alt="Imagem atual"
                        style="max-width: 100px; border-radius: 10px;"
                    >
                </div>
            @endif

            {{-- Preview da nova imagem --}}
            <div class="mb-2">
                <img
                    id="img-preview"
                    alt="Pré-visualização da imagem"
                    style="display:none; max-width: 120px; border-radius: 10px;"
                >
            </div>

            <input
                type="file"
                class="form-control"
                id="img"
                name="img"
                accept="image/*"
            >
        </div>

    </div>

    {{-- Linha 3 --}}
    <div class="row mb-3">

        {{-- Montadora --}}
        <div class="col-md-4">
            <label class="form-label" for="montadora_select">
                Montadora:*
            </label>

            <select
                id="montadora_select"
                class="form-control"
            >
                <option value="">
                    Escolha uma Montadora
                </option>

                @foreach($montadoras as $montadora)
                    <option value="{{ $montadora->id }}">
                        {{ $montadora->nome }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Veículos --}}
        <div class="col-md-4">
            <label class="form-label" for="veiculo_select">
                Veículo(s):*
            </label>

            <select
                id="veiculo_select"
                class="form-control"
            >
                <option value="">
                    Selecione uma montadora primeiro
                </option>
            </select>

            <div
                class="tags-input mt-2"
                data-name="veiculos[]"
                @if(isset($produto))
                    data-tags='@json(
                        $produto->veiculos->map(fn ($v) => [
                            "id" => $v->id,
                            "label" => $v->nome . " (" . $v->montadora->nome . ")"
                        ])
                    )'
                @endif
            >
                <div class="tags-container"></div>
            </div>
        </div>

    </div>

    {{-- Descrição --}}
    <div class="row mb-3">

        <div class="col-12">
            <label class="form-label" for="descricao">
                Descrição:*
            </label>

            <textarea
                class="form-control"
                id="descricao"
                name="descricao"
                required
            >{{ old('descricao', $produto->descricao ?? '') }}</textarea>
        </div>

    </div>

</div>
