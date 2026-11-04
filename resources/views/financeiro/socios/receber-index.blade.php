@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <x-list-header :title="'CONTAS A RECEBER — ' . $socio->nome" icon="bi-cash-coin" />

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 text-start">
        <p class="text-muted mb-0">Empréstimos e valores pessoais que terceiros devem a este sócio.</p>
        <a href="{{ route('socios-financeiro.show', $socio) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Voltar ao sócio
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success text-start">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger text-start">
            <strong>Confira os campos:</strong>
            <ul class="mb-0">@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
        </div>
    @endif

    @php
        $total = $socio->recebiveis->sum(fn ($r) => (float) $r->valor);
        $recebido = $socio->recebiveis->sum(fn ($r) => $r->valor_recebido);
        $saldo = $socio->recebiveis->sum(fn ($r) => $r->saldo);
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body text-start"><div class="small text-muted">Total emprestado</div><div class="fs-4 fw-bold">R$ {{ number_format($total,2,',','.') }}</div></div></div></div>
        <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body text-start"><div class="small text-muted">Recebido</div><div class="fs-4 fw-bold text-success">R$ {{ number_format($recebido,2,',','.') }}</div></div></div></div>
        <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body text-start"><div class="small text-muted">Ainda a receber</div><div class="fs-4 fw-bold text-danger">R$ {{ number_format($saldo,2,',','.') }}</div></div></div></div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header fw-bold text-start">Cadastrar empréstimo / conta pessoal a receber</div>
        <div class="card-body text-start">
            <form method="POST" action="{{ route('socios-financeiro.receber.store', $socio) }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label" for="receber_devedor">Quem deve?</label>
                    <input class="form-control" id="receber_devedor" name="devedor" maxlength="180" required value="{{ old('devedor') }}" placeholder="Nome do devedor">
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="receber_descricao">Descrição</label>
                    <input class="form-control" id="receber_descricao" name="descricao" maxlength="255" required value="{{ old('descricao') }}" placeholder="Ex.: empréstimo, compra no cartão">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="receber_origem">Como foi emprestado?</label>
                    <select class="form-select" id="receber_origem" name="origem" required>
                        @foreach(['dinheiro' => 'Dinheiro', 'pix' => 'Pix', 'cartao' => 'Cartão de crédito', 'transferencia' => 'Transferência', 'outros' => 'Outros'] as $valor => $rotulo)
                            <option value="{{ $valor }}" @selected(old('origem','dinheiro') === $valor)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="receber_cartao">Identificação do cartão (quando aplicável)</label>
                    <input class="form-control" id="receber_cartao" name="identificacao_cartao" maxlength="120" value="{{ old('identificacao_cartao') }}" placeholder="Ex.: Nubank final 1234">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="receber_valor">Valor (R$)</label>
                    <input class="form-control" id="receber_valor" name="valor" type="number" min="0.01" step="0.01" required value="{{ old('valor') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="receber_emissao">Data da operação</label>
                    <input class="form-control" id="receber_emissao" name="data_operacao" type="date" required value="{{ old('data_operacao', today()->toDateString()) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="receber_vencimento">1º vencimento</label>
                    <input class="form-control" id="receber_vencimento" name="primeiro_vencimento" type="date" required value="{{ old('primeiro_vencimento') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="receber_parcelas">Nº parcelas</label>
                    <input class="form-control" id="receber_parcelas" name="quantidade_parcelas" type="number" min="1" max="120" required value="{{ old('quantidade_parcelas', 1) }}">
                </div>
                <div class="col-md-9">
                    <label class="form-label" for="receber_obs">Observações</label>
                    <input class="form-control" id="receber_obs" name="observacoes" maxlength="5000" value="{{ old('observacoes') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i> Cadastrar conta</button>
                </div>
            </form>
            <p class="small text-muted mt-3 mb-0">Os vencimentos são mensais, e os centavos são distribuídos entre as parcelas. O pagamento por terceiros não quita automaticamente a fatura do cartão do titular.</p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header fw-bold text-start">Contas pessoais a receber</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle text-start mb-0">
                <thead><tr><th>Devedor</th><th>Descrição</th><th>Origem</th><th>Total</th><th>Recebido</th><th>Saldo</th><th>Situação</th><th></th></tr></thead>
                <tbody>
                @forelse($socio->recebiveis as $recebivel)
                    <tr>
                        <td>{{ $recebivel->devedor }}</td>
                        <td>{{ $recebivel->descricao }}</td>
                        <td>{{ ucfirst($recebivel->origem) }} @if($recebivel->identificacao_cartao)<small class="text-muted">({{ $recebivel->identificacao_cartao }})</small>@endif</td>
                        <td>R$ {{ number_format((float)$recebivel->valor,2,',','.') }}</td>
                        <td>R$ {{ number_format($recebivel->valor_recebido,2,',','.') }}</td>
                        <td>R$ {{ number_format($recebivel->saldo,2,',','.') }}</td>
                        <td>{{ ucfirst($recebivel->status) }}</td>
                        <td><a href="{{ route('socios-financeiro.receber.show', [$socio, $recebivel]) }}" class="btn btn-outline-primary btn-sm">Parcelas / receber</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Nenhuma conta pessoal a receber cadastrada.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
