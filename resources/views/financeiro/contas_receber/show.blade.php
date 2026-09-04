@extends('layouts.layout')

@section('content')

<section class="container cadastro">


<h1>
    <i class="bi bi-cash-stack"></i>
    CONTA A RECEBER #{{ str_pad($contaReceber->id, 6, '0', STR_PAD_LEFT) }}
</h1>

{{-- Mensagens --}}
@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

{{-- Resumo financeiro --}}
<div class="row mb-4">

    <div class="col-md-3 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">
                    Valor Original
                </small>

                <h4 class="mb-0">
                    R$ {{ number_format($contaReceber->valor_original, 2, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">
                    Valor Devido
                </small>

                <h4 class="mb-0">
                    R$ {{ number_format($valorDevido, 2, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">
                    Total Recebido
                </small>

                <h4 class="mb-0">
                    R$ {{ number_format($valorRecebido, 2, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">
                    Saldo
                </small>

                <h4 class="mb-0">
                    R$ {{ number_format(max($saldo, 0), 2, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>

</div>

{{-- Dados da conta --}}
<div class="card mb-4">

    <div class="card-header">
        <i class="bi bi-info-circle"></i>
        Dados da Conta
    </div>

    <div class="card-body">

        <div class="row">

            <div class="col-md-6 mb-3">
                <strong>Cliente:</strong><br>

                {{ $contaReceber->cliente?->pessoa?->nome
                    ?? $contaReceber->nota?->cliente?->pessoa?->nome
                    ?? 'Sem cliente' }}
            </div>

            <div class="col-md-6 mb-3">
                <strong>Nota:</strong><br>

                @if ($contaReceber->nota)
                    #{{ str_pad($contaReceber->nota->id, 6, '0', STR_PAD_LEFT) }}
                @else
                    Sem nota vinculada
                @endif
            </div>

            <div class="col-md-6 mb-3">
                <strong>Categoria Financeira:</strong><br>

                {{ $contaReceber->categoriaFinanceira?->nome ?? 'Não informada' }}
            </div>

            <div class="col-md-6 mb-3">
                <strong>Descrição:</strong><br>

                {{ $contaReceber->descricao }}
            </div>

            <div class="col-md-3 mb-3">
                <strong>Emissão:</strong><br>

                {{ $contaReceber->data_emissao?->format('d/m/Y') ?? '-' }}
            </div>

            <div class="col-md-3 mb-3">
                <strong>Vencimento:</strong><br>

                {{ $contaReceber->data_vencimento?->format('d/m/Y') ?? '-' }}
            </div>

            <div class="col-md-3 mb-3">
                <strong>Desconto:</strong><br>

                R$ {{ number_format($contaReceber->desconto, 2, ',', '.') }}
            </div>

            <div class="col-md-3 mb-3">
                <strong>Juros + Multa:</strong><br>

                R$ {{ number_format(
                    $contaReceber->juros + $contaReceber->multa,
                    2,
                    ',',
                    '.'
                ) }}
            </div>

            <div class="col-12 mb-3">
                <strong>Status:</strong><br>

                @if ($contaReceber->status === 'quitada')
                    <span class="badge bg-success">
                        Quitada
                    </span>
                @elseif ($contaReceber->status === 'parcial')
                    <span class="badge bg-warning text-dark">
                        Parcial
                    </span>
                @elseif ($vencida)
                    <span class="badge bg-danger">
                        Vencida
                    </span>
                @elseif ($contaReceber->status === 'cancelada')
                    <span class="badge bg-secondary">
                        Cancelada
                    </span>
                @else
                    <span class="badge bg-primary">
                        Aberta
                    </span>
                @endif
            </div>

            @if ($contaReceber->data_quitacao)
                <div class="col-md-3 mb-3">
                    <strong>Data de Quitação:</strong><br>

                    {{ $contaReceber->data_quitacao->format('d/m/Y') }}
                </div>
            @endif

            @if ($contaReceber->observacoes)
                <div class="col-12">
                    <strong>Observações:</strong><br>

                    {!! nl2br(e($contaReceber->observacoes)) !!}
                </div>
            @endif

        </div>

    </div>

</div>

{{-- Recebimentos --}}
<div class="card mb-4">

    <div class="card-header d-flex justify-content-between align-items-center">

        <span>
            <i class="bi bi-wallet2"></i>
            Recebimentos
        </span>

        @if (!in_array($contaReceber->status, ['quitada', 'cancelada']) && $saldo > 0)
            <a
                href="{{ route('recebimentos.create', $contaReceber) }}"
                class="btn btn-success btn-sm"
            >
                <i class="bi bi-plus-circle"></i>
                Registrar Recebimento
            </a>
        @endif

    </div>

    <div class="card-body p-0">

        @if ($contaReceber->recebimentos->isEmpty())

            <div class="p-3 text-center text-muted">
                Nenhum recebimento registrado.
            </div>

        @else

            <div class="table-responsive">

                <table class="table table-striped table-hover mb-0">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Forma de Pagamento</th>
                            <th>Valor</th>
                            <th>Usuário</th>
                            <th>Observações</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($contaReceber->recebimentos as $recebimento)

                            <tr>

                                <td>
                                    #{{ str_pad($recebimento->id, 6, '0', STR_PAD_LEFT) }}
                                </td>

                                <td>
                                    {{ $recebimento->data_pagamento?->format('d/m/Y H:i') }}
                                </td>

                                <td>
                                    {{ $recebimento->formaPagamento?->nome ?? '-' }}
                                </td>

                                <td>
                                    R$ {{ number_format($recebimento->valor, 2, ',', '.') }}
                                </td>

                                <td>
                                    {{ $recebimento->usuario?->name ?? '-' }}
                                </td>

                                <td>
                                    {{ $recebimento->observacoes ?? '-' }}
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                    <tfoot>

                        <tr>
                            <th colspan="3" class="text-end">
                                Total recebido:
                            </th>

                            <th>
                                R$ {{ number_format($valorRecebido, 2, ',', '.') }}
                            </th>

                            <th colspan="2"></th>
                        </tr>

                    </tfoot>

                </table>

            </div>

        @endif

    </div>

</div>

{{-- Botões --}}
<div class="col text-center">

    <a
        href="{{ route('contas-receber.index') }}"
        class="btn btn-secondary"
    >
        <i class="bi bi-arrow-left"></i>
        Voltar
    </a>

    @if (!$contaReceber->recebimentos->count())
        <a
            href="{{ route('contas-receber.edit', $contaReceber) }}"
            class="btn btn-primary"
        >
            <i class="bi bi-pencil-square"></i>
            Editar
        </a>

        <form
            action="{{ route('contas-receber.destroy', $contaReceber) }}"
            method="POST"
            class="d-inline"
            onsubmit="return confirm('Tem certeza que deseja excluir esta conta a receber?');"
        >
            @csrf
            @method('DELETE')

            <button
                type="submit"
                class="btn btn-danger"
            >
                <i class="bi bi-trash"></i>
                Excluir
            </button>
        </form>
    @endif

</div>


</section>

@endsection
