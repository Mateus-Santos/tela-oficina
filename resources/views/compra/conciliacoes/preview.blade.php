@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <x-list-header title="PRÉ-CONCILIAÇÃO FINANCEIRA D03A" icon="bi-clipboard-check" />
    <div class="alert alert-warning" role="alert">
        D03A: o rascunho não altera nenhum valor. D03B: abatimento só em conta individual aberta,
        com parcela única e sem histórico de pagamento. Crédito é registrado sem consumo automático;
        reembolso permanece como valor a receber até confirmação externa. Não há transferência bancária,
        emissão de NF-e nem estorno de pagamentos nesta tela.
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

    @if ($compra->estaAprovada() && ($previsao['possui_divergencia'] || $previsao['possui_devolucao'])
        && (!$registro || $registro->status === 'rascunho'))
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
    @elseif (!$efetivacao)
        <div class="alert alert-info">Pré-conciliação disponível apenas para compras aprovadas com devolução ou divergência.</div>
    @endif

    @if ($registro && $registro->status === 'rascunho' && !$efetivacao)
        <div class="card border-warning mb-4"><div class="card-body">
            <h2 class="h5">D03B — Confirmar conciliação financeira</h2>
            <p class="text-muted small">Tratamento: <strong>{{ $registro->tratamento_proposto }}</strong> ·
                Valor: <strong>R$ {{ number_format((float) $registro->valor_proposto, 2, ',', '.') }}</strong>.
                Esta confirmação é irreversível por esta interface. Revise fornecedor e documentos.</p>
            @if ($registro->tratamento_proposto === 'abatimento')
                <div class="alert alert-warning">Abatimento automático somente para conta aberta, de uma compra,
                    com parcela única e sem qualquer pagamento anterior. Contas agrupadas ou pagas são bloqueadas.</div>
            @elseif ($registro->tratamento_proposto === 'credito')
                <div class="alert alert-info">O crédito será registrado como direito não utilizado; nenhuma conta será baixada.</div>
            @elseif ($registro->tratamento_proposto === 'reembolso')
                <div class="alert alert-info">O reembolso será registrado como a receber; não confirma recebimento bancário.</div>
            @else
                <div class="alert alert-info">Encerra a divergência documental sem lançar valor.</div>
            @endif
            <form method="POST" action="{{ route('compras.conciliacao.efetivar', $compra) }}"
                onsubmit="return confirm('Confirmar conciliação? Esta operação poderá alterar o valor de uma conta a pagar.');">
                @csrf
                <input type="hidden" name="operacao_uuid"
                    value="{{ old('operacao_uuid', (string) \Illuminate\Support\Str::uuid()) }}">
                @if ($registro->tratamento_proposto === 'abatimento')
                    <div class="mb-3">
                        <label class="form-label" for="d03b_conta_id">Conta a pagar para abatimento *</label>
                        <select id="d03b_conta_id" name="conta_pagar_id" class="form-select" required>
                            <option value="">Selecione a conta</option>
                            @foreach ($previsao['contas'] as $conta)
                                <option value="{{ $conta['id'] }}" @selected(old('conta_pagar_id') == $conta['id'])>
                                    #{{ $conta['id'] }} · {{ $conta['status'] }} · R$ {{ number_format((float) $conta['valor'], 2, ',', '.') }}
                                    {{ $conta['agrupada'] ? '· AGRUPADA — bloqueada' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="mb-3">
                    <label for="d03b_documento" class="form-label">Documento ou acordo confirmado *</label>
                    <input id="d03b_documento" name="documento_confirmacao" type="text"
                        class="form-control" maxlength="100" required minlength="5"
                        value="{{ old('documento_confirmacao', $registro->referencia_documento) }}">
                </div>
                <div class="mb-3">
                    <label for="d03b_motivo" class="form-label">Justificativa da efetivação *</label>
                    <textarea id="d03b_motivo" name="justificativa" class="form-control" rows="3"
                        minlength="10" maxlength="2000" required>{{ old('justificativa', $registro->justificativa) }}</textarea>
                </div>
                <div class="form-check mb-3">
                    <input id="d03b_confirmacao" type="checkbox" class="form-check-input"
                        name="confirmacao" value="1" required>
                    <label for="d03b_confirmacao" class="form-check-label">Conferi o acordo com o fornecedor,
                        a conta relacionada e autorizo a operação indicada.</label>
                </div>
                <button type="submit" class="btn btn-danger">Confirmar tratamento D03B</button>
            </form>
        </div></div>
    @endif

    @if ($efetivacao)
        <div class="alert alert-success" role="status">
            <strong>D03B — Registro #{{ $efetivacao->id }}</strong> · {{ $efetivacao->status }}.
            Valor: R$ {{ number_format((float) $efetivacao->valor, 2, ',', '.') }}.
            Documento: {{ $efetivacao->documento_confirmacao }}.
            @if ($efetivacao->conta_pagar_id)
                Conta #{{ $efetivacao->conta_pagar_id }}:
                R$ {{ number_format((float) $efetivacao->valor_conta_antes, 2, ',', '.') }} →
                R$ {{ number_format((float) $efetivacao->valor_conta_depois, 2, ',', '.') }}.
            @endif
        </div>
    @endif
</div>
@endsection
