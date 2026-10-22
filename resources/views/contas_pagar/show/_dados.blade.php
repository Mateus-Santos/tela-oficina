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

            <div class="col-12">
                <div class="text-muted small mb-2">
                    Compras vinculadas
                </div>

                @if ($conta->compras->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Compra</th>
                                    <th>NF</th>
                                    <th>Emissão</th>
                                    <th class="text-end">
                                        Valor vinculado
                                    </th>
                                    <th class="text-center">
                                        Ação
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($conta->compras as $compraVinculada)
                                    <tr>
                                        <td>
                                            #{{ str_pad($compraVinculada->id, 6, '0', STR_PAD_LEFT) }}
                                        </td>

                                        <td>
                                            {{ $compraVinculada->numero_nf ?: 'Não informada' }}
                                        </td>

                                        <td>
                                            {{ $compraVinculada->data_emissao?->format('d/m/Y') ?? 'Não informada' }}
                                        </td>

                                        <td class="text-end fw-semibold">
                                            R$
                                            {{ number_format((float) $compraVinculada->pivot->valor_vinculado, 2, ',', '.') }}
                                        </td>

                                        <td class="text-center">
                                            <a
                                                href="{{ route('compras.show', $compraVinculada) }}"
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                <i class="bi bi-box-arrow-up-right"></i>
                                                Abrir
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                            <tfoot>
                                <tr>
                                    <th colspan="3">
                                        Total vinculado
                                    </th>

                                    <th class="text-end">
                                        R$
                                        {{ number_format(
                                            (float) $conta->compras->sum(
                                                fn ($compraVinculada) =>
                                                    (float) $compraVinculada->pivot->valor_vinculado
                                            ),
                                            2,
                                            ',',
                                            '.'
                                        ) }}
                                    </th>

                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="fw-semibold">
                        Nenhuma compra vinculada
                    </div>
                @endif
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
