<div class="row g-3">

    {{-- CLIENTE --}}
    <div class="col-12 col-lg-6">

        <label
            for="busca_cliente_nota"
            class="form-label"
        >
            <i class="bi bi-person"></i>
            Cliente
        </label>

        @if($clienteSelecionado)

            <div
                class="d-flex justify-content-between align-items-center border rounded bg-light p-3"
            >

                <div>

                    <div class="fw-bold">
                        <i class="bi bi-person-check me-1"></i>

                        {{ $clienteSelecionado['nome'] }}
                    </div>

                    <div class="small text-body-secondary mt-1">

                        @if(!empty($clienteSelecionado['cpf']))

                            <span class="me-3">
                                CPF:
                                {{ $clienteSelecionado['cpf'] }}
                            </span>

                        @endif

                        @if(!empty($clienteSelecionado['telefone']))

                            <span>
                                <i class="bi bi-telephone me-1"></i>

                                {{ $clienteSelecionado['telefone'] }}
                            </span>

                        @endif

                    </div>

                </div>

                <button
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    wire:click="removerCliente"
                    title="Remover cliente"
                >
                    <i class="bi bi-x-lg"></i>
                </button>

            </div>

            <input
                type="hidden"
                name="cliente_id"
                value="{{ $clienteSelecionadoId }}"
            >

        @else

            <div class="position-relative">

                <div class="input-group">

                    <span class="input-group-text">
                        <i class="bi bi-search"></i>
                    </span>

                    <input
                        type="text"
                        id="busca_cliente_nota"
                        class="form-control"
                        placeholder="Nome, CPF ou telefone..."
                        autocomplete="off"
                        wire:model.live.debounce.300ms="buscaCliente"
                    >

                </div>

                <div
                    wire:loading
                    wire:target="buscaCliente"
                    class="small fw-semibold mt-1"
                >
                    <i class="bi bi-arrow-repeat me-1"></i>
                    Buscando clientes...
                </div>

                @if(
                    mb_strlen(trim($buscaCliente)) >= 2
                    && count($resultadosClientes) > 0
                )

                    <div class="busca-selecao mt-1 shadow-sm">

                        <div class="busca-selecao__cabecalho">

                            <span>
                                <i class="bi bi-people me-1"></i>
                                Clientes encontrados
                            </span>

                            <span class="badge text-bg-secondary">
                                {{ count($resultadosClientes) }}
                            </span>

                        </div>

                        <div class="busca-selecao__resultados">

                            @foreach(
                                $resultadosClientes as $cliente
                            )

                                <button
                                    type="button"
                                    class="list-group-item list-group-item-action busca-selecao__item d-flex justify-content-between align-items-center gap-3"
                                    wire:key="cliente-nota-{{ $cliente['id'] }}"
                                    wire:click="selecionarCliente({{ $cliente['id'] }})"
                                >

                                    <span class="text-start">

                                        <span class="d-block fw-bold">
                                            {{ $cliente['nome'] }}
                                        </span>

                                        <span class="small text-body-secondary">

                                            @if(!empty($cliente['cpf']))

                                                <span class="me-3">
                                                    CPF:
                                                    {{ $cliente['cpf'] }}
                                                </span>

                                            @endif

                                            @if(!empty($cliente['telefone']))

                                                <span>
                                                    {{ $cliente['telefone'] }}
                                                </span>

                                            @endif

                                        </span>

                                    </span>

                                    <i class="bi bi-plus-lg"></i>

                                </button>

                            @endforeach

                        </div>

                    </div>

                @elseif(
                    mb_strlen(trim($buscaCliente)) >= 2
                    && count($resultadosClientes) === 0
                )

                    <div class="small fw-semibold mt-2">
                        Nenhum cliente encontrado.
                    </div>

                @endif

            </div>

            <small class="text-muted">
                Deixe vazio para venda de balcão.
            </small>

        @endif

        @error('cliente_id')

            <div class="text-danger small mt-1">
                {{ $message }}
            </div>

        @enderror

    </div>

    {{-- VEÍCULO --}}
    <div class="col-12 col-lg-6">

        <label
            for="busca_veiculo_nota"
            class="form-label"
        >
            <i class="bi bi-car-front"></i>
            Veículo
        </label>

        @if(!$clienteSelecionado)

            <div class="alert alert-secondary mb-0">
                <i class="bi bi-info-circle me-1"></i>

                Selecione um cliente para vincular um veículo.
                A Nota pode ser salva sem veículo em vendas de balcão.
            </div>

        @elseif($veiculoSelecionado)

            <div
                class="d-flex justify-content-between align-items-center border rounded bg-light p-3"
            >

                <div>

                    <div class="fw-bold">

                        <i class="bi bi-car-front me-1"></i>

                        {{ $veiculoSelecionado['veiculo'] }}

                        @if(!empty($veiculoSelecionado['montadora']))

                            —
                            {{ $veiculoSelecionado['montadora'] }}

                        @endif

                    </div>

                    <div class="small text-body-secondary mt-1">

                        <span class="me-3">
                            Placa:
                            {{ $veiculoSelecionado['placa']
                                ?? 'N/A' }}
                        </span>

                        @if(!empty($veiculoSelecionado['ano']))

                            <span class="me-3">
                                Ano:
                                {{ $veiculoSelecionado['ano'] }}
                            </span>

                        @endif

                        @if(!empty($veiculoSelecionado['cor']))

                            <span>
                                Cor:
                                {{ $veiculoSelecionado['cor'] }}
                            </span>

                        @endif

                    </div>

                </div>

                <button
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    wire:click="removerVeiculo"
                    title="Remover veículo"
                >
                    <i class="bi bi-x-lg"></i>
                </button>

            </div>

            <input
                type="hidden"
                name="veiculo_cliente_id"
                value="{{ $veiculoSelecionadoId }}"
            >

        @else

            <div class="position-relative">

                <div class="input-group">

                    <span class="input-group-text">
                        <i class="bi bi-search"></i>
                    </span>

                    <input
                        type="text"
                        id="busca_veiculo_nota"
                        class="form-control"
                        placeholder="Placa, veículo ou montadora..."
                        autocomplete="off"
                        wire:model.live.debounce.300ms="buscaVeiculo"
                    >

                </div>

                <div
                    wire:loading
                    wire:target="buscaVeiculo"
                    class="small fw-semibold mt-1"
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
                                Veículos vinculados ao cliente
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
                                    wire:key="veiculo-nota-{{ $veiculo['id'] }}"
                                    wire:click="selecionarVeiculo({{ $veiculo['id'] }})"
                                >

                                    <span class="text-start">

                                        <span class="d-block fw-bold">

                                            {{ $veiculo['veiculo'] }}

                                            @if(!empty($veiculo['montadora']))

                                                —
                                                {{ $veiculo['montadora'] }}

                                            @endif

                                        </span>

                                        <span class="small text-body-secondary">

                                            Placa:
                                            {{ $veiculo['placa'] ?? 'N/A' }}

                                            @if(!empty($veiculo['ano']))

                                                · Ano:
                                                {{ $veiculo['ano'] }}

                                            @endif

                                            @if(!empty($veiculo['cor']))

                                                · Cor:
                                                {{ $veiculo['cor'] }}

                                            @endif

                                        </span>

                                    </span>

                                    <i class="bi bi-plus-lg"></i>

                                </button>

                            @endforeach

                        </div>

                    </div>

                @elseif(
                    mb_strlen(trim($buscaVeiculo)) >= 2
                    && count($resultadosVeiculos) === 0
                )

                    <div class="small fw-semibold mt-2">
                        Nenhum veículo vinculado a esse cliente foi encontrado.
                    </div>

                @endif

            </div>

            <small class="text-muted">
                O veículo é opcional.
            </small>

        @endif

        @error('veiculo_cliente_id')

            <div class="text-danger small mt-1">
                {{ $message }}
            </div>

        @enderror

    </div>

</div>
