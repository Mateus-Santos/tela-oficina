<div class="card shadow-sm mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-info-circle"></i>
        <strong>Dados da conta</strong>
    </div>

    <div class="card-body">
        <div class="row g-3">

            <div class="col-md-6">
                <div class="text-muted small">Descrição</div>
                <div class="fw-semibold">{{ $conta->descricao }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">Fornecedor</div>
                <div class="fw-semibold">
                    {{ $conta->fornecedor?->nome ?? 'Não informado' }}
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-muted small">Valor</div>
                <div class="fw-semibold">
                    R$ {{ number_format((float) $conta->valor, 2, ',', '.') }}
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-muted small">Data de emissão</div>
                <div class="fw-semibold">
                    {{ $conta->data_emissao?->format('d/m/Y') ?? 'Não informada' }}
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-muted small">Vencimento</div>
                <div class="fw-semibold">
                    {{ $conta->data_vencimento?->format('d/m/Y') ?? 'Não informado' }}
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-muted small">Categoria financeira</div>
                <div class="fw-semibold">
                    {{ $conta->categoriaFinanceira?->nome ?? 'Não informada' }}
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-muted small">Forma de pagamento</div>
                <div class="fw-semibold">
                    {{ $conta->formaPagamento?->nome ?? 'Não informada' }}
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-muted small">Nota vinculada</div>
                <div class="fw-semibold">
                    {{ $conta->nota?->id ? '#' . str_pad($conta->nota->id, 6, '0', STR_PAD_LEFT) : 'Não vinculada' }}
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-muted small">Compra vinculada</div>
                <div class="fw-semibold">
                    @if ($conta->compra)
                        <a
                            href="{{ route('compras.show', $conta->compra) }}"
                            class="text-decoration-none"
                        >
                            <i class="bi bi-box-arrow-up-right me-1"></i>
                            #{{ str_pad($conta->compra->id, 6, '0', STR_PAD_LEFT) }}
                        </a>
                    @else
                        Não vinculada
                    @endif
                </div>
            </div>

            @if ($conta->observacoes)
                <div class="col-12">
                    <div class="text-muted small">Observações</div>
                    <div class="fw-semibold text-break">
                        {!! nl2br(e($conta->observacoes)) !!}
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
