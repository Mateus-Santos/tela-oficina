@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="LISTAR VEÍCULOS"
        icon="bi-car-front"
        create-route="veiculos.create"
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
        action="{{ route('veiculos.index') }}"
        id="filtros-veiculos"
        :collapsible="false"
    >
        <div class="row g-3 align-items-end">

            <div class="col-12 col-md-5">
                <label for="veiculo" class="form-label">
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

            <div class="col-12 col-md-5">
                <label for="montadora" class="form-label">
                    <i class="bi bi-building"></i>
                    Montadora
                </label>

                <select
                    name="montadora"
                    id="montadora"
                    class="filtros-container__select"
                >
                    <option value="">Todas as montadoras</option>

                    @foreach ($montadoras as $montadora)
                        <option
                            value="{{ $montadora->id }}"
                            @selected(request('montadora') == $montadora->id)
                        >
                            {{ $montadora->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-2">
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
                        href="{{ route('veiculos.index') }}"
                        class="btn btn-secondary"
                        title="Limpar filtros"
                    >
                        <i class="bi bi-x-lg"></i>
                    </a>

                </div>
            </div>

        </div>
    </x-filtros-container>

    @php
        $possuiFiltros = request()->filled('veiculo') || request()->filled('montadora');
    @endphp

    @if ($veiculos->isEmpty())
        <div class="alert alert-{{ $possuiFiltros ? 'warning' : 'info' }}">
            <i class="bi {{ $possuiFiltros ? 'bi-exclamation-triangle' : 'bi-info-circle' }}"></i>

            {{ $possuiFiltros
                ? 'Nenhum veículo encontrado com os filtros informados.'
                : 'Nenhum veículo cadastrado.' }}
        </div>
    @endif

    @if ($veiculos->isNotEmpty())

        <div class="table-responsive">

            <table class="table table-striped table-hover align-middle">

                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">MONTADORA</th>
                        <th scope="col">VEÍCULO</th>
                        <th scope="col" class="text-center">EDITAR</th>
                        <th scope="col" class="text-center">EXCLUIR</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach ($veiculos as $veiculo)

                        <tr>

                            <th scope="row">
                                {{ $veiculo->id }}
                            </th>

                            <td>
                                <i class="bi bi-building"></i>
                                {{ $veiculo->montadora?->nome ?? 'N/A' }}
                            </td>

                            <td>
                                <i class="bi bi-car-front"></i>
                                {{ $veiculo->nome ?? 'N/A' }}
                            </td>

                            <td class="text-center">
                                <a
                                    href="{{ route('veiculos.edit', $veiculo->id) }}"
                                    class="btn btn-sm btn-primary"
                                    title="Editar veículo"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            </td>

                            <td class="text-center">

                                <form
                                    action="{{ route('veiculos.destroy', $veiculo->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Tem certeza que deseja excluir este veículo?');"
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

        @if (method_exists($veiculos, 'hasPages') && $veiculos->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $veiculos->links() }}
            </div>
        @endif

    @endif

</div>

@endsection
