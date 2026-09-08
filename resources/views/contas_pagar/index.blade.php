@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <x-list-header
        title="LISTAR CONTAS A PAGAR"
        icon="bi-wallet2"
        create-route="contas-pagar.create"
        create-text="Nova Conta"
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

    @if ($errors->any())
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Não foi possível realizar a operação.</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-filtros-container
        action="{{ route('contas-pagar.index') }}"
        id="filtros-contas-pagar"
        :collapsible="true"
        :expanded="request()->filled('data_emissao_inicio') || request()->filled('data_emissao_fim') || request()->filled('data_vencimento_inicio') || request()->filled('data_vencimento_fim')"
    >
        <x-slot name="primary">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="descricao" class="form-label">
                        <i class="bi bi-search"></i>
                        Descrição
                    </label>
                    <input
                        type="text"
                        name="descricao"
                        id="descricao"
                        class="filtros-container__input"
                        value="{{ request('descricao') }}"
                        placeholder="Descrição da conta"
                    >
                </div>

                <div class="col-12 col-md-4">
                    <label for="fornecedor_id" class="form-label">
                        <i class="bi bi-building"></i>
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
                        <option value="aberta" @selected(request('status') === 'aberta')>
                            Aberta
                        </option>
                        <option value="parcialmente_paga" @selected(request('status') === 'parcialmente_paga')>
                            Parcialmente paga
                        </option>
                        <option value="paga" @selected(request('status') === 'paga')>
                            Paga
                        </option>
                        <option value="vencida" @selected(request('status') === 'vencida')>
                            Vencida
                        </option>
                        <option value="cancelada" @selected(request('status') === 'cancelada')>
                            Cancelada
                        </option>
                    </select>
                </div>

                <div class="col-12">
                    <div class="filtros-container__actions">
                        <button
                            type="submit"
                            class="btn btn-primary"
                            title="Filtrar contas"
                        >
                            <i class="bi bi-search"></i>
                            Filtrar
                        </button>

                        <a
                            href="{{ route('contas-pagar.index') }}"
                            class="btn btn-secondary"
                            title="Limpar filtros"
                        >
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
        </x-slot>

        <x-slot name="advanced">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label for="data_emissao_inicio" class="form-label">
                        <i class="bi bi-calendar-event"></i>
                        Emissão inicial
                    </label>
                    <input
                        type="date"
                        name="data_emissao_inicio"
                        id="data_emissao_inicio"
                        class="filtros-container__input"
                        value="{{ request('data_emissao_inicio') }}"
                    >
                </div>

                <div class="col-12 col-md-3">
                    <label for="data_emissao_fim" class="form-label">
                        <i class="bi bi-calendar-check"></i>
                        Emissão final
                    </label>
                    <input
                        type="date"
                        name="data_emissao_fim"
                        id="data_emissao_fim"
                        class="filtros-container__input"
                        value="{{ request('data_emissao_fim') }}"
                    >
                </div>

                <div class="col-12 col-md-3">
                    <label for="data_vencimento_inicio" class="form-label">
                        <i class="bi bi-calendar-event"></i>
                        Vencimento inicial
                    </label>
                    <input
                        type="date"
                        name="data_vencimento_inicio"
                        id="data_vencimento_inicio"
                        class="filtros-container__input"
                        value="{{ request('data_vencimento_inicio') }}"
                    >
                </div>

                <div class="col-12 col-md-3">
                    <label for="data_vencimento_fim" class="form-label">
                        <i class="bi bi-calendar-check"></i>
                        Vencimento final
                    </label>
                    <input
                        type="date"
                        name="data_vencimento_fim"
                        id="data_vencimento_fim"
                        class="filtros-container__input"
                        value="{{ request('data_vencimento_fim') }}"
                    >
                </div>
            </div>
        </x-slot>
    </x-filtros-container>

    @php
        $possuiFiltros =
            request()->filled('descricao') ||
            request()->filled('fornecedor_id') ||
            request()->filled('status') ||
            request()->filled('data_emissao_inicio') ||
            request()->filled('data_emissao_fim') ||
            request()->filled('data_vencimento_inicio') ||
            request()->filled('data_vencimento_fim');
    @endphp

    @if ($contas->isEmpty())
        <div class="alert alert-{{ $possuiFiltros ? 'warning' : 'info' }}">
            <i class="bi {{ $possuiFiltros ? 'bi-exclamation-triangle' : 'bi-info-circle' }}"></i>
            {{ $possuiFiltros
                ? 'Nenhuma conta a pagar encontrada com os filtros informados.'
                : 'Nenhuma conta a pagar cadastrada.' }}
        </div>
    @endif

    @if ($contas->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">DESCRIÇÃO</th>
                        <th scope="col">FORNECEDOR</th>
                        <th scope="col">VENCIMENTO</th>
                        <th scope="col">VALOR</th>
                        <th scope="col">PAGO</th>
                        <th scope="col">SALDO</th>
                        <th scope="col">STATUS</th>
                        <th scope="col">AÇÕES</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($contas as $conta)
                        @php
                            $valorPago = (float) ($conta->valor_pago ?? 0);
                            $saldo = max(0, round((float) $conta->valor - $valorPago, 2));

                            if ($conta->status === 'cancelada') {
                                $statusConfig = [
                                    'class' => 'bg-danger',
                                    'icon' => 'bi-x-circle',
                                    'label' => 'Cancelada',
                                ];
                            } elseif ($saldo <= 0) {
                                $statusConfig = [
                                    'class' => 'bg-success',
                                    'icon' => 'bi-check-circle',
                                    'label' => 'Paga',
                                ];
                            } elseif ($conta->data_vencimento->isBefore(today())) {
                                $statusConfig = [
                                    'class' => 'bg-danger',
                                    'icon' => 'bi-exclamation-circle',
                                    'label' => 'Vencida',
                                ];
                            } elseif ($valorPago > 0) {
                                $statusConfig = [
                                    'class' => 'bg-info text-dark',
                                    'icon' => 'bi-hourglass-split',
                                    'label' => 'Parcialmente paga',
                                ];
                            } else {
                                $statusConfig = [
                                    'class' => 'bg-warning text-dark',
                                    'icon' => 'bi-clock',
                                    'label' => 'Aberta',
                                ];
                            }
                        @endphp

                        <tr>
                            <td>{{ $conta->id }}</td>

                            <td>
                                <strong>{{ $conta->descricao }}</strong>

                                @if ($conta->nota_id)
                                    <br>
                                    <small class="text-muted">
                                        <i class="bi bi-receipt"></i>
                                        Nota #{{ $conta->nota_id }}
                                    </small>
                                @endif
                            </td>

                            <td>
                                {{ $conta->fornecedor->nome ?? 'Não informado' }}
                            </td>

                            <td>
                                {{ $conta->data_vencimento?->format('d/m/Y') ?? '-' }}

                                @if (
                                    $conta->status !== 'cancelada' &&
                                    $saldo > 0 &&
                                    $conta->data_vencimento->isBefore(today())
                                )
                                    <br>
                                    <small class="text-danger">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        Em atraso
                                    </small>
                                @endif
                            </td>

                            <td>
                                <strong>
                                    R$ {{ number_format((float) $conta->valor, 2, ',', '.') }}
                                </strong>
                            </td>

                            <td>
                                R$ {{ number_format($valorPago, 2, ',', '.') }}
                            </td>

                            <td>
                                <strong>
                                    R$ {{ number_format($saldo, 2, ',', '.') }}
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
                                        href="{{ route('contas-pagar.show', $conta) }}"
                                        class="btn btn-success btn-sm"
                                        title="Visualizar conta"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    @if ($conta->status !== 'cancelada')
                                        <a
                                            href="{{ route('contas-pagar.edit', $conta) }}"
                                            class="btn btn-primary btn-sm"
                                            title="Editar conta"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif

                                    @if ($conta->status !== 'cancelada' && $saldo > 0)
                                        <a
                                            href="{{ route('contas-pagar.show', $conta) }}#registrar-pagamento"
                                            class="btn btn-warning btn-sm"
                                            title="Registrar pagamento"
                                        >
                                            <i class="bi bi-cash-coin"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($contas->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $contas->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
