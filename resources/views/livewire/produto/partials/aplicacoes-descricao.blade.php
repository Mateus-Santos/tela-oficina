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
                    <i class="bi bi-arrow-repeat me-1"></i>
                    Buscando veículos...
                </div>

                @if(
                    mb_strlen(trim($buscaVeiculo)) >= 2
                    && count($resultadosVeiculos) > 0
                )

                    <div class="busca-selecao mt-1 shadow-sm">

                        <div class="busca-selecao__cabecalho">

                            <span>
                                <i class="bi bi-search me-1"></i>
                                Resultados encontrados
                            </span>

                            <span class="badge text-bg-secondary">
                                {{ count($resultadosVeiculos) }}
                            </span>

                        </div>

                        <div class="busca-selecao__resultados">

                            @foreach($resultadosVeiculos as $veiculo)

                                <button
                                    type="button"
                                    class="list-group-item list-group-item-action busca-selecao__item d-flex justify-content-between align-items-center gap-3"
                                    wire:key="resultado-veiculo-{{ $veiculo['id'] }}"
                                    wire:click="adicionarVeiculo({{ $veiculo['id'] }})"
                                    @disabled($veiculo['selecionado'])
                                >

                                    <span class="d-flex align-items-center text-start">

                                        <i class="bi bi-car-front me-2 flex-shrink-0"></i>

                                        <span>

                                            <strong>
                                                {{ $veiculo['nome'] }}
                                            </strong>

                                            @if(!empty($veiculo['montadora']))

                                                <span class="text-dark fw-medium">
                                                    — {{ $veiculo['montadora'] }}
                                                </span>

                                            @endif

                                        </span>

                                    </span>

                                    @if($veiculo['selecionado'])

                                        <span class="badge text-bg-success flex-shrink-0">
                                            <i class="bi bi-check-lg me-1"></i>
                                            Selecionado
                                        </span>

                                    @else

                                        <i class="bi bi-plus-lg flex-shrink-0"></i>

                                    @endif

                                </button>

                            @endforeach

                        </div>

                        <div class="busca-selecao__rodape">

                            <i class="bi bi-mouse me-1"></i>

                            Role a lista para visualizar todos os resultados.

                        </div>

                    </div>

                @elseif(
                    mb_strlen(trim($buscaVeiculo)) >= 2
                    && count($resultadosVeiculos) === 0
                )

                    <div class="small text-dark fw-semibold mt-2">
                        <i class="bi bi-search me-1"></i>
                        Nenhum veículo encontrado.
                    </div>

                @elseif(trim($buscaVeiculo) !== '')

                    <div class="small text-dark fw-semibold mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Digite pelo menos 2 caracteres para pesquisar.
                    </div>

                @endif

            </div>

            {{-- VEÍCULOS SELECIONADOS --}}
            @if(count($veiculosSelecionados) > 0)

                <div class="mt-3">

                    <div class="d-flex align-items-center justify-content-between mb-2">

                        <div class="small fw-bold text-dark">
                            Veículos selecionados:
                        </div>

                        <span class="badge text-bg-primary">
                            {{ count($veiculosSelecionados) }}
                        </span>

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
