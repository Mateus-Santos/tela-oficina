@extends('layouts.layout')

@section('content')
<main class="container mt-5 py-5" style="min-height: 70vh;">
    <a href="{{ route('cliente.solicitacoes.index') }}" class="btn btn-outline-secondary mb-4">Voltar às solicitações</a>
    <article class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h1 class="h3">Solicitação {{ $solicitacao->protocolo }}</h1>
            <p class="text-secondary small">Recebida em {{ $solicitacao->created_at?->format('d/m/Y H:i') ?? '-' }}</p>
            <p><strong>Situação:</strong> {{ \App\Models\MarketingLead::STATUS_LABELS[$solicitacao->status] ?? $solicitacao->status }}</p>
            <p><strong>Veículo informado:</strong> {{ $solicitacao->veiculo ?: 'Não informado' }}</p>
            <h2 class="h5 mt-4">Peças solicitadas</h2>
            @if($solicitacao->itens->isEmpty())
                <p>{{ $solicitacao->peca }}</p>
            @else
                <ul class="list-group mb-3">
                    @foreach($solicitacao->itens as $item)
                        <li class="list-group-item">{{ $item->descricao }} — Quantidade: {{ $item->quantidade }}</li>
                    @endforeach
                </ul>
            @endif
            @if($notaVisivel)
                <div class="alert alert-info">
                    A oficina vinculou uma nota à sua solicitação.
                    @if($solicitacao->nota->status === 'Aberto')
                        Ela está aberta e não representa venda finalizada nem pagamento devido.
                    @else
                        Situação da nota: {{ $solicitacao->nota->status }}.
                    @endif
                    <a href="{{ route('cliente.notas.show', $solicitacao->nota_id) }}">Consultar nota vinculada</a>
                </div>
            @else
                <p class="text-secondary">A oficina ainda não disponibilizou uma nota vinculada a este pedido.</p>
            @endif
            <p class="small text-secondary mb-0">A equipe confirma disponibilidade, compatibilidade e condições comerciais antes da conclusão.</p>
        </div>
    </article>
</main>
@endsection
