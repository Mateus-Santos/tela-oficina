@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <x-list-header title="PRÉ-CONCILIAÇÃO FINANCEIRA D03A" icon="bi-clipboard-check" />
    <div class="alert alert-warning" role="alert">
        Controle de proposta: esta tela <strong>não efetua abatimento, crédito, reembolso, baixa de boleto,
        estorno de pagamento nem emissão de NF-e</strong>. A efetivação e a conciliação documental serão feitas
        na etapa D03B após homologação.
    </div>
    <a class="btn btn-outline-secondary mb-3" href="{{ route('compras.show', $compra) }}">
        Voltar à compra #{{ $compra->id }}
    </a>
    @if (session('success')) <div class="alert alert-success" role="status">{{ session('success') }}</div> @endif
    @if (session('error')) <div class="alert alert-danger" role="alert">{{ session('error') }}</div> @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card mb-3"><div class="card-body">
        <h2 class="h5">Compra #{{ $compra->id }} — {{ $compra->fornecedor?->nome }}</h2>
        <p class="mb-1">NF de compra: {{ $compra->numero_nf }}</p>
        <p class="mb-1">Valor original da compra: <strong>R$ {{ number_format((float) $previsao['valor_compra'], 2, ',', '.') }}</strong></p>
        <p class="mb-0">Divergência: {{ $previsao['possui_divergencia'] ? 'Sim' : 'Não' }} · Devolução: {{ $previsao['possui_devolucao'] ? 'Sim' : 'Não' }}</p>
    </div></div>

    <div class="card mb-3"><div class="card-body">
        <h2 class="h5">Conferência e devoluções por item</h2>
        <div class="table-responsive"><table class="table table-sm align-middle">
            <thead><tr><th>Item</th><th>NF</th><th>Recebida</th><th>Aceita</th><th>Devolvida</th></tr></thead>
            <tbody>@foreach ($previsao['itens'] as $item)
                <tr>
                    <td>{{ $item['descricao'] }}</td>
                    <td>{{ $item['quantidade_nf'] }}</td>
                    <td>{{ $item['quantidade_recebida'] ?? 'Sem conferência' }}</td>
                    <td>{{ $item['quantidade_aceita'] ?? 'Não apurada' }}</td>
                    <td>{{ $item['quantidade_devolvida'] }}</td>
                </tr>
            @endforeach</tbody>
        </table></div>
    </div></div>

    <div class="card mb-3"><div class="card-body">
        <h2 class="h5">Contas a pagar relacionadas</h2>
        @forelse ($previsao['contas'] as $conta)
            <div class="border rounded p-3 mb-2">
                <strong>Conta #{{ $conta['id'] }}</strong> · {{ $conta['status'] }}
                <div>Valor da conta: R$ {{ number_format((float) $conta['valor'], 2, ',', '.') }}</div>
                <div>Pagamentos ativos desta conta: R$ {{ number_format((float) $conta['pago_total_conta'], 2, ',', '.') }}</div>
                @if ($conta['agrupada'])
                    <div class="text-danger small">Conta agrupada: os pagamentos indicados são da conta inteira, não exclusivamente desta compra.</div>
                @endif
            </div>
        @empty
            <p class="text-muted">Nenhuma conta a pagar vinculada.</p>
        @endforelse
        <p class="small text-muted mb-0">Os valores acima são históricos, não constituem autorização para compensação ou reembolso.</p>
    </div></div>

    @if ($compra->estaAprovada() && ($previsao['possui_divergencia'] || $previsao['possui_devolucao']))
        <div class="card mb-4"><div class="card-body">
            <h2 class="h5">Proposta de tratamento — somente rascunho</h2>
            <p class="text-muted small">Informe o valor negociado/documentado com o fornecedor. O sistema não calcula automaticamente impostos, frete, descontos ou o crédito devido.</p>
            <form method="POST" action="{{ route('compras.preconciliacao.store', $compra) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="tratamento_proposto">Tratamento pretendido</label>
                        <select class="form-select" id="tratamento_proposto" name="tratamento_proposto" required>
                            @foreach (['abatimento' => 'Abatimento em conta a pagar', 'credito' => 'Crédito com fornecedor', 'reembolso' => 'Reembolso a receber', 'sem_ajuste' => 'Sem ajuste financeiro'] as $valor => $rotulo)
                                <option value="{{ $valor }}" @selected(old('tratamento_proposto', $registro?->tratamento_proposto) === $valor)>{{ $rotulo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="valor_proposto">Valor proposto (R$)</label>
                        <input class="form-control" type="number" id="valor_proposto" name="valor_proposto"
                            min="0" step="0.01" max="{{ $compra->valor_total }}" required
                            value="{{ old('valor_proposto', $registro?->valor_proposto ?? '0.00') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="referencia_documento">Referência do documento / negociação</label>
                        <input class="form-control" type="text" id="referencia_documento" name="referencia_documento"
                            maxlength="100" value="{{ old('referencia_documento', $registro?->referencia_documento) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="justificativa">Justificativa (mínimo 10 caracteres)</label>
                        <textarea class="form-control" id="justificativa" name="justificativa" rows="4"
                            required minlength="10" maxlength="2000">{{ old('justificativa', $registro?->justificativa) }}</textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Salvar rascunho de pré-conciliação</button>
            </form>
            <p class="small text-muted mt-2 mb-0">Estado: {{ $registro?->status ?? 'não iniciado' }}. Não movimenta valores.</p>
        </div></div>
    @else
        <div class="alert alert-info">Pré-conciliação disponível apenas para compras aprovadas com devolução ou divergência.</div>
    @endif
</div>
@endsection
