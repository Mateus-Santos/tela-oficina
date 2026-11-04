@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <x-list-header :title="$conta->descricao" icon="bi-receipt" />
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 text-start">
        <p class="text-muted mb-0">Sócio: {{ $socio->nome }} | Credor: {{ $conta->credor ?? 'Não informado' }}</p>
        <a class="btn btn-outline-secondary" href="{{ route('socios-financeiro.show',$socio) }}"><i class="bi bi-arrow-left me-1"></i> Voltar ao sócio</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>@endif
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card shadow-sm"><div class="card-body text-start"><div class="text-muted small">Valor original</div><div class="fs-4 fw-bold">R$ {{ number_format((float)$conta->valor,2,',','.') }}</div></div></div></div>
        <div class="col-md-4"><div class="card shadow-sm"><div class="card-body text-start"><div class="text-muted small">Pago</div><div class="fs-4 fw-bold text-success">R$ {{ number_format($conta->valor_pago,2,',','.') }}</div></div></div></div>
        <div class="col-md-4"><div class="card shadow-sm"><div class="card-body text-start"><div class="text-muted small">Saldo em aberto</div><div class="fs-4 fw-bold">R$ {{ number_format($conta->saldo,2,',','.') }}</div></div></div></div>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-header fw-bold text-start">Agenda de parcelas e pagamentos</div>
        <div class="table-responsive"><table class="table table-hover align-middle text-start mb-0">
            <thead><tr><th>Parcela</th><th>Vencimento</th><th>Valor</th><th>Pago</th><th>Saldo</th><th>Ação</th></tr></thead>
            <tbody>
            @foreach($conta->parcelas as $parcela)
            <tr>
                <td>#{{ $parcela->numero }}</td><td>{{ $parcela->data_vencimento->format('d/m/Y') }}</td>
                <td>R$ {{ number_format((float)$parcela->valor,2,',','.') }}</td>
                <td>R$ {{ number_format($parcela->valor_pago,2,',','.') }}</td>
                <td>R$ {{ number_format($parcela->saldo,2,',','.') }}</td>
                <td>
                    @if($parcela->saldo > 0 && $conta->status !== 'cancelada')
                    <form method="POST" action="{{ route('socios-financeiro.pagamentos.store',[$socio,$conta]) }}" class="row g-1 align-items-end" style="min-width:350px">
                        @csrf
                        <input type="hidden" name="parcela_id" value="{{ $parcela->id }}">
                        <div class="col-sm-3"><label class="small" for="valor_{{ $parcela->id }}">Valor</label><input id="valor_{{ $parcela->id }}" class="form-control form-control-sm" name="valor" type="number" min="0.01" step="0.01" max="{{ $parcela->saldo }}" value="{{ number_format($parcela->saldo,2,'.','') }}" required></div>
                        <div class="col-sm-3"><label class="small" for="origem_{{ $parcela->id }}">Origem</label><select id="origem_{{ $parcela->id }}" class="form-select form-select-sm" name="origem" required><option value="pessoal">Pessoal</option><option value="empresa">Oficina</option></select></div>
                        <div class="col-sm-3"><label class="small" for="data_{{ $parcela->id }}">Pagamento</label><input id="data_{{ $parcela->id }}" type="date" class="form-control form-control-sm" name="data_pagamento" value="{{ today()->toDateString() }}" required></div>
                        <div class="col-sm-3"><button class="btn btn-primary btn-sm w-100" type="submit">Pagar</button></div>
                        <div class="col-12"><label class="small" for="forma_{{ $parcela->id }}">Forma de pagamento (opcional)</label><select id="forma_{{ $parcela->id }}" name="forma_pagamento_id" class="form-select form-select-sm"><option value="">Não informada</option>@foreach($formas as $forma)<option value="{{ $forma->id }}">{{ $forma->nome }}</option>@endforeach</select></div>
                    </form>
                    @else <span class="text-success">Quitada</span> @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table></div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header fw-bold text-start">Histórico de pagamentos e estornos</div>
        <div class="table-responsive"><table class="table table-sm align-middle text-start mb-0">
            <thead><tr><th>Data</th><th>Parcela</th><th>Origem</th><th>Valor</th><th>Status</th><th>Ação</th></tr></thead>
            <tbody>
            @forelse($conta->pagamentos->sortByDesc('id') as $pagamento)
            <tr>
                <td>{{ $pagamento->data_pagamento->format('d/m/Y') }}</td>
                <td>#{{ $conta->parcelas->firstWhere('id',$pagamento->parcela_conta_pagar_socio_id)?->numero ?? '—' }}</td>
                <td>{{ $pagamento->origem === 'empresa' ? 'Oficina (retirada)' : 'Pessoal' }}</td>
                <td>R$ {{ number_format((float)$pagamento->valor,2,',','.') }}</td>
                <td>{{ $pagamento->estornado_em ? 'Estornado' : 'Ativo' }}</td>
                <td>
                    @if(!$pagamento->estornado_em)
                    <form method="POST" action="{{ route('socios-financeiro.pagamentos.estornar',[$socio,$conta,$pagamento]) }}" class="d-flex gap-1 align-items-center">
                        @csrf <input class="form-control form-control-sm" name="motivo" required minlength="5" placeholder="Motivo do estorno"><button class="btn btn-outline-danger btn-sm">Estornar</button>
                    </form>
                    @else <small class="text-muted">{{ $pagamento->motivo_estorno }}</small> @endif
                </td>
            </tr>
            @empty<tr><td colspan="6" class="py-3 text-center text-muted">Nenhum pagamento.</td></tr>@endforelse
            </tbody>
        </table></div>
    </div>
    <div class="alert alert-info mt-3 small">Pagamentos de origem Oficina registram retirada definitiva no histórico deste sócio. O saldo de caixa empresarial ainda não é lançado automaticamente.</div>
</div>
@endsection
