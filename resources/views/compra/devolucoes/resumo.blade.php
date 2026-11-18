<section class="card shadow-sm mb-4" aria-labelledby="devolucoes-compra-titulo">
    <div class="card-body">
        <div class="d-flex justify-content-between flex-wrap gap-2 align-items-center mb-3">
            <div>
                <h2 id="devolucoes-compra-titulo" class="h5 mb-1">Devoluções ao fornecedor</h2>
                <p class="text-body-secondary small mb-0">Controle operacional: não equivale à emissão de NF-e nem à conciliação financeira.</p>
            </div>
            @if ($compra->estaAprovada())
                <a class="btn btn-outline-danger btn-sm" href="{{ route('compras.devolucoes.create', $compra) }}">
                    Registrar devolução
                </a>
            @endif
        </div>
        @forelse ($compra->devolucoes as $devolucao)
            <div class="border rounded p-3 mb-2">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <strong>Devolução #{{ $devolucao->id }} — {{ $devolucao->referencia_documento }}</strong>
                    <span class="badge bg-secondary">{{ $devolucao->status }}</span>
                </div>
                <div class="small text-body-secondary">{{ $devolucao->data_documento?->format('d/m/Y') }} — {{ $devolucao->itens->count() }} item(ns)</div>
                <ul class="mt-2 mb-0">
                    @foreach ($devolucao->itens as $linha)
                        <li>{{ $linha->itemCompra?->descricao ?? ('Item #'.$linha->compra_item_id) }}: {{ $linha->quantidade }}</li>
                    @endforeach
                </ul>
            </div>
        @empty
            <p class="text-body-secondary mb-0">Nenhuma devolução operacional registrada nesta compra.</p>
        @endforelse
    </div>
</section>
