@extends('layouts.layout')

@section('content')

<section class="container cadastro">

<h1><i class="bi bi-cash-stack"></i> CONTAS A RECEBER</h1>

{{-- Mensagens --}}

@if ($errors->any())
    <div class="alert alert-danger mensseger_error_container">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

{{-- Filtros --}}

<div class="filtros-container mb-4">

<form
    method="GET"
    action="{{ route('contas-receber.index') }}"
    class="row g-2 align-items-end"
>

    {{-- Cliente --}}

    <div class="col-md-4">
        <label for="cliente" class="form-label">
            <i class="bi bi-person"></i> Cliente
        </label>

        <input
            type="text"
            id="cliente"
            name="cliente"
            class="form-control"
            placeholder="Nome do cliente..."
            value="{{ request('cliente') }}"
        >
    </div>

    {{-- Status --}}

    <div class="col-md-2">
        <label for="status" class="form-label">
            <i class="bi bi-info-circle"></i> Status
        </label>

        <select
            id="status"
            name="status"
            class="form-select"
        >
            <option value="">Todos</option>

            <option value="aberta" {{ request('status') == 'aberta' ? 'selected' : '' }}>
                Aberta
            </option>

            <option value="parcial" {{ request('status') == 'parcial' ? 'selected' : '' }}>
                Parcial
            </option>

            <option value="quitada" {{ request('status') == 'quitada' ? 'selected' : '' }}>
                Quitada
            </option>

            <option value="vencida" {{ request('status') == 'vencida' ? 'selected' : '' }}>
                Vencida
            </option>

            <option value="cancelada" {{ request('status') == 'cancelada' ? 'selected' : '' }}>
                Cancelada
            </option>
        </select>
    </div>

    {{-- Nota --}}

    <div class="col-md-2">
        <label for="nota_id" class="form-label">
            <i class="bi bi-receipt"></i> Nota
        </label>

        <input
            type="number"
            id="nota_id"
            name="nota_id"
            class="form-control"
            placeholder="Nº da nota"
            value="{{ request('nota_id') }}"
            min="1"
        >
    </div>

    {{-- Data inicial --}}

    <div class="col-md-2">
        <label for="data_inicio" class="form-label">
            <i class="bi bi-calendar-event"></i> Vencimento de
        </label>

        <input
            type="date"
            id="data_inicio"
            name="data_inicio"
            class="form-control"
            value="{{ request('data_inicio') }}"
        >
    </div>

    {{-- Data final --}}

    <div class="col-md-2">
        <label for="data_fim" class="form-label">
            <i class="bi bi-calendar-event"></i> Vencimento até
        </label>

        <input
            type="date"
            id="data_fim"
            name="data_fim"
            class="form-control"
            value="{{ request('data_fim') }}"
        >
    </div>

    {{-- Botões --}}

    <div class="col-12 d-flex justify-content-end gap-2 mt-2">

        <a
            href="{{ route('contas-receber.index') }}"
            class="btn btn-secondary"
        >
            <i class="bi bi-x-circle"></i>
            Limpar Filtros
        </a>

        <button
            type="submit"
            class="btn btn-warning"
        >
            <i class="bi bi-funnel"></i>
            Filtrar
        </button>

        <a
            href="{{ route('contas-receber.create') }}"
            class="btn btn-success"
        >
            <i class="bi bi-plus-circle"></i>
            Nova Conta
        </a>

    </div>

</form>


</div>


{{-- Tabela --}}

<div class="table-responsive">

    <table class="table table-striped table-hover align-middle">

        <thead>

            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Descrição</th>
                <th>Nota</th>
                <th>Vencimento</th>
                <th>Valor</th>
                <th>Recebido</th>
                <th>Saldo</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>

        </thead>

        <tbody>

            @forelse ($contasReceber as $contaReceber)

                @php
                    $valorDevido = (float) $contaReceber->valor_original
                        - (float) $contaReceber->desconto
                        + (float) $contaReceber->juros
                        + (float) $contaReceber->multa;

                    $valorRecebido = (float) ($contaReceber->recebimentos_sum_valor ?? 0);
                    $saldo = $valorDevido - $valorRecebido;
                @endphp

                <tr>

                    <td>
                        {{ str_pad($contaReceber->id, 6, '0', STR_PAD_LEFT) }}
                    </td>

                    <td>
                        {{ $contaReceber->cliente?->nome ?? 'Sem cliente' }}
                    </td>

                    <td>
                        {{ $contaReceber->descricao }}
                    </td>

                    <td>

                        @if ($contaReceber->nota)

                            #{{ str_pad($contaReceber->nota->id, 6, '0', STR_PAD_LEFT) }}

                        @else

                            <span class="text-muted">Sem nota</span>

                        @endif

                    </td>

                    <td>
                        {{ $contaReceber->data_vencimento->format('d/m/Y') }}
                    </td>

                    <td>
                        R$ {{ number_format($valorDevido, 2, ',', '.') }}
                    </td>

                    <td>
                        R$ {{ number_format($valorRecebido, 2, ',', '.') }}
                    </td>

                    <td>
                        R$ {{ number_format(max($saldo, 0), 2, ',', '.') }}
                    </td>

                    <td>

                        @if ($contaReceber->estaVencida())

                            <span class="badge bg-danger">
                                <i class="bi bi-exclamation-circle"></i> Vencida
                            </span>

                        @elseif ($contaReceber->status === 'quitada')

                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i> Quitada
                            </span>

                        @elseif ($contaReceber->status === 'parcial')

                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-hourglass-split"></i> Parcial
                            </span>

                        @elseif ($contaReceber->status === 'cancelada')

                            <span class="badge bg-secondary">
                                <i class="bi bi-x-circle"></i> Cancelada
                            </span>

                        @else

                            <span class="badge bg-primary">
                                <i class="bi bi-clock"></i> Aberta
                            </span>

                        @endif

                    </td>

                    <td>

                        <div class="d-flex gap-1">

                            <a
                                href="{{ route('contas-receber.show', $contaReceber) }}"
                                class="btn btn-primary btn-sm"
                                title="Visualizar"
                            >
                                <i class="bi bi-eye"></i>
                            </a>

                            @if (!$contaReceber->recebimentos_exists && $contaReceber->status !== 'cancelada')

                                <a
                                    href="{{ route('contas-receber.edit', $contaReceber) }}"
                                    class="btn btn-warning btn-sm"
                                    title="Editar"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>

                            @endif

                            @if (
                                !$contaReceber->estaVencida()
                                && $contaReceber->status !== 'quitada'
                                && $contaReceber->status !== 'cancelada'
                            )

                                <a
                                    href="{{ route('recebimentos.create', $contaReceber) }}"
                                    class="btn btn-success btn-sm"
                                    title="Registrar recebimento"
                                >
                                    <i class="bi bi-cash-coin"></i>
                                </a>

                            @elseif ($contaReceber->estaVencida())

                                <a
                                    href="{{ route('recebimentos.create', $contaReceber) }}"
                                    class="btn btn-danger btn-sm"
                                    title="Registrar recebimento de conta vencida"
                                >
                                    <i class="bi bi-cash-coin"></i>
                                </a>

                            @endif

                            @if (!$contaReceber->recebimentos_exists)

                                <form
                                    action="{{ route('contas-receber.destroy', $contaReceber) }}"
                                    method="POST"
                                    onsubmit="return confirm('Tem certeza que deseja excluir esta conta a receber?');"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn btn-danger btn-sm"
                                        title="Excluir"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>

                                </form>

                            @endif

                        </div>

                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="10" class="text-center">
                        Nenhuma conta a receber encontrada.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>

</div>

{{-- Paginação --}}

<div class="d-flex justify-content-center mt-4">
    {{ $contasReceber->links() }}
</div>


</section>

@endsection
