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

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- RESUMO --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small text-uppercase">Total</div>
                            <div class="fs-4 fw-bold">
                                {{ $resumo['total'] }}
                            </div>
                        </div>
                        <i class="bi bi-cart-check fs-2 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small text-uppercase">Valor total</div>
                            <div class="fs-4 fw-bold">
                                R$ {{ number_format($resumo['valor_total'], 2, ',', '.') }}
                            </div>
                        </div>
                        <i class="bi bi-cash-stack fs-2 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small text-uppercase">Aprovadas</div>
                            <div class="fs-4 fw-bold text-success">
                                {{ $resumo['aprovadas'] }}
                            </div>
                        </div>
                        <i class="bi bi-check-circle fs-2 text-success"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small text-uppercase">Pendentes</div>
                            <div class="fs-4 fw-bold">
                                {{ $resumo['pendentes'] }}
                            </div>
                        </div>
                        <i class="bi bi-hourglass-split fs-2 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTROS --}}
    <x-filtros-container
        action="{{ route('compras.index') }}"
        id="filtros-compras"
        :collapsible="true"
        :expanded="request()->filled('data_inicio') || request()->filled('data_fim')"
    >
        <x-slot name="primary">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label for="numero_nf" class="form-label">Número da NF</label>
                    <input
                        type="text"
                        name="numero_nf"
                        id="numero_nf"
                        class="form-control"
                        value="{{ request('numero_nf') }}"
                        placeholder="Número da nota fiscal"
                    >
                </div>

                <div class="col-12 col-md-4">
                    <label for="fornecedor_id" class="form-label">Fornecedor</label>
                    <select name="fornecedor_id" id="fornecedor_id" class="form-select">
                        <option value="">Todos os fornecedores</option>
                        @foreach($fornecedores as $fornecedor)
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
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos os status</option>
                        <option value="pendente" @selected(request('status') === 'pendente')>
                            Pendente
                        </option>
                        <option value="conferindo" @selected(request('status') === 'conferindo')>
                            Conferindo
                        </option>
                        <option value="aprovada" @selected(request('status') === 'aprovada')>
                            Aprovada
                        </option>
                        <option value="cancelada" @selected(request('status') === 'cancelada')>
                            Cancelada
                        </option>
                    </select>
                </div>

                <div class="col-12">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i>
                            Filtrar
                        </button>

                        <a href="{{ route('compras.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-1"></i>
                            Limpar
                        </a>
                    </div>
                </div>
            </div>
        </x-slot>

        <x-slot name="advanced">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="data_inicio" class="form-label">Data inicial</label>
                    <input
                        type="date"
                        name="data_inicio"
                        id="data_inicio"
                        class="form-control"
                        value="{{ request('data_inicio') }}"
                    >
                </div>

                <div class="col-12 col-md-6">
                    <label for="data_fim" class="form-label">Data final</label>
                    <input
                        type="date"
                        name="data_fim"
                        id="data_fim"
                        class="form-control"
                        value="{{ request('data_fim') }}"
                    >
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

    @if($compras->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-cart-x fs-1 text-muted"></i>

                <h5 class="mt-3">Nenhuma compra encontrada</h5>

                @if($possuiFiltros)
                    <p class="text-muted mb-3">
                        Nenhuma compra corresponde aos filtros informados.
                    </p>

                    <a href="{{ route('compras.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg me-1"></i>
                        Limpar filtros
                    </a>
                @else
                    <p class="text-muted mb-3">
                        Ainda não existem compras cadastradas.
                    </p>

                    <a href="{{ route('compras.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>
                        Nova Compra
                    </a>
                @endif
            </div>
        </div>
    @else
        @php
            $gruposPorData = $compras->getCollection()->groupBy(
                fn ($compra) => $compra->data_entrada?->format('Y-m-d') ?? 'sem-data'
            );
        @endphp

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Agenda de compras</h5>
                <div class="text-muted small">
                    {{ $compras->total() }}
                    {{ $compras->total() === 1 ? 'compra encontrada' : 'compras encontradas' }}
                </div>
            </div>
        </div>

        @foreach($gruposPorData as $data => $comprasDoDia)
            @php
                $dataEntrada = $comprasDoDia->first()->data_entrada;
                $valorTotalGrupo = $comprasDoDia->sum('valor_total');
            @endphp

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-lg-5">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-calendar3 text-primary fs-4"></i>

                                <div>
                                    <div class="fw-bold fs-5">
                                        {{ $dataEntrada?->format('d/m/Y') ?? 'Data não informada' }}
                                    </div>

                                    <div class="small text-muted">
                                        {{ $comprasDoDia->count() }}
                                        {{ $comprasDoDia->count() === 1 ? 'compra' : 'compras' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-7">
                            <div class="row g-2 text-lg-end">
                                <div class="col-6">
                                    <div class="small text-muted">Valor total</div>
                                    <div class="fw-bold">
                                        R$ {{ number_format($valorTotalGrupo, 2, ',', '.') }}
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="small text-muted">Aprovadas</div>
                                    <div class="fw-bold text-success">
                                        {{ $comprasDoDia->where('status', 'aprovada')->count() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">ID</th>
                                <th>NF</th>
                                <th>Fornecedor</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($comprasDoDia as $compra)
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
                                    <td class="ps-3">
                                        #{{ $compra->id }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold">
                                            {{ $compra->numero_nf }}
                                        </div>

                                        @if($compra->serie_nf)
                                            <div class="small text-muted">
                                                Série {{ $compra->serie_nf }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        @if($compra->fornecedor)
                                            {{ $compra->fornecedor->nome }}
                                        @else
                                            <span class="text-muted">Não informado</span>
                                        @endif
                                    </td>

                                    <td class="fw-semibold">
                                        R$ {{ number_format($compra->valor_total, 2, ',', '.') }}
                                    </td>

                                    <td>
                                        <span class="badge {{ $statusConfig['class'] }}">
                                            <i class="bi {{ $statusConfig['icon'] }}"></i>
                                            {{ $statusConfig['label'] }}
                                        </span>
                                    </td>

                                    <td class="text-end pe-3">
                                        <div class="btn-group" role="group">
                                            <a
                                                href="{{ route('compras.show', $compra) }}"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Visualizar"
                                            >
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            @if(in_array($compra->status, ['pendente', 'conferindo'], true))
                                                <a
                                                    href="{{ route('compras.edit', $compra) }}"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    title="Editar"
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
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Excluir"
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
            </div>
        @endforeach

        <div class="mt-4">
            {{ $compras->links() }}
        </div>
    @endif
</div>
@endsection
