<div class="campos">

    {{-- =========================================================
         DADOS PRINCIPAIS + IDENTIFICAÇÃO
         ========================================================= --}}
    <div class="formulario-secao">
        <div class="formulario-secao__titulo">
            <i class="bi bi-box-seam"></i>
            <span>Dados principais e identificação</span>
        </div>

        <div class="row mb-3">
            {{-- Nome --}}
            <div class="col-md-5">
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

            {{-- Marca --}}
            <div class="col-md-4">
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

        <div class="row">
            {{-- Código do fabricante --}}
            <div class="col-md-4">
                <label class="form-label" for="codigo_fabricante">
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
                        style="background-color: #fff; padding: 4px 8px; border-radius: 4px;"
                    >
                        Este código de fabricante já está cadastrado.
                    </div>
                @endif
            </div>

            {{-- Código de barras --}}
            <div class="col-md-4">
                <label class="form-label" for="codigo_barras">
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
                        style="background-color: #fff; padding: 4px 8px; border-radius: 4px;"
                    >
                        Este código de barras já está cadastrado.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- =========================================================
         APLICAÇÕES + DESCRIÇÃO
         ========================================================= --}}
    <div class="formulario-secao">
        <div class="formulario-secao__titulo">
            <i class="bi bi-car-front"></i>
            <span>Aplicações e descrição</span>
        </div>

        <div class="formulario-secao__descricao">
            Selecione os veículos compatíveis e informe a descrição do produto.
        </div>

        <div class="row mb-3">
            {{-- Montadora --}}
            <div class="col-md-4">
                <label class="form-label" for="montadora_select">
                    Montadora:
                </label>

                <select
                    id="montadora_select"
                    class="form-control"
                    wire:model.live="montadoraSelecionada"
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

                <div
                    wire:loading
                    wire:target="montadoraSelecionada"
                    class="small text-muted mt-1"
                >
                    Carregando veículos...
                </div>
            </div>

            {{-- Veículos --}}
            <div class="col-md-5">
                <label class="form-label" for="veiculo_select">
                    Veículo(s):*
                </label>

                <select
                    id="veiculo_select"
                    class="form-control"
                    wire:change="adicionarVeiculo($event.target.value)"
                    @disabled(empty($montadoraSelecionada) || empty($veiculos))
                >
                    <option value="">
                        @if(empty($montadoraSelecionada))
                            Selecione uma montadora primeiro
                        @elseif(empty($veiculos))
                            Nenhum veículo encontrado
                        @else
                            Selecione um veículo
                        @endif
                    </option>

                    @foreach($veiculos as $veiculo)
                        <option value="{{ $veiculo['id'] }}">
                            {{ $veiculo['nome'] }}
                        </option>
                    @endforeach
                </select>

                @if(count($veiculosSelecionados) > 0)
                    <div class="mt-2">
                        <div class="tags-container">
                            @foreach($veiculosSelecionados as $veiculo)
                                <div class="tag">
                                    {{ $veiculo['nome'] }}

                                    @if(!empty($veiculo['montadora']))
                                        ({{ $veiculo['montadora'] }})
                                    @endif

                                    <span
                                        class="remove-tag"
                                        wire:click="removerVeiculo({{ $veiculo['id'] }})"
                                        role="button"
                                        title="Remover veículo"
                                    >
                                        &times;
                                    </span>
                                </div>

                                <input
                                    type="hidden"
                                    name="veiculos[]"
                                    value="{{ $veiculo['id'] }}"
                                >
                            @endforeach
                        </div>
                    </div>
                @endif

                @error('veiculos')
                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>
                @enderror

                @error('veiculos.*')
                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>
        </div>

        {{-- Descrição --}}
        <div class="row">
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

    {{-- =========================================================
         ESTOQUE E PREÇO + IMAGEM
         ========================================================= --}}
    <div class="formulario-secao">
        <div class="formulario-secao__titulo">
            <i class="bi bi-cash-stack"></i>
            <span>Estoque, preço e imagem</span>
            <small class="text-muted">(imagem opcional)</small>
        </div>

        <div class="row">
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

            {{-- Imagem --}}
            <div class="col-md-5" wire:ignore>
                <label class="form-label" for="img">
                    Imagem:
                </label>

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
    </div>

</div>
