<div>

    <label
        for="busca_veiculo"
        class="form-label"
    >
        Veículo:*
    </label>

    @if($veiculoSelecionado)

        <div class="mb-3">

            <div
                class="d-flex align-items-center justify-content-between border rounded p-3 bg-light"
            >

                <div>

                    <div class="fw-bold">

                        <i class="bi bi-car-front me-1"></i>

                        {{ $veiculoSelecionado['nome'] }}

                    </div>

                    @if(!empty($veiculoSelecionado['montadora']))

                        <div class="small text-body-secondary mt-1">

                            <i class="bi bi-building me-1"></i>

                            {{ $veiculoSelecionado['montadora'] }}

                        </div>

                    @endif

                </div>

                <button
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    wire:click="removerVeiculo"
                    title="Remover veículo selecionado"
                >
                    <i class="bi bi-x-lg"></i>
                </button>

            </div>

            <input
                type="hidden"
                name="veiculo_id"
                value="{{ $veiculoSelecionadoId }}"
            >

        </div>

    @else

        <div class="position-relative">

            <div class="input-group">

                <span class="input-group-text">
                    <i class="bi bi-search"></i>
                </span>

                <input
                    type="text"
                    id="busca_veiculo"
                    class="form-control"
                    placeholder="Digite o veículo ou montadora..."
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
                            <i class="bi bi-car-front me-1"></i>
                            Veículos encontrados
                        </span>

                        <span class="badge text-bg-secondary">
                            {{ count($resultadosVeiculos) }}
                        </span>

                    </div>

                    <div class="busca-selecao__resultados">

                        @foreach(
                            $resultadosVeiculos as $veiculo
                        )

                            <button
                                type="button"
                                class="list-group-item list-group-item-action busca-selecao__item d-flex justify-content-between align-items-center gap-3"
                                wire:key="resultado-veiculo-{{ $veiculo['id'] }}"
                                wire:click="selecionarVeiculo({{ $veiculo['id'] }})"
                            >

                                <span class="text-start">

                                    <span class="d-block fw-bold">

                                        <i class="bi bi-car-front me-1"></i>

                                        {{ $veiculo['nome'] }}

                                    </span>

                                    @if(!empty($veiculo['montadora']))

                                        <span class="small text-body-secondary">

                                            <i class="bi bi-building me-1"></i>

                                            {{ $veiculo['montadora'] }}

                                        </span>

                                    @endif

                                </span>

                                <i class="bi bi-plus-lg"></i>

                            </button>

                        @endforeach

                    </div>

                    <div class="busca-selecao__rodape">

                        <i class="bi bi-mouse me-1"></i>

                        Role para visualizar os resultados.

                    </div>

                </div>

            @elseif(
                mb_strlen(trim($buscaVeiculo)) >= 2
                && count($resultadosVeiculos) === 0
            )

                <div class="small fw-semibold mt-2">
                    Nenhum veículo encontrado.
                </div>

            @endif

        </div>

    @endif

    @error('veiculo_id')

        <div class="text-danger small fw-semibold mt-1">
            {{ $message }}
        </div>

    @enderror

</div>
