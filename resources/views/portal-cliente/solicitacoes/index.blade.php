@extends('layouts.layout')

@section('content')
<main class="container mt-5 py-5" style="min-height: 70vh;">
    <header class="mb-4">
        <span class="small text-secondary text-uppercase fw-semibold">Área do cliente</span>
        <h1 class="h2 fw-bold mt-2">Minhas solicitações de orçamento</h1>
        <p class="text-secondary">Acompanhe somente solicitações vinculadas e conferidas pela oficina. Um pedido não representa venda concluída.</p>
        <a href="{{ route('cliente.notas.index') }}" class="btn btn-outline-secondary">Minhas notas</a>
    </header>

    @if($solicitacoes->isEmpty())
        <div class="alert alert-light border" role="status">Nenhuma solicitação vinculada à sua conta.</div>
    @else
        <div class="row g-3 mb-4">
            @foreach($solicitacoes as $solicitacao)
                <div class="col-12 col-md-6">
                    <article class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h5">Solicitação {{ $solicitacao->protocolo }}</h2>
                            <p class="text-secondary small">{{ $solicitacao->created_at?->format('d/m/Y H:i') ?? '-' }}</p>
                            <p><strong>Peças:</strong> {{ $solicitacao->peca }}</p>
                            <p><strong>Situação:</strong> {{ \App\Models\MarketingLead::STATUS_LABELS[$solicitacao->status] ?? $solicitacao->status }}</p>
                            <a class="btn btn-outline-primary" href="{{ route('cliente.solicitacoes.show', $solicitacao->id) }}">Ver solicitação</a>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
        <nav aria-label="Paginação das minhas solicitações">{{ $solicitacoes->links() }}</nav>
    @endif
</main>
@endsection
