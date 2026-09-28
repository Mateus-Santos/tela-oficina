{{-- =========================================================
     APLICAÇÕES + DESCRIÇÃO
     ========================================================= --}}
<div class="formulario-secao">

    <div class="formulario-secao__titulo">
        <i class="bi bi-car-front"></i>
        <span>Aplicações e descrição</span>
    </div>

    <div class="border-start border-4 border-primary bg-white rounded-end px-3 py-2 mb-3 text-dark fw-medium">

        <i class="bi bi-info-circle-fill me-1 text-primary"></i>

        Busque e selecione todos os veículos compatíveis com o produto.

    </div>

    {{-- IMPORTAÇÃO --}}
    <div class="row mb-3">

        <div class="col-12">

            <button
                type="button"
                id="btn-importar-aplicacoes"
                class="btn btn-primary fw-semibold"
            >
                <i class="bi bi-clipboard"></i>
                Importar aplicações da área de transferência
            </button>

            <div
                wire:loading
                wire:target="importarAplicacoes"
                class="small text-dark fw-semibold mt-2"
            >
                <i class="bi bi-hourglass-split me-1"></i>
                Analisando aplicações...
            </div>

            @if($mensagemImportacao)

                <div class="alert alert-info mt-3 mb-0">
                    {{ $mensagemImportacao }}
                </div>

            @endif

        </div>

    </div>

    {{-- PRÉVIA DA IMPORTAÇÃO --}}
    @if($mostrarPreviaImportacao)

        <div class="alert alert-warning">

            <div class="fw-bold mb-2">
                <i class="bi bi-eye"></i>
                Prévia da importação
            </div>

            <div class="mb-3">

                Foram encontradas

                <strong>
                    {{ $previsaoImportacao['total_aplicacoes'] ?? 0 }}
                </strong>

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
                                {{ $veiculo['montadora'] }}
                                -
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
                                {{ $veiculo['montadora'] }}
                                -
                                {{ $veiculo['veiculo'] }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif

            <div class="mt-3 text-dark fw-medium">

                <strong>
                    <i class="bi bi-info-circle"></i>
                    Observação:
                </strong>

                os dados técnicos da tabela, como ano, motor,
                válvulas, cilindrada e combustível, foram
                preservados durante a importação, mas ainda não
                são gravados na estrutura atual.

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

    {{-- BUSCA DE VEÍCULOS --}}
    <div class="row mb-3">

        <div class="col-md-9">

            <label
                class="form-label"
                for="busca_veiculo"
            >
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
                    class="small text-dark fw-semibold mt-1"
                >
                    Buscando veículos...
                </div>

                @if(
                    mb_strlen(trim($buscaVeiculo)) >= 2
                    && count($resultadosVeiculos) > 0
                )

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

                                    <strong>
                                        {{ $veiculo['nome'] }}
                                    </strong>

                                    @if(!empty($veiculo['montadora']))

                                        <span class="text-dark fw-medium">
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

                @elseif(
                    mb_strlen(trim($buscaVeiculo)) >= 2
                    && count($resultadosVeiculos) === 0
                )

                    <div class="small text-dark fw-semibold mt-2">
                        Nenhum veículo encontrado.
                    </div>

                @elseif(trim($buscaVeiculo) !== '')

                    <div class="small text-dark fw-semibold mt-2">
                        Digite pelo menos 2 caracteres para pesquisar.
                    </div>

                @endif

            </div>

            @if(count($veiculosSelecionados) > 0)

                <div class="mt-3">

                    <div class="small fw-bold text-dark mb-2">
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

                <div class="small text-dark fw-semibold mt-2">
                    Nenhum veículo selecionado.
                </div>

            @endif

            @error('veiculos')
                <div class="text-danger small fw-semibold mt-1">
                    {{ $message }}
                </div>
            @enderror

            @error('veiculos.*')
                <div class="text-danger small fw-semibold mt-1">
                    {{ $message }}
                </div>
            @enderror

        </div>

    </div>

    {{-- DESCRIÇÃO --}}
    <div class="row">

        <div class="col-12">

            <label
                class="form-label"
                for="descricao"
            >
                Descrição:*
            </label>

            <textarea
                class="form-control @error('descricao') is-invalid @enderror"
                id="descricao"
                name="descricao"
                required
            >{{ old('descricao', $produto->descricao ?? '') }}</textarea>

            @error('descricao')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror

        </div>

    </div>

</div>
