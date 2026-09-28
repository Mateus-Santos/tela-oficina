{{-- =========================================================
     DADOS PRINCIPAIS + IDENTIFICAÇÃO
     ========================================================= --}}
<div class="formulario-secao">

    <div class="formulario-secao__titulo">
        <i class="bi bi-box-seam"></i>
        <span>Dados principais e identificação</span>
    </div>

    <div class="row mb-3">

        {{-- NOME --}}
        <div class="col-md-4">

            <label
                class="form-label"
                for="nome"
            >
                Nome:*
            </label>

            <input
                type="text"
                class="form-control @error('nome') is-invalid @enderror"
                id="nome"
                name="nome"
                maxlength="150"
                value="{{ old('nome', $produto->nome ?? '') }}"
                required
            >

            @error('nome')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror

        </div>

        {{-- MARCA --}}
        <div class="col-md-4">

            <label
                class="form-label"
                for="marca_id"
            >
                Marca:*
            </label>

            <div class="d-flex gap-2 align-items-start">

                {{-- LOGO DA MARCA --}}
                <div
                    class="border rounded bg-white d-flex align-items-center justify-content-center flex-shrink-0"
                    style="
                        width:48px;
                        height:48px;
                        overflow:hidden;
                    "
                >

                    @if(isset($produto) && $produto->marcaRelacionada?->logo_path)

                        <img
                            src="{{ asset('storage/' . $produto->marcaRelacionada->logo_path) }}"
                            alt="Logo {{ $produto->marcaRelacionada->nome }}"
                            style="
                                width:100%;
                                height:100%;
                                object-fit:contain;
                                padding:4px;
                            "
                        >

                    @elseif(isset($produto) && $produto->marcaRelacionada?->logo_url)

                        <img
                            src="{{ $produto->marcaRelacionada->logo_url }}"
                            alt="Logo {{ $produto->marcaRelacionada->nome }}"
                            style="
                                width:100%;
                                height:100%;
                                object-fit:contain;
                                padding:4px;
                            "
                        >

                    @else

                        <i class="bi bi-tag fs-5 text-secondary"></i>

                    @endif

                </div>

                <div class="flex-grow-1">

                    <div class="input-group">

                        <select
                            class="form-select @error('marca_id') is-invalid @enderror"
                            id="marca_id"
                            name="marca_id"
                            wire:model="marcaSelecionada"
                            required
                        >
                            <option value="">
                                Selecione uma marca
                            </option>

                            @foreach($marcas as $marca)

                                <option value="{{ $marca->id }}">
                                    {{ $marca->nome }}

                                    @if(!$marca->ativo)
                                        - Inativa
                                    @endif
                                </option>

                            @endforeach

                        </select>

                        <button
                            type="button"
                            class="btn btn-outline-primary fw-semibold"
                            wire:click="abrirCadastroMarca"
                            title="Cadastrar nova marca"
                        >
                            <i class="bi bi-plus-lg"></i>
                            Nova
                        </button>

                    </div>

                    @error('marca_id')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>

            @if($mensagemMarca)

                <div class="alert alert-success py-2 mt-2 mb-0">
                    <i class="bi bi-check-circle me-1"></i>
                    {{ $mensagemMarca }}
                </div>

            @endif

            {{-- CADASTRO RÁPIDO DE MARCA --}}
            @if($mostrarCadastroMarca)

                <div class="card border-primary mt-3">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center mb-3">

                            <div class="fw-semibold">
                                <i class="bi bi-tag me-1"></i>
                                Nova marca
                            </div>

                            <button
                                type="button"
                                class="btn-close"
                                wire:click="fecharCadastroMarca"
                                aria-label="Fechar"
                            ></button>

                        </div>

                        <div class="mb-3">

                            <label
                                for="nova_marca_nome"
                                class="form-label"
                            >
                                Nome da marca:*
                            </label>

                            <input
                                type="text"
                                id="nova_marca_nome"
                                class="form-control @error('novaMarcaNome') is-invalid @enderror"
                                wire:model="novaMarcaNome"
                                wire:keydown.enter.prevent="criarMarcaRapida"
                                maxlength="150"
                                autocomplete="off"
                                placeholder="Ex.: WEGA"
                            >

                            @error('novaMarcaNome')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                        <div class="d-flex justify-content-end gap-2">

                            <button
                                type="button"
                                class="btn btn-secondary btn-sm"
                                wire:click="fecharCadastroMarca"
                                wire:loading.attr="disabled"
                                wire:target="criarMarcaRapida"
                            >
                                <i class="bi bi-x-lg me-1"></i>
                                Cancelar
                            </button>

                            <button
                                type="button"
                                class="btn btn-success btn-sm"
                                wire:click="criarMarcaRapida"
                                wire:loading.attr="disabled"
                                wire:target="criarMarcaRapida"
                            >
                                <span
                                    wire:loading.remove
                                    wire:target="criarMarcaRapida"
                                >
                                    <i class="bi bi-check-lg me-1"></i>
                                    Cadastrar
                                </span>

                                <span
                                    wire:loading
                                    wire:target="criarMarcaRapida"
                                >
                                    Cadastrando...
                                </span>
                            </button>

                        </div>

                    </div>

                </div>

            @endif

        </div>

        {{-- STATUS --}}
        <div class="col-md-4">

            <label
                class="form-label d-block"
                for="status"
            >
                Status:*
            </label>

            <input
                type="hidden"
                name="status"
                value="0"
            >

            <div class="border border-secondary rounded bg-light px-3 py-2 d-flex align-items-center justify-content-between">

                <div class="d-flex align-items-center gap-2">

                    <i class="bi bi-check-circle-fill text-success fs-5"></i>

                    <div>

                        <div class="fw-bold text-dark">
                            Produto ativo
                        </div>

                        <div class="small text-dark fw-medium">
                            Ative para permitir o uso normal deste produto no sistema.
                        </div>

                    </div>

                </div>

                <div class="form-check form-switch m-0">

                    <input
                        type="checkbox"
                        class="form-check-input @error('status') is-invalid @enderror"
                        id="status"
                        name="status"
                        value="1"
                        @checked(
                            (string) old(
                                'status',
                                isset($produto)
                                    ? (int) $produto->status
                                    : 1
                            ) === '1'
                        )
                    >

                </div>

            </div>

            @error('status')
                <div class="text-danger small fw-semibold mt-1">
                    {{ $message }}
                </div>
            @enderror

        </div>

    </div>

    <div class="row">

        {{-- CÓDIGO DO FABRICANTE --}}
        <div class="col-md-4">

            <label
                class="form-label"
                for="codigo_fabricante"
            >
                Código do Fabricante:*
            </label>

            <input
                type="text"
                class="form-control @if($codigoFabricanteDuplicado) is-invalid @endif"
                id="codigo_fabricante"
                name="codigo_fabricante"
                wire:model.live.debounce.500ms="codigoFabricante"
                required
            >

            @if($codigoFabricanteDuplicado)

                <div
                    class="invalid-feedback d-block"
                    style="background-color:#fff;padding:4px 8px;border-radius:4px;"
                >
                    Este código de fabricante já está cadastrado.
                </div>

            @endif

        </div>

        {{-- CÓDIGO DE BARRAS --}}
        <div class="col-md-4">

            <label
                class="form-label"
                for="codigo_barras"
            >
                Código de Barras:
            </label>

            <input
                type="text"
                class="form-control @if($codigoBarrasDuplicado) is-invalid @endif"
                id="codigo_barras"
                name="codigo_barras"
                wire:model.live.debounce.500ms="codigoBarras"
            >

            @if($codigoBarrasDuplicado)

                <div
                    class="invalid-feedback d-block"
                    style="background-color:#fff;padding:4px 8px;border-radius:4px;"
                >
                    Este código de barras já está cadastrado.
                </div>

            @endif

        </div>

        {{-- INFORMAÇÕES ADICIONAIS --}}
        <div class="col-md-4 d-flex align-items-end">

            <button
                type="button"
                class="btn btn-dark w-100 fw-bold"
                id="btn-dados-adicionais-produto"
                title="Abrir informações fiscais, comerciais e de estoque"
            >
                <i class="bi bi-sliders me-1"></i>
                Informações adicionais
            </button>

        </div>

    </div>

</div>
