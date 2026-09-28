@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="LISTAR VEÍCULOS DE CLIENTES"
        icon="bi-car-front"
        create-route="veiculosclientes.create"
        create-text="Novo Veículo"
        create-icon="bi-plus-lg"
    />

    @if (session('success'))

        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i>
            {{ session('success') }}
        </div>

    @endif

    @if (session('error'))

        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i>
            {{ session('error') }}
        </div>

    @endif

    <x-filtros-container
        action="{{ route('veiculosclientes.index') }}"
        id="filtros-veiculos-clientes"
        :collapsible="true"
        :expanded="request()->hasAny([
            'montadora',
            'ano',
            'cor'
        ])"
    >

        <x-slot:primary>

            <div class="row g-3 align-items-end">

                {{-- CLIENTE --}}
                <div class="col-12 col-md-4">

                    <label
                        for="cliente"
                        class="form-label"
                    >
                        <i class="bi bi-person"></i>
                        Cliente
                    </label>

                    <input
                        type="text"
                        name="cliente"
                        id="cliente"
                        class="filtros-container__input"
                        placeholder="Nome do cliente"
                        value="{{ request('cliente') }}"
                    >

                </div>

                {{-- PLACA --}}
                <div class="col-12 col-md-4">

                    <label
                        for="placa"
                        class="form-label"
                    >
                        <i class="bi bi-credit-card-2-front"></i>
                        Placa
                    </label>

                    <input
                        type="text"
                        name="placa"
                        id="placa"
                        class="filtros-container__input"
                        placeholder="Placa do veículo"
                        value="{{ request('placa') }}"
                    >

                </div>

                {{-- VEÍCULO --}}
                <div class="col-12 col-md-4">

                    <label
                        for="veiculo"
                        class="form-label"
                    >
                        <i class="bi bi-car-front"></i>
                        Veículo
                    </label>

                    <input
                        type="text"
                        name="veiculo"
                        id="veiculo"
                        class="filtros-container__input"
                        placeholder="Nome do veículo"
                        value="{{ request('veiculo') }}"
                    >

                </div>

            </div>

        </x-slot:primary>

        <x-slot:advanced>

            <div class="row g-3 align-items-end">

                {{-- MONTADORA --}}
                <div class="col-12 col-md-3">

                    <label
                        for="montadora"
                        class="form-label"
                    >
                        <i class="bi bi-building"></i>
                        Montadora
                    </label>

                    <select
                        name="montadora"
                        id="montadora"
                        class="filtros-container__select"
                    >

                        <option value="">
                            Todas as montadoras
                        </option>

                        @foreach ($montadoras as $montadora)

                            <option
                                value="{{ $montadora->id }}"
                                @selected(
                                    request('montadora')
                                    == $montadora->id
                                )
                            >
                                {{ $montadora->nome }}
                            </option>

                        @endforeach

                    </select>

                </div>

                {{-- ANO --}}
                <div class="col-12 col-md-3">

                    <label
                        for="ano"
                        class="form-label"
                    >
                        <i class="bi bi-calendar3"></i>
                        Ano
                    </label>

                    <input
                        type="text"
                        name="ano"
                        id="ano"
                        class="filtros-container__input"
                        placeholder="Ano"
                        value="{{ request('ano') }}"
                    >

                </div>

                {{-- COR --}}
                <div class="col-12 col-md-3">

                    <label
                        for="cor"
                        class="form-label"
                    >
                        <i class="bi bi-palette"></i>
                        Cor
                    </label>

                    <input
                        type="text"
                        name="cor"
                        id="cor"
                        class="filtros-container__input"
                        placeholder="Cor"
                        value="{{ request('cor') }}"
                    >

                </div>

                {{-- AÇÕES --}}
                <div class="col-12 col-md-3">

                    <div class="filtros-container__actions">

                        <button
                            type="submit"
                            class="btn btn-primary"
                            title="Filtrar veículos"
                        >
                            <i class="bi bi-search"></i>
                            Filtrar
                        </button>

                        <a
                            href="{{ route('veiculosclientes.index') }}"
                            class="btn btn-secondary"
                            title="Limpar filtros"
                        >
                            <i class="bi bi-x-lg"></i>
                        </a>

                    </div>

                </div>

            </div>

        </x-slot:advanced>

    </x-filtros-container>

    @php
        $possuiFiltros = request()->hasAny([
            'cliente',
            'placa',
            'veiculo',
            'montadora',
            'ano',
            'cor',
        ]);
    @endphp

    @if ($veiculosclientes->isEmpty())

        <div
            class="alert alert-{{ $possuiFiltros ? 'warning' : 'info' }}"
        >

            <i
                class="bi {{ $possuiFiltros
                    ? 'bi-exclamation-triangle'
                    : 'bi-info-circle' }}"
            ></i>

            {{ $possuiFiltros
                ? 'Nenhum veículo encontrado com os filtros informados.'
                : 'Nenhum veículo de cliente cadastrado.' }}

        </div>

    @endif

    @if ($veiculosclientes->isNotEmpty())

        <div class="table-responsive">

            <table
                class="table table-striped table-hover align-middle"
            >

                <thead>

                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">VEÍCULO</th>
                        <th scope="col">MONTADORA</th>
                        <th scope="col">PLACA</th>
                        <th scope="col">ANO</th>
                        <th scope="col">COR</th>
                        <th scope="col">RESPONSÁVEIS</th>
                        <th
                            scope="col"
                            class="text-center"
                        >
                            EDITAR
                        </th>
                        <th
                            scope="col"
                            class="text-center"
                        >
                            EXCLUIR
                        </th>
                    </tr>

                </thead>

                <tbody>

                    @foreach ($veiculosclientes as $veiculoscliente)

                        <tr>

                            {{-- ID --}}
                            <th scope="row">
                                {{ $veiculoscliente->id }}
                            </th>

                            {{-- VEÍCULO --}}
                            <td>

                                <i class="bi bi-car-front me-1"></i>

                                {{ $veiculoscliente
                                    ->veiculo
                                    ?->nome
                                    ?? 'N/A' }}

                            </td>

                            {{-- MONTADORA --}}
                            <td>

                                <i class="bi bi-building me-1"></i>

                                {{ $veiculoscliente
                                    ->veiculo
                                    ?->montadora
                                    ?->nome
                                    ?? 'N/A' }}

                            </td>

                            {{-- PLACA --}}
                            <td>

                                <i class="bi bi-credit-card-2-front me-1"></i>

                                {{ $veiculoscliente->placa ?? 'N/A' }}

                            </td>

                            {{-- ANO --}}
                            <td>

                                <i class="bi bi-calendar3 me-1"></i>

                                {{ $veiculoscliente->ano ?? 'N/A' }}

                            </td>

                            {{-- COR --}}
                            <td>

                                <i class="bi bi-palette me-1"></i>

                                {{ $veiculoscliente->cor ?? 'N/A' }}

                            </td>

                            {{-- RESPONSÁVEIS --}}
                            <td>

                                @if(
                                    $veiculoscliente
                                        ->clientes
                                        ->isNotEmpty()
                                )

                                    <div
                                        class="d-flex flex-wrap gap-1"
                                    >

                                        @foreach(
                                            $veiculoscliente->clientes
                                            as $cliente
                                        )

                                            <span
                                                class="badge text-bg-secondary"
                                                title="Cliente vinculado"
                                            >

                                                <i class="bi bi-person me-1"></i>

                                                {{ $cliente
                                                    ->pessoa
                                                    ?->nome
                                                    ?? 'Cliente sem nome' }}

                                            </span>

                                        @endforeach

                                    </div>

                                @else

                                    <span
                                        class="text-body-secondary"
                                    >
                                        <i class="bi bi-person-x me-1"></i>
                                        Nenhum responsável
                                    </span>

                                @endif

                            </td>

                            {{-- EDITAR --}}
                            <td class="text-center">

                                <a
                                    href="{{ route(
                                        'veiculosclientes.edit',
                                        $veiculoscliente->id
                                    ) }}"
                                    class="btn btn-sm btn-primary"
                                    title="Editar veículo"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                            </td>

                            {{-- EXCLUIR --}}
                            <td class="text-center">

                                <form
                                    action="{{ route(
                                        'veiculosclientes.destroy',
                                        $veiculoscliente->id
                                    ) }}"
                                    method="POST"
                                    onsubmit="return confirm(
                                        'Tem certeza que deseja excluir este veículo?'
                                    );"
                                >

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-danger"
                                        title="Excluir veículo"
                                    >
                                        <i class="bi bi-trash3"></i>
                                    </button>

                                </form>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        @if ($veiculosclientes->hasPages())

            <div
                class="d-flex justify-content-center mt-4"
            >
                {{ $veiculosclientes->links() }}
            </div>

        @endif

    @endif

</div>

@endsection
