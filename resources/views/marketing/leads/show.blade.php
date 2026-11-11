@extends('layouts.layout')

@section('content')
<main class="container" style="padding-top: 125px; padding-bottom: 65px; min-height: 75vh;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Solicitação {{ $lead->protocolo }}</h1>
            <p class="text-secondary mb-0">Recebida em {{ $lead->created_at?->format('d/m/Y H:i') ?? '-' }} · {{ $lead->nome }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('marketing.leads.index') }}">Voltar às solicitações</a>
    </div>

    @if(session('marketing_vinculo_sucesso'))
        <div class="alert alert-success" role="status">{{ session('marketing_vinculo_sucesso') }}</div>
    @endif
    @if(session('marketing_nota_sucesso'))
        <div class="alert alert-success" role="status">{{ session('marketing_nota_sucesso') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $mensagem)<li>{{ $mensagem }}</li>@endforeach
            </ul>
        </div>
    @endif

    <section class="card mb-4" aria-label="Dados da solicitação">
        <div class="card-body">
            <p><strong>WhatsApp:</strong> {{ $lead->whatsapp }}</p>
            <p><strong>Veículo:</strong> {{ $lead->veiculo ?: 'Não informado' }} {{ $lead->ano ?: '' }} {{ $lead->motor ?: '' }}</p>
            <p><strong>Peça informada:</strong> {{ $lead->peca }}</p>
            <p><strong>Detalhes:</strong> {{ $lead->detalhes ?: 'Não informados' }}</p>
            <p><strong>Origem:</strong> {{ $lead->utm_source ?: 'Não informada' }} · {{ $lead->utm_campaign ?: '-' }}</p>
            <p class="mb-0"><strong>Status:</strong> {{ \App\Models\MarketingLead::STATUS_LABELS[$lead->status] ?? $lead->status }}</p>
        </div>
    </section>

    <section class="card mb-4" aria-label="Vínculo de cliente da solicitação">
        <div class="card-body">
            <h2 class="h5">Identificação do cliente</h2>
            <p class="small text-secondary">
                O WhatsApp, o e-mail e o nome enviados no formulário não provam titularidade.
                Confira a identidade do solicitante e o cadastro do cliente antes de vincular.
                Se o portal estiver liberado para esse cliente, a solicitação poderá aparecer imediatamente.
            </p>
            @if($lead->cliente_id)
                <p><strong>Cliente vinculado:</strong> #{{ $lead->cliente_id }} · {{ $lead->cliente?->pessoa?->nome ?: 'Cadastro sem nome disponível' }}</p>
            @else
                <p class="text-secondary">Esta solicitação ainda não está vinculada a um cadastro de cliente.</p>
            @endif
            @if($lead->nota_id || in_array($lead->status, ['descartado', 'concluido'], true))
                <p class="alert alert-warning mb-0">O vínculo não pode ser alterado após conversão em nota ou encerramento da solicitação.</p>
            @elseif($lead->cliente_id)
                <form method="POST" action="{{ route('marketing.leads.desvincular-cliente', $lead) }}">
                    @csrf
                    @method('DELETE')
                    <div class="mb-3">
                        <label class="form-label" for="mkt-desvincular-protocolo">Digite o protocolo para confirmar a desvinculação</label>
                        <input class="form-control" id="mkt-desvincular-protocolo" name="protocolo_confirmacao" maxlength="40" required autocomplete="off">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="mkt-confirmar-desvinculacao" name="confirmar_desvinculacao" value="1" required>
                        <label class="form-check-label" for="mkt-confirmar-desvinculacao">Confirmei que a desvinculação é necessária e que não existe nota associada.</label>
                    </div>
                    <button type="submit" class="btn btn-outline-danger">Desvincular cliente</button>
                </form>
            @else
                <p class="small">Consulte o <a href="{{ route('clientes.index') }}" target="_blank" rel="noopener noreferrer">cadastro de clientes</a> e confirme sua identidade por canal confiável.</p>
                <form method="POST" action="{{ route('marketing.leads.vincular-cliente', $lead) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="mkt-vinculo-cliente">ID do cliente confirmado</label>
                        <input class="form-control" id="mkt-vinculo-cliente" type="number" min="1" name="cliente_id" value="{{ old('cliente_id') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="mkt-vinculo-protocolo">Digite o protocolo da solicitação</label>
                        <input class="form-control" id="mkt-vinculo-protocolo" name="protocolo_confirmacao" maxlength="40" required autocomplete="off">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="mkt-confirmar-identidade" name="confirmar_identidade" value="1" required>
                        <label class="form-check-label" for="mkt-confirmar-identidade">Conferi a identidade e autorizei o vínculo com o cadastro correto.</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Vincular cliente confirmado</button>
                </form>
            @endif
        </div>
    </section>

    @if($lead->nota_id)
        <div class="alert alert-info">
            Solicitação vinculada à <a href="{{ route('notas.show', $lead->nota_id) }}">nota #{{ $lead->nota_id }}</a>.
            Nenhuma nova conversão pode ser realizada.
        </div>
    @else
        <section class="card" aria-label="Conferir itens e criar nota aberta">
            <div class="card-body">
                <h2 class="h5">Conferência comercial antes da nota</h2>
                <p class="text-secondary small">
                    A conversão é opcional e exclusiva da administração. Ela cria uma nota do tipo Venda,
                    com status Aberto, visível no portal do cliente escolhido. Não confirma compatibilidade,
                    não reserva estoque, não finaliza venda e não gera cobrança.
                </p>
                <p class="small">Associe somente a um cliente cuja identidade foi conferida pela equipe.
                    Consulte <a href="{{ route('clientes.index') }}" target="_blank" rel="noopener noreferrer">a lista de clientes</a> para obter seu ID.
                </p>
                @if(config('marketing.conversao_nota_habilitada', false))
                <form method="POST" action="{{ route('marketing.leads.converter-nota', $lead) }}">
                    @csrf
                    <div class="mb-3">
                        <label for="mkt-conversao-cliente" class="form-label">ID do cliente confirmado pela oficina</label>
                        <input id="mkt-conversao-cliente" type="number" name="cliente_id" min="1"
                            value="{{ old('cliente_id', $lead->cliente_id) }}" class="form-control" required>
                    </div>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Peça solicitada</th><th>Qtd.</th><th>Identificação</th><th>Preço unitário revisado (R$)</th></tr></thead>
                            <tbody>
                                @forelse($lead->itens as $item)
                                    <tr>
                                        <td>{{ $item->descricao }}</td>
                                        <td>{{ $item->quantidade }}</td>
                                        <td>
                                            @if($item->produto_id && $item->produto?->status)
                                                Produto #{{ $item->produto_id }}
                                                @if($item->produto?->codigo_fabricante)
                                                    <span class="d-block small">Ref. fabricante: {{ $item->produto->codigo_fabricante }}</span>
                                                @endif
                                            @else
                                                <strong class="text-danger">Requer identificação de produto</strong>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->produto_id && $item->produto?->status)
                                                <label class="visually-hidden" for="mkt-preco-{{ $item->id }}">Preço da peça {{ $item->descricao }}</label>
                                                <input id="mkt-preco-{{ $item->id }}" type="number" step="0.01" min="0.01" max="999999.99"
                                                    name="precos[{{ $item->id }}]" value="{{ old('precos.'.$item->id) }}"
                                                    class="form-control form-control-sm" required>
                                            @else
                                                <span class="small text-secondary">Não disponível para conversão automática</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4">Nenhum item registrado. A conversão não está disponível.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" id="mkt-confirmar-revisao" name="confirmar_revisao" value="1" class="form-check-input" required>
                        <label for="mkt-confirmar-revisao" class="form-check-label">Revisei a identidade do cliente, as peças, a compatibilidade e os preços.</label>
                    </div>
                    @if($lead->itens->isNotEmpty() && $lead->itens->every(fn ($item) => $item->produto_id && $item->produto?->status) && !in_array($lead->status, ['descartado', 'concluido'], true))
                        <button type="submit" class="btn btn-primary">Criar nota aberta vinculada</button>
                    @else
                        <p class="alert alert-warning">Conversão indisponível: existem itens sem produto identificado ou a solicitação está encerrada. Faça a revisão na oficina.</p>
                    @endif
                </form>
                @else
                    <div class="alert alert-warning" role="status">A conversão para nota está em homologação e permanece desativada.</div>
                @endif
            </div>
        </section>
    @endif
</main>
@endsection
