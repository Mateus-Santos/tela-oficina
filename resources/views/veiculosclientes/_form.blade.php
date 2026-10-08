@php
    $usuarioLogado = auth()->user();
    $administrador = $usuarioLogado?->permitions == 1;
@endphp

<div class="campos">

    @if ($errors->any())

        <div class="alert alert-danger">

            <ul class="mb-0">

                @foreach ($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif

    {{-- CLIENTES --}}
    <div class="row mb-4">

        <div class="col-12">

            <h5 class="mb-3">
                <i class="bi bi-people me-1"></i>
                Responsável(is) pelo veículo
            </h5>

        </div>

        @if($administrador)

            <div class="col-12 col-lg-9">

                <livewire:cliente.seletor-cliente
                    :clientes-selecionados-ids="old(
                        'clientes',
                        isset($veiculoscliente)
                            ? $veiculoscliente
                                ->clientes
                                ->pluck('id')
                                ->all()
                            : []
                    )"
                />

                <div class="form-text mt-2">
                    É possível vincular mais de um cliente ao mesmo veículo.
                </div>

            </div>

        @else

            <div class="col-12 col-lg-9">

                <label class="form-label">
                    Cliente(s):
                </label>

                <div class="border rounded bg-light p-3">

                    @if(isset($veiculoscliente))

                        @forelse(
                            $veiculoscliente->clientes as $cliente
                        )

                            <div
                                class="d-flex align-items-center gap-2 mb-1"
                            >
                                <i class="bi bi-person-check"></i>

                                <strong>
                                    {{ $cliente->pessoa?->nome
                                        ?? 'Cliente sem nome' }}
                                </strong>
                            </div>

                        @empty

                            <span class="text-body-secondary">
                                Nenhum cliente vinculado.
                            </span>

                        @endforelse

                    @else

                        <div class="d-flex align-items-center gap-2">

                            <i class="bi bi-person-check fs-5"></i>

                            <strong>
                                {{ $usuarioLogado?->pessoa?->nome
                                    ?? $usuarioLogado?->name
                                    ?? 'Cliente' }}
                            </strong>

                        </div>

                    @endif

                </div>

                @if(isset($veiculoscliente))

                    <div class="form-text">
                        Usuários comuns não podem alterar os responsáveis pelo veículo.
                    </div>

                @else

                    <div class="form-text">
                        O veículo será vinculado automaticamente ao seu cadastro.
                    </div>

                @endif

            </div>

        @endif

    </div>

    <hr class="my-4">

    {{-- VEÍCULO --}}
    <div class="row mb-4">

        <div class="col-12">

            <h5 class="mb-3">
                <i class="bi bi-car-front me-1"></i>
                Modelo do veículo
            </h5>

        </div>

        <div class="col-12 col-lg-9">

            <livewire:veiculo.seletor-veiculo
                :veiculo-selecionado-id="
                    old(
                        'veiculo_id',
                        $veiculoscliente->veiculo_id ?? null
                    )
                        ? (int) old(
                            'veiculo_id',
                            $veiculoscliente->veiculo_id ?? null
                        )
                        : null
                "
            />

            @error('veiculo_id')
                <div class="text-danger small fw-semibold mt-1">
                    {{ $message }}
                </div>
            @enderror

        </div>

    </div>

    <hr class="my-4">

    {{-- DADOS DO VEÍCULO --}}
    <div class="row">

        <div class="col-12">

            <h5 class="mb-3">
                <i class="bi bi-card-text me-1"></i>
                Dados do veículo
            </h5>

        </div>

    </div>

    <div class="row g-3 mb-3">

        {{-- PLACA --}}
        <div class="col-12 col-md-4">

            <label
                class="form-label"
                for="placa"
            >
                Placa *
            </label>

            <input
                type="text"
                class="form-control @error('placa') is-invalid @enderror"
                id="placa"
                name="placa"
                value="{{ old(
                    'placa',
                    $veiculoscliente->placa ?? ''
                ) }}"
                placeholder="Ex.: ABC1D23"
                maxlength="7"
                autocomplete="off"
                required
            >

            @error('placa')

                <div class="invalid-feedback">
                    {{ $message }}
                </div>

            @enderror

        </div>

        {{-- ANO --}}
        <div class="col-12 col-md-3">

            <label
                class="form-label"
                for="ano"
            >
                Ano *
            </label>

            <input
                type="number"
                class="form-control @error('ano') is-invalid @enderror"
                id="ano"
                name="ano"
                value="{{ old(
                    'ano',
                    $veiculoscliente->ano ?? ''
                ) }}"
                placeholder="Ex.: 2022"
                min="1900"
                max="{{ date('Y') + 1 }}"
                required
            >

            @error('ano')

                <div class="invalid-feedback">
                    {{ $message }}
                </div>

            @enderror

        </div>

        {{-- COR --}}
        <div class="col-12 col-md-4">

            <label
                class="form-label"
                for="cor"
            >
                Cor
            </label>

            <input
                type="text"
                class="form-control @error('cor') is-invalid @enderror"
                id="cor"
                name="cor"
                value="{{ old(
                    'cor',
                    $veiculoscliente->cor ?? ''
                ) }}"
                placeholder="Ex.: Branco"
                maxlength="20"
                autocomplete="off"
            >

            @error('cor')

                <div class="invalid-feedback">
                    {{ $message }}
                </div>

            @enderror

        </div>

    </div>

</div>
