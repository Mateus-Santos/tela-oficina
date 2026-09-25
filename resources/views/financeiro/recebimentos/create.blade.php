@extends('layouts.layout')

@section('content')
<section class="container cadastro">
    <h1><i class="bi bi-wallet2"></i> REGISTRAR RECEBIMENTO</h1>

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

    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-receipt"></i> Dados da Conta e Parcela</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <strong>Conta:</strong><br>
                    #{{ str_pad($contaReceber->id, 6, '0', STR_PAD_LEFT) }}
                </div>

                <div class="col-md-5 mb-3">
                    <strong>Cliente:</strong><br>
                    {{ $contaReceber->cliente?->pessoa?->nome ?? $contaReceber->nota?->cliente?->pessoa?->nome ?? 'Sem cliente' }}
                </div>

                <div class="col-md-4 mb-3">
                    <strong>Nota:</strong><br>
                    @if ($contaReceber->nota)
                        #{{ str_pad($contaReceber->nota->id, 6, '0', STR_PAD_LEFT) }}
                    @else
                        Sem nota vinculada
                    @endif
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Descrição:</strong><br>
                    {{ $contaReceber->descricao }}
                </div>

                <div class="col-md-3 mb-3">
                    <strong>Parcela:</strong><br>
                    {{ $parcela->numero }}/{{ $contaReceber->parcelas()->count() }}
                </div>

                <div class="col-md-3 mb-3">
                    <strong>Vencimento:</strong><br>
                    {{ $parcela->data_vencimento?->format('d/m/Y') ?? '-' }}
                </div>

                <div class="col-md-4 mb-3">
                    <strong>Valor da Parcela:</strong><br>
                    R$ {{ number_format((float) $parcela->valor, 2, ',', '.') }}
                </div>

                <div class="col-md-4 mb-3">
                    <strong>Já Recebido:</strong><br>
                    R$ {{ number_format($valorRecebido, 2, ',', '.') }}
                </div>

                <div class="col-md-4 mb-3">
                    <strong>Saldo da Parcela:</strong><br>
                    <span class="fs-5 fw-bold">R$ {{ number_format($saldo, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('recebimentos.store') }}" method="POST" class="row g-3">
        @csrf
        <input type="hidden" name="conta_receber_id" value="{{ $contaReceber->id }}">
        <input type="hidden" name="parcela_conta_receber_id" value="{{ $parcela->id }}">

        <div class="campos">
            <div class="row mb-3">
                <div class="col-md-5">
                    <label class="form-label" for="forma_pagamento_id"><i class="bi bi-credit-card"></i> Forma de Pagamento:*</label>
                    <select class="form-control" id="forma_pagamento_id" name="forma_pagamento_id" required>
                        <option value="">Selecione uma forma de pagamento</option>
                        @foreach ($formasPagamento as $formaPagamento)
                            <option value="{{ $formaPagamento->id }}" {{ old('forma_pagamento_id') == $formaPagamento->id ? 'selected' : '' }}>
                                {{ $formaPagamento->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="valor"><i class="bi bi-currency-dollar"></i> Valor Recebido (R$):*</label>
                    <input type="number" step="0.01" min="0.01" max="{{ number_format($saldo, 2, '.', '') }}" class="form-control" id="valor" name="valor" value="{{ old('valor', number_format($saldo, 2, '.', '')) }}" required>
                    <small class="text-muted">Saldo disponível: R$ {{ number_format($saldo, 2, ',', '.') }}</small>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label" for="data_pagamento"><i class="bi bi-calendar-event"></i> Data do Pagamento:*</label>
                    <input type="datetime-local" class="form-control" id="data_pagamento" name="data_pagamento" value="{{ old('data_pagamento', now()->format('Y-m-d\TH:i')) }}" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-12">
                    <label class="form-label" for="observacoes"><i class="bi bi-chat-left-text"></i> Observações:</label>
                    <textarea class="form-control" id="observacoes" name="observacoes" rows="4">{{ old('observacoes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="col text-center">
            <a href="{{ route('contas-receber.show', $contaReceber) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-circle"></i> Registrar Recebimento
            </button>
        </div>
    </form>
</section>
@endsection
