<div class="card shadow-sm mb-4">

    <div class="card-header d-flex align-items-center justify-content-between">

        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-list-ol"></i>
            <strong>Parcelas da conta</strong>
        </div>

        @if ($conta->status !== 'cancelada' && !$conta->estaPaga())
            <button
                type="button"
                class="btn btn-sm btn-primary"
                id="btn-adicionar-parcela"
            >
                <i class="bi bi-plus-lg"></i>
                Adicionar parcela
            </button>
        @endif

    </div>

    <div class="card-body">

        <form
            method="POST"
            action="{{ route('contas-pagar.parcelas.update', $conta) }}"
            id="form-parcelas"
        >
            @csrf

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead>
                        <tr>
                            <th width="80">Parcela</th>
                            <th>Vencimento</th>
                            <th width="180">Valor</th>
                            <th width="180">Pago</th>
                            <th width="180">Saldo</th>
                            <th width="150">Status</th>

                            @if ($conta->status !== 'cancelada' && !$conta->estaPaga())
                                <th width="80" class="text-end">Ações</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody id="parcelas-container">

                        @foreach ($conta->parcelas as $index => $parcela)

                            @php
                                $parcelaPaga = $parcela->estaPaga();
                                $parcelaTemPagamento = $parcela->valor_pago > 0;
                                $podeEditarParcela = $conta->status !== 'cancelada'
                                    && !$conta->estaPaga()
                                    && !$parcelaPaga;
                            @endphp

                            <tr
                                class="parcela-row"
                                data-valor-pago="{{ number_format((float) $parcela->valor_pago, 2, '.', '') }}"
                            >

                                <td>

                                    <input
                                        type="hidden"
                                        name="parcelas[{{ $index }}][id]"
                                        value="{{ $parcela->id }}"
                                        class="parcela-id"
                                    >

                                    <span class="badge bg-secondary parcela-numero">
                                        {{ $parcela->numero }}
                                    </span>

                                </td>

                                <td>

                                    @if ($podeEditarParcela)
                                        <input
                                            type="date"
                                            name="parcelas[{{ $index }}][data_vencimento]"
                                            value="{{ $parcela->data_vencimento?->format('Y-m-d') }}"
                                            class="form-control parcela-vencimento"
                                            required
                                        >
                                    @else
                                        <input
                                            type="hidden"
                                            name="parcelas[{{ $index }}][data_vencimento]"
                                            value="{{ $parcela->data_vencimento?->format('Y-m-d') }}"
                                        >

                                        <span class="form-control-plaintext">
                                            {{ $parcela->data_vencimento?->format('d/m/Y') }}
                                            @if ($parcelaPaga)
                                                <i
                                                    class="bi bi-lock-fill text-muted ms-1"
                                                    title="Parcela paga"
                                                ></i>
                                            @endif
                                        </span>
                                    @endif

                                    @if ($parcela->estaVencida())
                                        <small class="text-danger">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            Vencida
                                        </small>
                                    @endif

                                </td>

                                <td>

                                    @if ($podeEditarParcela)
                                        <div class="input-group">

                                            <span class="input-group-text">R$</span>

                                            <input
                                                type="number"
                                                name="parcelas[{{ $index }}][valor]"
                                                value="{{ number_format((float) $parcela->valor, 2, '.', '') }}"
                                                min="0.01"
                                                step="0.01"
                                                class="form-control parcela-valor"
                                                required
                                            >

                                        </div>
                                    @else
                                        <input
                                            type="hidden"
                                            name="parcelas[{{ $index }}][valor]"
                                            value="{{ number_format((float) $parcela->valor, 2, '.', '') }}"
                                        >

                                        <span class="form-control-plaintext">
                                            R$ {{ number_format((float) $parcela->valor, 2, ',', '.') }}

                                            @if ($parcelaPaga)
                                                <i
                                                    class="bi bi-lock-fill text-muted ms-1"
                                                    title="Parcela paga"
                                                ></i>
                                            @endif
                                        </span>
                                    @endif

                                </td>

                                <td>
                                    R$ {{ number_format((float) $parcela->valor_pago, 2, ',', '.') }}
                                </td>

                                <td>

                                    <span class="parcela-saldo">
                                        R$ {{ number_format((float) $parcela->saldo, 2, ',', '.') }}
                                    </span>

                                </td>

                                <td>

                                    @if ($parcelaPaga)

                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle"></i>
                                            Paga
                                        </span>

                                    @elseif ($parcela->estaVencida())

                                        <span class="badge bg-danger">
                                            <i class="bi bi-exclamation-circle"></i>
                                            Vencida
                                        </span>

                                    @elseif ($parcelaTemPagamento)

                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-hourglass-split"></i>
                                            Parcial
                                        </span>

                                    @else

                                        <span class="badge bg-secondary">
                                            <i class="bi bi-clock"></i>
                                            Aberta
                                        </span>

                                    @endif

                                </td>

                                @if ($conta->status !== 'cancelada' && !$conta->estaPaga())

                                    <td class="text-end">

                                        @if (!$parcelaPaga && !$parcelaTemPagamento)
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger btn-remover-parcela"
                                                title="Remover parcela"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif

                                    </td>

                                @endif

                            </tr>

                        @endforeach

                    </tbody>

                    <tfoot>

                        <tr class="table-light fw-semibold">

                            <td colspan="2" class="text-end">
                                Total:
                            </td>

                            <td>
                                <span id="parcelas-total">
                                    R$ {{ number_format((float) $conta->valor, 2, ',', '.') }}
                                </span>
                            </td>

                            <td>
                                R$ {{ number_format((float) $conta->valor_pago, 2, ',', '.') }}
                            </td>

                            <td colspan="{{ $conta->status !== 'cancelada' && !$conta->estaPaga() ? 3 : 2 }}">
                                R$ {{ number_format((float) $conta->saldo, 2, ',', '.') }}
                            </td>

                        </tr>

                    </tfoot>

                </table>

            </div>

            @if ($conta->status !== 'cancelada' && !$conta->estaPaga())

                <div class="d-flex justify-content-end mt-3">

                    <button
                        type="submit"
                        class="btn btn-success"
                        id="btn-salvar-parcelas"
                    >
                        <i class="bi bi-check-lg"></i>
                        Salvar parcelas
                    </button>

                </div>

            @endif

        </form>

    </div>

</div>
