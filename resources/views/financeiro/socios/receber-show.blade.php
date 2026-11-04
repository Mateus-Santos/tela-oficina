@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <x-list-header :title="'A RECEBER — ' . $recebivel->devedor" icon="bi-cash-coin" />

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 text-start">
        <p class="text-muted mb-0">{{ $recebivel->descricao }} · Sócio: {{ $socio->nome }} · Origem: {{ ucfirst($recebivel->origem) }}</p>
        <a href="{{ route('socios-financeiro.receber.index', $socio) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Voltar às contas a receber
        </a>
    </div>

    @if(session('success'))<div class="alert alert-success text-start">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger text-start"><ul class="mb-0">@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>@endif

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card shadow-sm"><div class="card-body text-start"><div class="small text-muted">Total original</div><div class="fs-4 fw-bold">R$ {{ number_format((float)$recebivel->valor,2,',','.') }}</div></div></div></div>
        <div class="col-md-4"><div class="card shadow-sm"><div class="card-body text-start"><div class="small text-muted">Total recebido</div><div class="fs-4 fw-bold text-success">R$ {{ number_format($recebivel->valor_recebido,2,',','.') }}</div></div></div></div>
        <div class="col-md-4"><div class="card shadow-sm"><div class="card-body text-start"><div class="small text-muted">Saldo a receber</div><div class="fs-4 fw-bold text-danger">R$ {{ number_format($recebivel->saldo,2,',','.') }}</div></div></div></div>
    </div>

    @if($formasPagamento->isEmpty())
        <div class="alert alert-warning text-start">
            Não há formas de pagamento ativas. Cadastre ou ative uma em
            <a href="{{ route('formas-pagamento.index') }}">Cadastros → Formas de Pagamento</a>
            para registrar recebimentos pessoais.
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-header fw-bold text-start">Parcelas e recebimentos</div>
        <div class="table-responsive"><table class="table table-hover align-middle text-start mb-0">
            <thead><tr><th>Parcela</th><th>Vencimento</th><th>Valor</th><th>Recebido</th><th>Saldo</th><th>Ação</th></tr></thead>
            <tbody>
            @foreach($recebivel->parcelas as $parcela)
                <tr>
                    <td>#{{ $parcela->numero }}</td>
                    <td>{{ $parcela->data_vencimento->format('d/m/Y') }}</td>
                    <td>R$ {{ number_format((float)$parcela->valor,2,',','.') }}</td>
                    <td>R$ {{ number_format($parcela->valor_recebido,2,',','.') }}</td>
                    <td>R$ {{ number_format($parcela->saldo,2,',','.') }}</td>
                    <td>
                        @if($parcela->saldo > 0 && $recebivel->status !== 'cancelada')
                            <form method="POST" action="{{ route('socios-financeiro.receber.recebimentos.store', [$socio, $recebivel]) }}" class="row g-1 align-items-end" style="min-width:410px">
                                @csrf
                                <input type="hidden" name="parcela_id" value="{{ $parcela->id }}">
                                <div class="col-sm-3"><label class="small" for="receber_valor_{{ $parcela->id }}">Valor (R$)</label><input class="form-control form-control-sm" id="receber_valor_{{ $parcela->id }}" name="valor" type="number" min="0.01" step="0.01" max="{{ number_format($parcela->saldo, 2, '.', '') }}" value="{{ number_format($parcela->saldo, 2, '.', '') }}" required></div>
                                <div class="col-sm-3">
                                    <label class="small" for="receber_forma_{{ $parcela->id }}">Recebido por</label>
                                    <select class="form-select form-select-sm" id="receber_forma_{{ $parcela->id }}" name="forma_pagamento_id" required @disabled($formasPagamento->isEmpty())>
                                        <option value="" selected disabled>Selecione</option>
                                        @foreach($formasPagamento as $forma)
                                            <option value="{{ $forma->id }}" @selected((string) old('forma_pagamento_id') === (string) $forma->id)>{{ $forma->nome }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-3"><label class="small" for="receber_data_{{ $parcela->id }}">Data</label><input class="form-control form-control-sm" id="receber_data_{{ $parcela->id }}" name="data_recebimento" type="date" value="{{ today()->toDateString() }}" required></div>
                                <div class="col-sm-3"><button class="btn btn-primary btn-sm w-100" type="submit" @disabled($formasPagamento->isEmpty())>Receber</button></div>
                                <div class="col-12"><label class="small" for="receber_obs_{{ $parcela->id }}">Observações (opcional)</label><input class="form-control form-control-sm" id="receber_obs_{{ $parcela->id }}" name="observacoes" maxlength="5000"></div>
                            </form>
                        @else <span class="text-success">Quitada</span> @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header fw-bold text-start">Histórico de recebimentos e estornos</div>
        <div class="table-responsive"><table class="table table-sm align-middle text-start mb-0">
            <thead><tr><th>Data</th><th>Parcela</th><th>Meio</th><th>Valor</th><th>Situação</th><th>Ação</th></tr></thead>
            <tbody>
            @forelse($recebivel->recebimentos->sortByDesc('id') as $recebimento)
                <tr>
                    <td>{{ $recebimento->data_recebimento->format('d/m/Y') }}</td>
                    <td>#{{ $recebivel->parcelas->firstWhere('id', $recebimento->parcela_conta_receber_socio_id)?->numero ?? '—' }}</td>
                    <td>{{ $recebimento->forma_pagamento_nome ?: ($recebimento->formaPagamento?->nome ?: ucfirst($recebimento->meio_recebimento)) }}</td>
                    <td>R$ {{ number_format((float)$recebimento->valor,2,',','.') }}</td>
                    <td>{{ $recebimento->estornado_em ? 'Estornado' : 'Ativo' }}</td>
                    <td>
                        @if(!$recebimento->estornado_em)
                            <form method="POST" action="{{ route('socios-financeiro.receber.recebimentos.estornar', [$socio, $recebivel, $recebimento]) }}" class="d-flex gap-1 align-items-center">
                                @csrf
                                <input name="motivo" class="form-control form-control-sm" minlength="5" maxlength="1000" placeholder="Motivo do estorno" required>
                                <button class="btn btn-outline-danger btn-sm" type="submit">Estornar</button>
                            </form>
                        @else <small class="text-muted">{{ $recebimento->motivo_estorno }}</small> @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-3">Nenhum recebimento registrado.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
    <div class="alert alert-info text-start small">Recebimentos dos sócios não geram entradas no caixa empresarial. Caso a operação tenha sido feita com cartão emprestado, a fatura do titular é controlada separadamente.</div>
</div>
@endsection
