@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="LISTAR COMPRAS"
        icon="bi-cart-check"
        create-route="compras.create"
        create-text="Nova Compra"
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
        action="{{ route('compras.index') }}"
        id="filtros-compras"
        :collapsible="true"
        :expanded="request()->filled('data_inicio') || request()->filled('data_fim')"
    >

        <x-slot name="primary">

            <div class="row g-3 align-items-end">

                <div class="col-12 col-md-4">

                    <label for="numero_nf" class="form-label">
                        <i class="bi bi-receipt"></i>
                        Número da NF
                    </label>

                    <input
                        type="text"
                        name="numero_nf"
                        id="numero_nf"
                        class="filtros-container__input"
                        value="{{ request('numero_nf') }}"
                        placeholder="Número da nota fiscal"
                    >

                </div>

                <div class="col-12 col-md-4">

                    <label for="fornecedor_id" class="form-label">
                        <i class="bi bi-truck"></i>
                        Fornecedor
                    </label>

                    <select
                        name="fornecedor_id"
                        id="fornecedor_id"
                        class="filtros-container__select"
                    >
                        <option value="">Todos os fornecedores</option>

                        @foreach ($fornecedores as $fornecedor)
                            <option
                                value="{{ $fornecedor->id }}"
                                @selected((string) request('fornecedor_id') === (string) $fornecedor->id)
                            >
                                {{ $fornecedor->nome }}
                            </option>
                        @endforeach

                    </select>

                </div>

                <div class="col-12 col-md-4">

                    <label for="status" class="form-label">
                        <i class="bi bi-filter-circle"></i>
                        Status
                    </label>

                    <select
                        name="status"
                        id="status"
                        class="filtros-container__select"
                    >
                        <option value="">Todos os status</option>

                        <option
                            value="pendente"
                            @selected(request('status') === 'pendente')
                        >
                            Pendente
                        </option>

                        <option
                            value="conferindo"
                            @selected(request('status') === 'conferindo')
                        >
                            Conferindo
                        </option>

                        <option
                            value="aprovada"
                            @selected(request('status') === 'aprovada')
                        >
                            Aprovada
                        </option>

                        <option
                            value="cancelada"
                            @selected(request('status') === 'cancelada')
                        >
                            Cancelada
                        </option>

                    </select>

                </div>

            </div>

        </x-slot>

        <x-slot name="advanced">

            <div class="row g-3 align-items-end">

                <div class="col-12 col-md-5">

                    <label for="data_inicio" class="form-label">
                        <i class="bi bi-calendar-event"></i>
                        Data inicial
                    </label>

                    <input
                        type="date"
                        name="data_inicio"
                        id="data_inicio"
                        class="filtros-container__input"
                        value="{{ request('data_inicio') }}"
                    >

                </div>

                <div class="col-12 col-md-5">

                    <label for="data_fim" class="form-label">
                        <i class="bi bi-calendar-check"></i>
                        Data final
                    </label>

                    <input
                        type="date"
                        name="data_fim"
                        id="data_fim"
                        class="filtros-container__input"
                        value="{{ request('data_fim') }}"
                    >

                </div>

                <div class="col-12 col-md-2">

                    <div class="filtros-container__actions">

                        <button
                            type="submit"
                            class="btn btn-primary"
                            title="Filtrar compras"
                        >
                            <i class="bi bi-search"></i>
                            Filtrar
                        </button>

                        <a
                            href="{{ route('compras.index') }}"
                            class="btn btn-secondary"
                            title="Limpar filtros"
                        >
                            <i class="bi bi-x-lg"></i>
                        </a>

                    </div>

                </div>

            </div>

        </x-slot>

    </x-filtros-container>

    @php
        $possuiFiltros =
            request()->filled('numero_nf') ||
            request()->filled('fornecedor_id') ||
            request()->filled('status') ||
            request()->filled('data_inicio') ||
            request()->filled('data_fim');
    @endphp

    @if ($compras->isEmpty())

        <div class="alert alert-{{ $possuiFiltros ? 'warning' : 'info' }}">

            <i class="bi {{ $possuiFiltros ? 'bi-exclamation-triangle' : 'bi-info-circle' }}"></i>

            {{ $possuiFiltros
                ? 'Nenhuma compra encontrada com os filtros informados.'
                : 'Nenhuma compra cadastrada.' }}

        </div>

    @endif

    @if ($compras->isNotEmpty())

        <div class="table-responsive">

            <table class="table table-striped table-hover align-middle">

                <thead>

                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">NF</th>
                        <th scope="col">FORNECEDOR</th>
                        <th scope="col">DATA DE ENTRADA</th>
                        <th scope="col">VALOR TOTAL</th>
                        <th scope="col">STATUS</th>
                        <th scope="col">AÇÕES</th>
                    </tr>

                </thead>

                <tbody>

                    @foreach ($compras as $compra)

                        @php
                            $statusConfig = match ($compra->status) {
                                'pendente' => [
                                    'class' => 'bg-warning text-dark',
                                    'icon' => 'bi-clock',
                                    'label' => 'Pendente',
                                ],
                                'conferindo' => [
                                    'class' => 'bg-info text-dark',
                                    'icon' => 'bi-search',
                                    'label' => 'Conferindo',
                                ],
                                'aprovada' => [
                                    'class' => 'bg-success',
                                    'icon' => 'bi-check-circle',
                                    'label' => 'Aprovada',
                                ],
                                'cancelada' => [
                                    'class' => 'bg-danger',
                                    'icon' => 'bi-x-circle',
                                    'label' => 'Cancelada',
                                ],
                                default => [
                                    'class' => 'bg-secondary',
                                    'icon' => 'bi-question-circle',
                                    'label' => ucfirst($compra->status),
                                ],
                            };
                        @endphp

                        <tr>

                            <td>
                                {{ $compra->id }}
                            </td>

                            <td>
                                <strong>
                                    {{ $compra->numero_nf }}
                                </strong>

                                @if ($compra->serie_nf)
                                    <br>
                                    <small class="text-muted">
                                        Série {{ $compra->serie_nf }}
                                    </small>
                                @endif
                            </td>

                            <td>
                                {{ $compra->fornecedor->nome ?? 'Não informado' }}
                            </td>

                            <td>
                                {{ $compra->data_entrada?->format('d/m/Y') ?? '-' }}
                            </td>

                            <td>
                                <strong>
                                    R$ {{ number_format($compra->valor_total, 2, ',', '.') }}
                                </strong>
                            </td>

                            <td>

                                <span class="badge {{ $statusConfig['class'] }}">

                                    <i class="bi {{ $statusConfig['icon'] }}"></i>

                                    {{ $statusConfig['label'] }}

                                </span>

                            </td>

                            <td>

                                <div class="d-flex gap-1">

                                    <a
                                        href="{{ route('compras.show', $compra) }}"
                                        class="btn btn-success btn-sm"
                                        title="Visualizar compra"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    @if (in_array($compra->status, ['pendente', 'conferindo'], true))

                                        <a
                                            href="{{ route('compras.edit', $compra) }}"
                                            class="btn btn-primary btn-sm"
                                            title="Editar compra"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <form
                                            action="{{ route('compras.destroy', $compra) }}"
                                            method="POST"
                                            onsubmit="return confirm('Tem certeza que deseja excluir esta compra?');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="btn btn-danger btn-sm"
                                                title="Excluir compra"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>

                                        </form>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        @if ($compras->hasPages())

            <div class="d-flex justify-content-center mt-4">
                {{ $compras->links() }}
            </div>

        @endif

    @endif

</div>

@endsection
