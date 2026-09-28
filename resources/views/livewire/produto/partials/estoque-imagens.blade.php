{{-- =========================================================
     ESTOQUE + PREÇO + IMAGENS
     ========================================================= --}}
<div class="formulario-secao">

    @php
        $quantidadeImagens = isset($produto)
            ? $produto->imagens->count()
            : 0;
    @endphp

    <div class="formulario-secao__titulo">

        <i class="bi bi-cash-stack"></i>

        <span>
            Estoque, preço e imagens
        </span>

        <span class="badge text-bg-dark ms-1">
            Até 8 imagens
        </span>

    </div>

    <div class="row g-3">

        <div class="col-md-3">

            <label
                class="form-label"
                for="preco_uni"
            >
                Preço Unitário (R$):*
            </label>

            <input
                type="text"
                inputmode="numeric"
                class="form-control @error('preco_uni') is-invalid @enderror"
                id="preco_uni"
                name="preco_uni"
                value="{{ old(
                    'preco_uni',
                    isset($produto)
                        ? number_format(
                            $produto->preco_uni,
                            2,
                            ',',
                            '.'
                        )
                        : ''
                ) }}"
                required
            >

            @error('preco_uni')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror

        </div>

        <div class="col-md-2">

            <label
                class="form-label"
                for="quantidade"
            >
                Quantidade:*
            </label>

            <input
                type="number"
                class="form-control @error('quantidade') is-invalid @enderror"
                id="quantidade"
                name="quantidade"
                min="0"
                value="{{ old(
                    'quantidade',
                    $produto->quantidade ?? 0
                ) }}"
                required
            >

            @error('quantidade')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror

        </div>

        {{-- IMAGENS --}}
        <div
            class="col-md-7"
            wire:ignore
        >

            <label
                class="form-label"
                for="imagens"
            >
                Imagens do produto
            </label>

            <input
                type="file"
                class="form-control @error('imagens') is-invalid @enderror"
                id="imagens"
                name="imagens[]"
                accept="image/*"
                multiple
            >

            <div class="small text-dark fw-semibold mt-2 bg-white p-2 rounded">

                <i class="bi bi-arrows-move text-primary me-1"></i>

                Arraste as imagens para definir a ordem.
                A primeira será usada como capa do produto.
                Máximo de 8 imagens e 2 MB por arquivo.

            </div>

            <div
                class="small text-dark fw-semibold mt-2"
                id="produto-imagens-contador"
            >
                {{ $quantidadeImagens }}/8 imagens cadastradas.
            </div>

            <input
                type="hidden"
                name="ordem_imagens"
                id="ordem_imagens"
                value=""
            >

            <div
                id="produto-imagens-ordenaveis"
                class="d-flex flex-wrap gap-3 mt-3"
                data-max-imagens="8"
            >

                @if(isset($produto))

                    @foreach($produto->imagens as $imagem)

                        <div
                            class="produto-imagem-ordenavel position-relative border rounded bg-white p-2"
                            draggable="true"
                            data-imagem-token="existente:{{ $imagem->id }}"
                            data-produto-imagem="{{ $imagem->id }}"
                            style="cursor:grab;"
                        >

                            <div
                                class="position-absolute top-0 start-0 m-1 badge text-bg-dark"
                                title="Arraste para alterar a posição"
                                style="z-index:2;"
                            >
                                <i class="bi bi-grip-vertical"></i>
                            </div>

                            <img
                                src="{{ asset('storage/' . $imagem->caminho) }}"
                                alt="Imagem do produto"
                                class="img-thumbnail d-block"
                                draggable="false"
                                style="
                                    width:120px;
                                    height:120px;
                                    object-fit:contain;
                                "
                            >

                            <button
                                type="button"
                                class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                data-excluir-imagem-produto
                                data-url="{{ route(
                                    'produtos.imagens.destroy',
                                    [
                                        'produto' => $produto->id,
                                        'imagem' => $imagem->id,
                                    ]
                                ) }}"
                                data-imagem-id="{{ $imagem->id }}"
                                title="Excluir imagem"
                                style="z-index:2;"
                            >
                                <i class="bi bi-trash"></i>
                            </button>

                            <div class="small text-center fw-bold text-dark mt-1 produto-imagem-posicao">
                                {{ $loop->first ? 'Capa' : '#' . $loop->iteration }}
                            </div>

                        </div>

                    @endforeach

                @endif

            </div>

        </div>

    </div>

    @error('imagens')
        <div class="text-danger small fw-semibold mt-2">
            {{ $message }}
        </div>
    @enderror

    @error('imagens.*')
        <div class="text-danger small fw-semibold mt-2">
            {{ $message }}
        </div>
    @enderror

    @error('ordem_imagens')
        <div class="text-danger small fw-semibold mt-2">
            {{ $message }}
        </div>
    @enderror

</div>
