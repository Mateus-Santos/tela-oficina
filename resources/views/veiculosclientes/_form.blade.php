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

        @if(auth()->user() && auth()->user()->permitions == 1)

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

            </div>

        @else

            <div class="col-12 col-md-6">

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
                                {{ auth()->user()->pessoa?->nome
                                    ?? auth()->user()->name
                                    ?? 'Cliente' }}
                            </strong>

                        </div>

                    @endif

                </div>

                @if(isset($veiculoscliente))

                    <div class="form-text">
                        Usuários comuns não podem alterar os responsáveis pelo veículo.
                    </div>

                @endif

            </div>

        @endif

    </div>

    {{-- VEÍCULO --}}
    <div class="row mb-4">

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

        </div>

    </div>

    {{-- DADOS DO VEÍCULO --}}
    <div class="row g-3 mb-3">

        {{-- PLACA --}}
        <div class="col-12 col-md-4">

            <label
                class="form-label"
                for="placa"
            >
                Placa:*
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
                Ano:*
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
                Cor:
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
            >

            @error('cor')

                <div class="invalid-feedback">
                    {{ $message }}
                </div>

            @enderror

        </div>

    </div>

</div>
