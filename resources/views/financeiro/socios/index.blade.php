@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <x-list-header title="FINANCEIRO DOS SÓCIOS" icon="bi-people-fill" />
    <p class="text-muted text-start mb-4">Dívidas pessoais, pagamentos e retiradas da oficina.</p>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>
    @endif

    @php
        $totalDividas = $socios->sum(fn($s) => $s->contas->where('status', '!=', 'cancelada')->sum(fn($c) => (float)$c->valor));
        $totalSaldos = $socios->sum(fn($s) => $s->contas->where('status', '!=', 'cancelada')->sum(fn($c) => $c->saldo));
        $totalRetiradas = $socios->sum(fn($s) => $s->retiradasAtivas->sum(fn($r) => (float)$r->valor));
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body text-start"><div class="small text-muted">Dívidas cadastradas</div><div class="fs-4 fw-bold">R$ {{ number_format($totalDividas,2,',','.') }}</div></div></div></div>
        <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body text-start"><div class="small text-muted">Saldo pessoal em aberto</div><div class="fs-4 fw-bold text-danger">R$ {{ number_format($totalSaldos,2,',','.') }}</div></div></div></div>
        <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body text-start"><div class="small text-muted">Retiradas registradas (histórico)</div><div class="fs-4 fw-bold">R$ {{ number_format($totalRetiradas,2,',','.') }}</div></div></div></div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header fw-bold text-start">Cadastrar sócio</div>
        <div class="card-body text-start">
            <form method="POST" action="{{ route('socios-financeiro.store') }}" class="row g-3">
                @csrf
                <div class="col-md-5"><label class="form-label" for="nome">Nome</label><input id="nome" name="nome" class="form-control" maxlength="150" value="{{ old('nome') }}" required></div>
                <div class="col-md-5"><label class="form-label" for="observacoes">Observações</label><input id="observacoes" name="observacoes" class="form-control" maxlength="5000" value="{{ old('observacoes') }}"></div>
                <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit">Cadastrar</button></div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header fw-bold text-start">Sócios cadastrados</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle text-start mb-0">
                <thead><tr><th>Sócio</th><th>Dívidas</th><th>Saldo</th><th>Retiradas registradas</th><th class="text-end">Acesso</th></tr></thead>
                <tbody>
                @forelse($socios as $socio)
                    <tr>
                        <td><strong>{{ $socio->nome }}</strong></td>
                        <td>{{ $socio->contas->count() }}</td>
                        <td>R$ {{ number_format($socio->contas->where('status','!=','cancelada')->sum(fn($c) => $c->saldo),2,',','.') }}</td>
                        <td>R$ {{ number_format($socio->retiradasAtivas->sum(fn($r) => (float)$r->valor),2,',','.') }}</td>
                        <td class="text-end"><a href="{{ route('socios-financeiro.show', $socio) }}" class="btn btn-outline-primary btn-sm">Abrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">Nenhum sócio cadastrado.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="alert alert-info mt-3 small">As retiradas são registradas neste módulo separadamente. Ainda não alteram automaticamente o saldo de caixa do financeiro empresarial.</div>
</div>
@endsection
