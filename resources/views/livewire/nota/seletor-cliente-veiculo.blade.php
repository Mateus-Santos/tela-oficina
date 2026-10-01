<div class="row g-3">

    {{-- =====================================================
         CLIENTE
    ====================================================== --}}

    <div class="col-12 col-lg-6">

        <label
            for="busca_cliente_nota"
            class="form-label"
        >
            <i class="bi bi-person"></i>
            Cliente
        </label>

        @if($clienteSelecionado)

            <div class="border rounded bg-light p-3">

                <div class="d-flex justify-content-between align-items-start gap-3">

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
                        title="Trocar cliente"
                    >
                        <i class="bi bi-x-lg"></i>
                    </button>

                </div>

            </div>

            <input
                type="hidden"
                name="cliente_id"
                id="cliente_id"
                value="{{ $clienteSelecionadoId }}"
            >

        @else

            @if(!$exibirCadastroClienteRapido)

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
                                    $resultadosClientes
                                    as $cliente
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

                <div class="d-flex justify-content-between align-items-center gap-2 mt-2">

                    <small class="text-muted">
                        Deixe vazio para venda de balcão.
                    </small>

                    <button
                        type="button"
                        class="btn btn-outline-primary btn-sm"
                        wire:click="abrirCadastroClienteRapido"
                    >
                        <i class="bi bi-person-plus"></i>
                        Novo cliente
                    </button>

                </div>

            @else

                <div class="card border-primary">

                    <div class="card-header d-flex justify-content-between align-items-center">

                        <strong>
                            <i class="bi bi-person-plus"></i>
                            Novo cliente
                        </strong>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            wire:click="fecharCadastroClienteRapido"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>

                    </div>

                    <div class="card-body">

                        <div class="mb-3">

                            <label
                                for="cliente_rapido_nome"
                                class="form-label"
                            >
                                Nome
                            </label>

                            <input
                                type="text"
                                id="cliente_rapido_nome"
                                class="form-control @error('clienteRapidoNome') is-invalid @enderror"
                                wire:model="clienteRapidoNome"
                                maxlength="255"
                                autocomplete="off"
                            >

                            @error('clienteRapidoNome')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                        <div class="mb-3">

                            <label
                                for="cliente_rapido_telefone"
                                class="form-label"
                            >
                                Telefone principal
                            </label>

                            <input
                                type="text"
                                id="cliente_rapido_telefone"
                                class="form-control @error('clienteRapidoTelefone') is-invalid @enderror"
                                wire:model="clienteRapidoTelefone"
                                maxlength="15"
                                inputmode="tel"
                                autocomplete="off"
                                placeholder="Ex.: 75999999999"
                            >

                            @error('clienteRapidoTelefone')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                        <div class="d-flex justify-content-end gap-2">

                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                wire:click="fecharCadastroClienteRapido"
                            >
                                Cancelar
                            </button>

                            <button
                                type="button"
                                class="btn btn-primary"
                                wire:click="criarClienteRapido"
                                wire:loading.attr="disabled"
                                wire:target="criarClienteRapido"
                            >

                                <span
                                    wire:loading.remove
                                    wire:target="criarClienteRapido"
                                >
                                    <i class="bi bi-check-circle"></i>
                                    Criar e selecionar
                                </span>

                                <span
                                    wire:loading
                                    wire:target="criarClienteRapido"
                                >
                                    Criando...
                                </span>

                            </button>

                        </div>

                    </div>

                </div>

            @endif

        @endif

        @error('cliente_id')

            <div class="text-danger small mt-1">
                {{ $message }}
            </div>

        @enderror

    </div>


    {{-- =====================================================
         VEÍCULO
    ====================================================== --}}

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

            <div class="border rounded bg-light p-3">

                <div class="d-flex justify-content-between align-items-start gap-3">

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
                                {{
                                    $veiculoSelecionado['placa']
                                    ?? 'N/A'
                                }}
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
                        title="Trocar veículo"
                    >
                        <i class="bi bi-x-lg"></i>
                    </button>

                </div>

            </div>

            <input
                type="hidden"
                name="veiculo_cliente_id"
                id="veiculo_cliente_id"
                value="{{ $veiculoSelecionadoId }}"
            >

        @else

            @if(!$exibirCadastroVeiculoRapido)

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
                                    $resultadosVeiculos
                                    as $veiculo
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

                <div class="d-flex justify-content-between align-items-center gap-2 mt-2">

                    <small class="text-muted">
                        O veículo é opcional.
                    </small>

                    <button
                        type="button"
                        class="btn btn-outline-primary btn-sm"
                        wire:click="abrirCadastroVeiculoRapido"
                    >
                        <i class="bi bi-car-front-fill"></i>
                        Novo veículo
                    </button>

                </div>

            @else

                <div class="card border-primary">

                    <div class="card-header d-flex justify-content-between align-items-center">

                        <strong>
                            <i class="bi bi-car-front"></i>
                            Novo veículo
                        </strong>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            wire:click="fecharCadastroVeiculoRapido"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>

                    </div>

                    <div class="card-body">

                        <div class="row g-3">

                            <div class="col-12">

                                <label
                                    for="veiculo_rapido_montadora"
                                    class="form-label"
                                >
                                    Montadora
                                </label>

                                <select
                                    id="veiculo_rapido_montadora"
                                    class="form-select @error('veiculoRapidoMontadoraId') is-invalid @enderror"
                                    wire:model.live="veiculoRapidoMontadoraId"
                                >

                                    <option value="">
                                        Selecione...
                                    </option>

                                    @foreach(
                                        $montadorasDisponiveis
                                        as $montadora
                                    )

                                        <option
                                            value="{{ $montadora['id'] }}"
                                        >
                                            {{ $montadora['nome'] }}
                                        </option>

                                    @endforeach

                                </select>

                                @error('veiculoRapidoMontadoraId')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                            <div class="col-12">

                                <label
                                    for="veiculo_rapido_modelo"
                                    class="form-label"
                                >
                                    Modelo
                                </label>

                                <select
                                    id="veiculo_rapido_modelo"
                                    class="form-select @error('veiculoRapidoVeiculoId') is-invalid @enderror"
                                    wire:model="veiculoRapidoVeiculoId"
                                    @disabled(!$veiculoRapidoMontadoraId)
                                >

                                    <option value="">
                                        Selecione...
                                    </option>

                                    @foreach(
                                        $veiculosDisponiveis
                                        as $veiculo
                                    )

                                        <option
                                            value="{{ $veiculo['id'] }}"
                                        >
                                            {{ $veiculo['nome'] }}
                                        </option>

                                    @endforeach

                                </select>

                                @error('veiculoRapidoVeiculoId')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                            <div class="col-12 col-md-4">

                                <label
                                    for="veiculo_rapido_placa"
                                    class="form-label"
                                >
                                    Placa
                                </label>

                                <input
                                    type="text"
                                    id="veiculo_rapido_placa"
                                    class="form-control @error('veiculoRapidoPlaca') is-invalid @enderror"
                                    wire:model="veiculoRapidoPlaca"
                                    maxlength="7"
                                    autocomplete="off"
                                    placeholder="ABC1D23"
                                >

                                @error('veiculoRapidoPlaca')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                            <div class="col-12 col-md-4">

                                <label
                                    for="veiculo_rapido_ano"
                                    class="form-label"
                                >
                                    Ano
                                </label>

                                <input
                                    type="number"
                                    id="veiculo_rapido_ano"
                                    class="form-control @error('veiculoRapidoAno') is-invalid @enderror"
                                    wire:model="veiculoRapidoAno"
                                    min="1900"
                                    max="{{ date('Y') + 1 }}"
                                    placeholder="{{ date('Y') }}"
                                >

                                @error('veiculoRapidoAno')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                            <div class="col-12 col-md-4">

                                <label
                                    for="veiculo_rapido_cor"
                                    class="form-label"
                                >
                                    Cor
                                </label>

                                <input
                                    type="text"
                                    id="veiculo_rapido_cor"
                                    class="form-control @error('veiculoRapidoCor') is-invalid @enderror"
                                    wire:model="veiculoRapidoCor"
                                    maxlength="20"
                                    placeholder="Branco"
                                >

                                @error('veiculoRapidoCor')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">

                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                wire:click="fecharCadastroVeiculoRapido"
                            >
                                Cancelar
                            </button>

                            <button
                                type="button"
                                class="btn btn-primary"
                                wire:click="criarVeiculoRapido"
                                wire:loading.attr="disabled"
                                wire:target="criarVeiculoRapido"
                            >

                                <span
                                    wire:loading.remove
                                    wire:target="criarVeiculoRapido"
                                >
                                    <i class="bi bi-check-circle"></i>
                                    Criar e selecionar
                                </span>

                                <span
                                    wire:loading
                                    wire:target="criarVeiculoRapido"
                                >
                                    Criando...
                                </span>

                            </button>

                        </div>

                    </div>

                </div>

            @endif

        @endif

        @error('veiculo_cliente_id')

            <div class="text-danger small mt-1">
                {{ $message }}
            </div>

        @enderror

    </div>

</div>
