@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <x-list-header :title="'FINANCEIRO — ' . $socio->nome" icon="bi-person-vcard" />
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('socios-financeiro.receber.index', $socio) }}" class="btn btn-success">
            <i class="bi bi-cash-coin me-1"></i> Contas a receber pessoais
        </a>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 text-start">
        <p class="text-muted mb-0">Dívidas pessoais e retiradas definitivas.</p>
        <a class="btn btn-outline-secondary" href="{{ route('socios-financeiro.index') }}"><i class="bi bi-arrow-left me-1"></i> Voltar aos sócios</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>@endif
    @php
        $saldo = $socio->contas->where('status','!=','cancelada')->sum(fn($c) => $c->saldo);
        $retiradas = $socio->retiradas->whereNull('estornado_em');
        $totalRetiradas = $retiradas->sum(fn($r) => (float)$r->valor);
        $retiradasDividas = $retiradas->where('tipo','pagamento_divida')->sum(fn($r) => (float)$r->valor);
        $remuneracao = $retiradas->where('tipo','remuneracao')->sum(fn($r) => (float)$r->valor);
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card shadow-sm"><div class="card-body text-start"><div class="text-muted small">Saldo das dívidas</div><div class="fs-4 fw-bold">R$ {{ number_format($saldo,2,',','.') }}</div></div></div></div>
        <div class="col-md-4"><div class="card shadow-sm"><div class="card-body text-start"><div class="text-muted small">Total de saídas pela oficina</div><div class="fs-4 fw-bold">R$ {{ number_format($totalRetiradas,2,',','.') }}</div></div></div></div>
        <div class="col-md-4"><div class="card shadow-sm"><div class="card-body text-start"><div class="text-muted small">Para dívidas / Remuneração</div><div class="fw-bold">R$ {{ number_format($retiradasDividas,2,',','.') }} / R$ {{ number_format($remuneracao,2,',','.') }}</div></div></div></div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header fw-bold text-start">Nova dívida pessoal</div>
        <div class="card-body text-start">
            <form method="POST" action="{{ route('socios-financeiro.contas.store',$socio) }}" class="row g-3">
                @csrf
                <div class="col-md-6"><label class="form-label" for="descricao">Descrição</label><input class="form-control" id="descricao" name="descricao" required maxlength="255" value="{{ old('descricao') }}" placeholder="Ex.: financiamento do veículo"></div>
                <div class="col-md-3"><label class="form-label" for="credor">Credor</label><input class="form-control" id="credor" name="credor" maxlength="255" value="{{ old('credor') }}"></div>
                <div class="col-md-3"><label class="form-label" for="categoria">Categoria</label><select name="categoria" id="categoria" class="form-select" required>@foreach($categorias as $categoria)<option value="{{ $categoria }}" @selected(old('categoria')===$categoria)>{{ $categoria }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label" for="valor">Valor total (R$)</label><input class="form-control" id="valor" name="valor" type="number" step="0.01" min="0.01" required value="{{ old('valor') }}"></div>
                <div class="col-md-3"><label class="form-label" for="data_emissao">Data de emissão</label><input class="form-control" id="data_emissao" name="data_emissao" type="date" required value="{{ old('data_emissao',today()->toDateString()) }}"></div>
                <div class="col-md-3"><label class="form-label" for="primeiro_vencimento">Primeiro vencimento</label><input class="form-control" id="primeiro_vencimento" name="primeiro_vencimento" type="date" required value="{{ old('primeiro_vencimento') }}"></div>
                <div class="col-md-3"><label class="form-label" for="quantidade_parcelas">Nº parcelas mensais</label><input class="form-control" id="quantidade_parcelas" name="quantidade_parcelas" type="number" min="1" max="120" required value="{{ old('quantidade_parcelas',1) }}"></div>
                <div class="col-md-9"><label class="form-label" for="observacoes">Observações</label><input class="form-control" id="observacoes" name="observacoes" value="{{ old('observacoes') }}"></div>
                <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit">Cadastrar dívida</button></div>
            </form>
            <p class="small text-muted mt-2 mb-0">As parcelas são distribuídas automaticamente em valores iguais, com vencimentos mensais. Conferir os valores reais do contrato antes de cadastrar.</p>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header fw-bold text-start">Contas e parcelas</div>
        <div class="table-responsive"><table class="table table-hover align-middle text-start mb-0">
            <thead><tr><th>Descrição</th><th>Credor</th><th>Categoria</th><th>Valor</th><th>Saldo</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($socio->contas as $conta)
            <tr>
                <td>{{ $conta->descricao }}</td><td>{{ $conta->credor ?? '—' }}</td><td>{{ $conta->categoria }}</td>
                <td>R$ {{ number_format((float)$conta->valor,2,',','.') }}</td>
                <td>R$ {{ number_format($conta->saldo,2,',','.') }}</td><td>{{ str_replace('_',' ',$conta->status) }}</td>
                <td><a class="btn btn-outline-primary btn-sm" href="{{ route('socios-financeiro.contas.show',[$socio,$conta]) }}">Parcelas / pagar</a></td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-4 text-muted">Nenhuma dívida cadastrada.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header fw-bold text-start">Registrar saída da oficina para o sócio</div>
        <div class="card-body text-start">
            <form method="POST" action="{{ route('socios-financeiro.retiradas.store',$socio) }}" class="row g-3">
                @csrf
                <div class="col-md-3"><label class="form-label" for="tipo">Natureza</label><select class="form-select" id="tipo" name="tipo" required><option value="retirada_livre">Retirada pessoal livre</option><option value="remuneracao">Remuneração (verificar duplicidade)</option></select></div>
                <div class="col-md-3"><label class="form-label" for="valor_saida">Valor (R$)</label><input class="form-control" id="valor_saida" name="valor" type="number" min="0.01" step="0.01" required></div>
                <div class="col-md-3"><label class="form-label" for="data_retirada">Data</label><input class="form-control" id="data_retirada" name="data_retirada" type="date" required value="{{ today()->toDateString() }}"></div>
                <div class="col-md-3 d-flex align-items-end"><button class="btn btn-outline-primary w-100" type="submit">Registrar saída</button></div>
                <div class="col-12"><label class="form-label" for="observacoes_saida">Observações</label><input class="form-control" id="observacoes_saida" name="observacoes" maxlength="5000"></div>
            </form>
            <div class="alert alert-warning small mt-3 mb-0">Não registre aqui novamente pagamentos de dívidas feitos pela oficina: eles geram retirada automática. Se o salário já está lançado em outro módulo, não duplique a remuneração. Este controle ainda não movimenta o caixa empresarial automaticamente.</div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header fw-bold text-start">Histórico de retiradas / remuneração</div>
        <div class="table-responsive"><table class="table table-sm align-middle text-start mb-0">
            <thead><tr><th>Data</th><th>Natureza</th><th>Valor</th><th>Situação</th><th>Observações</th><th></th></tr></thead>
            <tbody>
            @forelse($socio->retiradas as $retirada)
            <tr>
                <td>{{ $retirada->data_retirada->format('d/m/Y') }}</td>
                <td>{{ str_replace('_',' ',$retirada->tipo) }}</td>
                <td>R$ {{ number_format((float)$retirada->valor,2,',','.') }}</td>
                <td>{{ $retirada->estornado_em ? 'Estornada' : 'Ativa' }}</td>
                <td>{{ $retirada->observacoes }}</td>
                <td>
                    @if(!$retirada->estornado_em && !$retirada->pagamento_conta_pagar_socio_id)
                    <form method="POST" action="{{ route('socios-financeiro.retiradas.estornar',[$socio,$retirada]) }}" class="d-flex gap-1 align-items-center">
                        @csrf <input name="motivo" class="form-control form-control-sm" placeholder="Motivo do estorno" minlength="5" required><button class="btn btn-outline-danger btn-sm">Estornar</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty<tr><td colspan="6" class="text-center py-3 text-muted">Sem retiradas.</td></tr>@endforelse
            </tbody>
        </table></div>
    </div>
</div>
@endsection
