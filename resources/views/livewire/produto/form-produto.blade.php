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

            <div class="col-md-5">
                <label class="form-label" for="nome">Nome:*</label>

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

            {{-- MARCA --}}
            <div class="col-md-5">
                <label class="form-label" for="marca_id">Marca:*</label>

                <div class="input-group">
                    <select
                        class="form-select @error('marca_id') is-invalid @enderror"
                        id="marca_id"
                        name="marca_id"
                        wire:model="marcaSelecionada"
                        required
                    >
                        <option value="">Selecione uma marca</option>

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
                        class="btn btn-outline-primary"
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

        </div>

        <div class="row">

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
                        style="background-color:#fff;padding:4px 8px;border-radius:4px;"
                    >
                        Este código de fabricante já está cadastrado.
                    </div>
                @endif
            </div>

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
                        style="background-color:#fff;padding:4px 8px;border-radius:4px;"
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
            Busque e selecione todos os veículos compatíveis com o produto.
        </div>

        {{-- Importação --}}
        <div class="row mb-3">
            <div class="col-12">

                <button
                    type="button"
                    id="btn-importar-aplicacoes"
                    class="btn btn-primary"
                >
                    <i class="bi bi-clipboard"></i>
                    Importar aplicações da área de transferência
                </button>

                <div
                    wire:loading
                    wire:target="importarAplicacoes"
                    class="small text-muted mt-2"
                >
                    Analisando aplicações...
                </div>

                @if($mensagemImportacao)
                    <div class="alert alert-info mt-3 mb-0">
                        {{ $mensagemImportacao }}
                    </div>
                @endif

            </div>
        </div>

        {{-- Prévia da importação --}}
        @if($mostrarPreviaImportacao)
            <div class="alert alert-warning">

                <div class="fw-bold mb-2">
                    <i class="bi bi-eye"></i>
                    Prévia da importação
                </div>

                <div class="mb-3">
                    Foram encontradas
                    <strong>{{ $previsaoImportacao['total_aplicacoes'] ?? 0 }}</strong>
                    aplicações na tabela.
                </div>

                @if(!empty($previsaoImportacao['montadoras']))
                    <div class="mb-3">
                        <strong>Montadoras:</strong>

                        <ul class="mb-0">
                            @foreach($previsaoImportacao['montadoras'] as $montadora)
                                <li>
                                    {{ $montadora['nome'] }}

                                    @if($montadora['existente'] ?? false)
                                        <span class="badge text-bg-success">
                                            já cadastrada
                                        </span>
                                    @else
                                        <span class="badge text-bg-warning">
                                            será criada
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!empty($previsaoImportacao['veiculos_existentes']))
                    <div class="mb-3">
                        <strong>Veículos já cadastrados:</strong>

                        <ul class="mb-0">
                            @foreach($previsaoImportacao['veiculos_existentes'] as $veiculo)
                                <li>
                                    {{ $veiculo['montadora'] }} -
                                    {{ $veiculo['veiculo'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!empty($previsaoImportacao['veiculos_novos']))
                    <div class="mb-3">
                        <strong>Veículos novos:</strong>

                        <ul class="mb-0">
                            @foreach($previsaoImportacao['veiculos_novos'] as $veiculo)
                                <li>
                                    {{ $veiculo['montadora'] }} -
                                    {{ $veiculo['veiculo'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-3">
                    <strong>
                        <i class="bi bi-info-circle"></i>
                        Observação:
                    </strong>

                    os dados técnicos da tabela, como ano, motor, válvulas,
                    cilindrada e combustível, foram preservados durante a
                    importação, mas ainda não são gravados na estrutura atual.
                </div>

                <div class="d-flex gap-2 mt-3">

                    <button
                        type="button"
                        class="btn btn-success"
                        wire:click="confirmarImportacao"
                        wire:loading.attr="disabled"
                        wire:target="confirmarImportacao"
                    >
                        <span
                            wire:loading.remove
                            wire:target="confirmarImportacao"
                        >
                            <i class="bi bi-check-lg"></i>
                            Confirmar importação
                        </span>

                        <span
                            wire:loading
                            wire:target="confirmarImportacao"
                        >
                            Importando...
                        </span>
                    </button>

                    <button
                        type="button"
                        class="btn btn-secondary"
                        wire:click="cancelarImportacao"
                        wire:loading.attr="disabled"
                        wire:target="cancelarImportacao"
                    >
                        <i class="bi bi-x-lg"></i>
                        Cancelar
                    </button>

                </div>
            </div>
        @endif

        {{-- Busca única de veículos --}}
        <div class="row mb-3">
            <div class="col-md-9">

                <label class="form-label" for="busca_veiculo">
                    Veículo(s):*
                </label>

                <div class="position-relative">

                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>

                        <input
                            type="text"
                            id="busca_veiculo"
                            class="form-control"
                            placeholder="Digite veículo ou montadora..."
                            autocomplete="off"
                            wire:model.live.debounce.300ms="buscaVeiculo"
                        >
                    </div>

                    <div
                        wire:loading
                        wire:target="buscaVeiculo"
                        class="small text-muted mt-1"
                    >
                        Buscando veículos...
                    </div>

                    @if(mb_strlen(trim($buscaVeiculo)) >= 2 && count($resultadosVeiculos) > 0)
                        <div class="list-group mt-1 shadow-sm">

                            @foreach($resultadosVeiculos as $veiculo)
                                <button
                                    type="button"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                    wire:key="resultado-veiculo-{{ $veiculo['id'] }}"
                                    wire:click="adicionarVeiculo({{ $veiculo['id'] }})"
                                    @disabled($veiculo['selecionado'])
                                >
                                    <span>
                                        <i class="bi bi-car-front me-2"></i>

                                        <strong>{{ $veiculo['nome'] }}</strong>

                                        @if(!empty($veiculo['montadora']))
                                            <span class="text-muted">
                                                — {{ $veiculo['montadora'] }}
                                            </span>
                                        @endif
                                    </span>

                                    @if($veiculo['selecionado'])
                                        <span class="badge text-bg-success">
                                            Selecionado
                                        </span>
                                    @else
                                        <i class="bi bi-plus-lg"></i>
                                    @endif
                                </button>
                            @endforeach

                        </div>
                    @elseif(mb_strlen(trim($buscaVeiculo)) >= 2 && count($resultadosVeiculos) === 0)
                        <div class="small text-muted mt-2">
                            Nenhum veículo encontrado.
                        </div>
                    @elseif(trim($buscaVeiculo) !== '')
                        <div class="small text-muted mt-2">
                            Digite pelo menos 2 caracteres para pesquisar.
                        </div>
                    @endif

                </div>

                @if(count($veiculosSelecionados) > 0)
                    <div class="mt-3">

                        <div class="small text-muted mb-2">
                            Veículos selecionados:
                        </div>

                        <div class="tags-container">

                            @foreach($veiculosSelecionados as $veiculo)
                                <div
                                    class="tag"
                                    wire:key="veiculo-selecionado-{{ $veiculo['id'] }}"
                                >
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
                @else
                    <div class="small text-muted mt-2">
                        Nenhum veículo selecionado.
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
                            style="max-width:100px;border-radius:10px;"
                        >
                    </div>
                @endif

                <div class="mb-2">
                    <img
                        id="img-preview"
                        alt="Pré-visualização da imagem"
                        style="display:none;max-width:120px;border-radius:10px;"
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
