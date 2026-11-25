<section class="card shadow-sm mb-4" aria-labelledby="devolucoes-compra-titulo">
    <div class="card-body">
        <div class="d-flex justify-content-between flex-wrap gap-2 align-items-center mb-3">
            <div>
                <h2 id="devolucoes-compra-titulo" class="h5 mb-1">Devoluções ao fornecedor</h2>
                <p class="text-body-secondary small mb-0">Operacional: controle físico e histórico, sem abatimento automático. Documental: registra o documento e aplica abatimento financeiro somente quando seguro. A NF-e é emitida fora do sistema.</p>
            </div>
            @if ($compra->estaAprovada() && auth()->user()?->isAdmin())
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-danger btn-sm" href="{{ route('compras.devolucoes.documental.create', $compra) }}">
                        Nova devolução documental (impacto financeiro)
                    </a>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('compras.devolucoes.create', $compra) }}">
                        Controle operacional legado
                    </a>
                </div>
            @endif
        </div>
        @forelse ($compra->devolucoes as $devolucao)
            <div class="border rounded p-3 mb-2">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <strong>Devolução #{{ $devolucao->id }} — {{ $devolucao->referencia_documento }}</strong>
                    <span class="badge bg-secondary">{{ $devolucao->status }}</span>
                    @if ($devolucao->modalidade === 'documental')
                        <span class="badge {{ $devolucao->situacao_financeira === 'aplicada' ? 'bg-success' : 'bg-warning text-dark' }}">
                            Financeiro: {{ $devolucao->situacao_financeira }}
                        </span>
                    @else
                        <span class="badge bg-info text-dark">Operacional · sem abatimento financeiro</span>
                    @endif
                </div>
                <div class="small text-body-secondary">{{ $devolucao->data_documento?->format('d/m/Y') }} — {{ $devolucao->itens->count() }} item(ns)</div>
                @if ($devolucao->modalidade === 'documental')
                    <div>Valor de abatimento: R$ {{ number_format((float) $devolucao->valor_abatimento, 2, ',', '.') }}</div>
                @endif
                <ul class="mt-2 mb-0">
                    @foreach ($devolucao->itens as $linha)
                        <li>{{ $linha->itemCompra?->descricao ?? ('Item #'.$linha->compra_item_id) }}:
                            {{ $linha->quantidade }}
                            @if ($devolucao->modalidade === 'documental')
                                ({{ $linha->modalidade === 'recusado' ? 'recusado sem entrada' : 'baixado do estoque' }})
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <p class="text-body-secondary mb-0">Nenhuma devolução registrada nesta compra.</p>
        @endforelse
    </div>
</section>
