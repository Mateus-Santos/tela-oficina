<div>

    <label
        for="busca_cliente"
        class="form-label"
    >
        Cliente(s): *
    </label>

    <div class="position-relative">

        <div class="input-group">

            <span class="input-group-text">
                <i class="bi bi-search"></i>
            </span>

            <input
                type="search"
                id="busca_cliente"
                class="form-control"
                placeholder="Digite nome, CPF ou telefone..."
                autocomplete="off"
                wire:model.live.debounce.300ms="buscaCliente"
            >

        </div>

        <div
            wire:loading
            wire:target="buscaCliente"
            class="small text-dark fw-semibold mt-1"
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
                            wire:key="resultado-cliente-{{ $cliente['id'] }}"
                            wire:click="selecionarCliente({{ $cliente['id'] }})"
                            @disabled($cliente['selecionado'])
                        >

                            <span class="text-start">

                                <span class="d-block fw-bold">

                                    <i class="bi bi-person me-1"></i>

                                    {{ $cliente['nome'] }}

                                </span>

                                @if(
                                    !empty($cliente['cpf'])
                                    || !empty($cliente['telefone'])
                                )

                                    <span class="small text-body-secondary">

                                        @if(!empty($cliente['cpf']))

                                            <span class="me-3">
                                                CPF:
                                                {{ $cliente['cpf'] }}
                                            </span>

                                        @endif

                                        @if(!empty($cliente['telefone']))

                                            <span>
                                                <i class="bi bi-telephone me-1"></i>

                                                {{ $cliente['telefone'] }}
                                            </span>

                                        @endif

                                    </span>

                                @endif

                            </span>

                            @if($cliente['selecionado'])

                                <span class="badge text-bg-success">
                                    Selecionado
                                </span>

                            @else

                                <i class="bi bi-plus-lg"></i>

                            @endif

                        </button>

                    @endforeach

                </div>

                <div class="busca-selecao__rodape">
                    <i class="bi bi-mouse me-1"></i>
                    Role para visualizar os resultados.
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

    @if(count($clientesSelecionados) > 0)

        <div class="mt-3">

            <div class="d-flex justify-content-between align-items-center mb-2">

                <strong class="small">
                    Clientes vinculados:
                </strong>

                <span class="badge text-bg-primary">
                    {{ count($clientesSelecionados) }}
                </span>

            </div>

            <div class="tags-container">

                @foreach(
                    $clientesSelecionados as $cliente
                )

                    <div
                        class="tag d-inline-flex align-items-center gap-2"
                        wire:key="cliente-selecionado-{{ $cliente['id'] }}"
                    >

                        <span>
                            {{ $cliente['nome'] }}
                        </span>

                        <button
                            type="button"
                            class="remove-tag border-0 bg-transparent p-0"
                            wire:click="removerCliente({{ $cliente['id'] }})"
                            title="Remover cliente"
                            aria-label="Remover {{ $cliente['nome'] }}"
                        >
                            &times;
                        </button>

                    </div>

                    <input
                        type="hidden"
                        name="clientes[]"
                        value="{{ $cliente['id'] }}"
                    >

                @endforeach

            </div>

        </div>

    @else

        <div class="small fw-semibold mt-2">
            Nenhum cliente selecionado.
        </div>

    @endif

    @error('clientes')
        <div class="text-danger small fw-semibold mt-1">
            {{ $message }}
        </div>
    @enderror

    @error('clientes.*')
        <div class="text-danger small fw-semibold mt-1">
            {{ $message }}
        </div>
    @enderror

</div>
