@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <x-list-header
        title="DETALHES DA CONTA A PAGAR"
        icon="bi-wallet2"
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

    @php
        $valor = (float) $conta->valor;
        $valorPago = (float) $conta->valor_pago;
        $saldo = max(0, round($valor - $valorPago, 2));

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

    <div class="mb-3">
        <a
            href="{{ route('contas-pagar.index') }}"
            class="btn btn-secondary"
        >
            <i class="bi bi-arrow-left"></i>
            Voltar
        </a>

        @if ($conta->status !== 'cancelada')
            <a
                href="{{ route('contas-pagar.edit', $conta) }}"
                class="btn btn-primary"
            >
                <i class="bi bi-pencil"></i>
                Editar
            </a>
        @endif
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">
                        <i class="bi bi-wallet2"></i>
                        Valor da conta
                    </small>
                    <h3 class="mt-2 mb-0">
                        R$ {{ number_format($valor, 2, ',', '.') }}
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">
                        <i class="bi bi-cash-coin"></i>
                        Total pago
                    </small>
                    <h3 class="mt-2 mb-0">
                        R$ {{ number_format($valorPago, 2, ',', '.') }}
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">
                        <i class="bi bi-hourglass-split"></i>
                        Saldo
                    </small>
                    <h3 class="mt-2 mb-0">
                        R$ {{ number_format($saldo, 2, ',', '.') }}
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-info-circle"></i>
            Dados da conta
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <strong>Descrição</strong>
                    <div>{{ $conta->descricao }}</div>
                </div>

                <div class="col-12 col-md-6">
                    <strong>Status</strong>
                    <div class="mt-1">
                        <span class="badge {{ $statusConfig['class'] }}">
                            <i class="bi {{ $statusConfig['icon'] }}"></i>
                            {{ $statusConfig['label'] }}
                        </span>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <strong>Fornecedor</strong>
                    <div>
                        {{ $conta->fornecedor->nome ?? 'Não informado' }}
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <strong>Data de emissão</strong>
                    <div>
                        {{ $conta->data_emissao?->format('d/m/Y') ?? '-' }}
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <strong>Data de vencimento</strong>
                    <div>
                        {{ $conta->data_vencimento?->format('d/m/Y') ?? '-' }}

                        @if (
                            $conta->status !== 'cancelada' &&
                            $saldo > 0 &&
                            $conta->data_vencimento->isBefore(today())
                        )
                            <span class="text-danger">
                                <i class="bi bi-exclamation-triangle"></i>
                                Em atraso
                            </span>
                        @endif
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <strong>Nota vinculada</strong>
                    <div>
                        @if ($conta->nota)
                            <i class="bi bi-receipt"></i>
                            #{{ $conta->nota->id }}
                        @else
                            Não vinculada
                        @endif
                    </div>
                </div>

                @if ($conta->observacoes)
                    <div class="col-12">
                        <strong>Observações</strong>
                        <div class="mt-1">
                            {!! nl2br(e($conta->observacoes)) !!}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($conta->status !== 'cancelada' && $saldo > 0)
        <div class="card mb-4" id="registrar-pagamento">
            <div class="card-header">
                <i class="bi bi-cash-coin"></i>
                Registrar pagamento
            </div>

            <div class="card-body">
                <form
                    method="POST"
                    action="{{ route('contas-pagar.pagamentos.store', $conta) }}"
                >
                    @csrf

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="valor_pagamento" class="form-label">
                                Valor do pagamento
                            </label>
                            <input
                                type="number"
                                name="valor"
                                id="valor_pagamento"
                                class="form-control"
                                min="0.01"
                                max="{{ number_format($saldo, 2, '.', '') }}"
                                step="0.01"
                                value="{{ old('valor', number_format($saldo, 2, '.', '')) }}"
                                required
                            >
                            <small class="text-muted">
                                Saldo disponível:
                                R$ {{ number_format($saldo, 2, ',', '.') }}
                            </small>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="data_pagamento" class="form-label">
                                Data do pagamento
                            </label>
                            <input
                                type="date"
                                name="data_pagamento"
                                id="data_pagamento"
                                class="form-control"
                                value="{{ old('data_pagamento', today()->format('Y-m-d')) }}"
                                required
                            >
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="forma_pagamento" class="form-label">
                                Forma de pagamento
                            </label>
                            <input
                                type="text"
                                name="forma_pagamento"
                                id="forma_pagamento"
                                class="form-control"
                                maxlength="100"
                                value="{{ old('forma_pagamento') }}"
                                placeholder="PIX, boleto, transferência..."
                                required
                            >
                        </div>

                        <div class="col-12">
                            <label for="observacoes_pagamento" class="form-label">
                                Observações
                            </label>
                            <textarea
                                name="observacoes"
                                id="observacoes_pagamento"
                                class="form-control"
                                rows="3"
                            >{{ old('observacoes') }}</textarea>
                        </div>

                        <div class="col-12">
                            <button
                                type="submit"
                                class="btn btn-success"
                            >
                                <i class="bi bi-cash-coin"></i>
                                Registrar pagamento
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-clock-history"></i>
            Histórico de pagamentos
        </div>

        <div class="card-body">
            @if ($conta->pagamentos->isEmpty())
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i>
                    Nenhum pagamento registrado para esta conta.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>DATA</th>
                                <th>VALOR</th>
                                <th>FORMA</th>
                                <th>STATUS</th>
                                <th>OBSERVAÇÕES</th>
                                <th>AÇÕES</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($conta->pagamentos as $pagamento)
                                <tr>
                                    <td>{{ $pagamento->id }}</td>

                                    <td>
                                        {{ $pagamento->data_pagamento?->format('d/m/Y') ?? '-' }}
                                    </td>

                                    <td>
                                        <strong>
                                            R$ {{ number_format((float) $pagamento->valor, 2, ',', '.') }}
                                        </strong>
                                    </td>

                                    <td>
                                        {{ $pagamento->forma_pagamento }}
                                    </td>

                                    <td>
                                        @if ($pagamento->estaEstornado())
                                            <span class="badge bg-danger">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                                Estornado
                                            </span>

                                            @if ($pagamento->estornado_em)
                                                <br>
                                                <small class="text-muted">
                                                    {{ $pagamento->estornado_em->format('d/m/Y H:i') }}
                                                </small>
                                            @endif
                                        @else
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i>
                                                Ativo
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if ($pagamento->observacoes)
                                            {{ $pagamento->observacoes }}
                                        @else
                                            -
                                        @endif

                                        @if ($pagamento->motivo_estorno)
                                            <br>
                                            <small class="text-danger">
                                                <strong>Motivo do estorno:</strong>
                                                {{ $pagamento->motivo_estorno }}
                                            </small>
                                        @endif
                                    </td>

                                    <td>
                                        @if (!$pagamento->estaEstornado() && $conta->status !== 'cancelada')
                                            <button
                                                type="button"
                                                class="btn btn-danger btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEstorno{{ $pagamento->id }}"
                                                title="Estornar pagamento"
                                            >
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if ($conta->status !== 'cancelada')
        <div class="card border-danger">
            <div class="card-header text-danger">
                <i class="bi bi-exclamation-triangle"></i>
                Cancelar conta
            </div>

            <div class="card-body">
                @if ($valorPago > 0)
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle"></i>
                        Esta conta possui pagamentos ativos. Para cancelá-la,
                        primeiro estorne todos os pagamentos.
                    </div>
                @else
                    <p>
                        O cancelamento não excluirá a conta nem seu histórico.
                        Informe o motivo para registrar a operação.
                    </p>

                    <form
                        method="POST"
                        action="{{ route('contas-pagar.cancelar', $conta) }}"
                        onsubmit="return confirm('Tem certeza que deseja cancelar esta conta?');"
                    >
                        @csrf

                        <div class="mb-3">
                            <label for="motivo_cancelamento" class="form-label">
                                Motivo do cancelamento
                            </label>
                            <textarea
                                name="motivo"
                                id="motivo_cancelamento"
                                class="form-control"
                                rows="3"
                                maxlength="1000"
                                required
                            ></textarea>
                        </div>

                        <button
                            type="submit"
                            class="btn btn-danger"
                        >
                            <i class="bi bi-x-circle"></i>
                            Cancelar conta
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>

@foreach ($conta->pagamentos as $pagamento)
    @if (!$pagamento->estaEstornado() && $conta->status !== 'cancelada')
        <div
            class="modal fade"
            id="modalEstorno{{ $pagamento->id }}"
            tabindex="-1"
            aria-labelledby="modalEstornoLabel{{ $pagamento->id }}"
            aria-hidden="true"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5
                            class="modal-title"
                            id="modalEstornoLabel{{ $pagamento->id }}"
                        >
                            <i class="bi bi-arrow-counterclockwise"></i>
                            Estornar pagamento
                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Fechar"
                        ></button>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('contas-pagar.pagamentos.estornar', [$conta, $pagamento]) }}"
                    >
                        @csrf

                        <div class="modal-body">
                            <p>
                                Pagamento:
                                <strong>
                                    R$ {{ number_format((float) $pagamento->valor, 2, ',', '.') }}
                                </strong>
                            </p>

                            <div class="mb-3">
                                <label
                                    for="motivo_estorno_{{ $pagamento->id }}"
                                    class="form-label"
                                >
                                    Motivo do estorno
                                </label>

                                <textarea
                                    name="motivo"
                                    id="motivo_estorno_{{ $pagamento->id }}"
                                    class="form-control"
                                    rows="4"
                                    maxlength="1000"
                                    required
                                ></textarea>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal"
                            >
                                <i class="bi bi-x-lg"></i>
                                Fechar
                            </button>

                            <button
                                type="submit"
                                class="btn btn-danger"
                            >
                                <i class="bi bi-arrow-counterclockwise"></i>
                                Confirmar estorno
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection
