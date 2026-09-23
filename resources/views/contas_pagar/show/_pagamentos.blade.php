<div class="card shadow-sm mb-4">

    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-cash-coin"></i>
        <strong>Pagamentos</strong>
    </div>

    <div class="card-body">

        @if ($conta->status !== 'cancelada' && !$conta->estaPaga())

            <form
                method="POST"
                action="{{ route('contas-pagar.pagamentos.store', $conta) }}"
                id="form-pagamento"
                class="mb-4"
            >
                @csrf

                <div class="row g-3">

                    <div class="col-md-4">

                        <label for="parcela_conta_pagar_id" class="form-label">
                            Parcela
                        </label>

                        <select
                            name="parcela_conta_pagar_id"
                            id="parcela_conta_pagar_id"
                            class="form-select @error('parcela_conta_pagar_id') is-invalid @enderror"
                            required
                        >
                            <option value="">Selecione a parcela...</option>

                            @foreach ($conta->parcelas as $parcela)

                                @if ($parcela->saldo > 0)

                                    <option
                                        value="{{ $parcela->id }}"
                                        data-saldo="{{ number_format((float) $parcela->saldo, 2, '.', '') }}"
                                        @selected(old('parcela_conta_pagar_id') == $parcela->id)
                                    >
                                        Parcela {{ $parcela->numero }}
                                        — Venc. {{ $parcela->data_vencimento?->format('d/m/Y') }}
                                        — Saldo R$ {{ number_format((float) $parcela->saldo, 2, ',', '.') }}
                                    </option>

                                @endif

                            @endforeach

                        </select>

                        @error('parcela_conta_pagar_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                        <div id="saldo-parcela" class="form-text">
                            Selecione uma parcela para visualizar o saldo disponível.
                        </div>

                    </div>

                    <div class="col-md-2">

                        <label for="valor_pagamento" class="form-label">
                            Valor
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">R$</span>

                            <input
                                type="number"
                                name="valor"
                                id="valor_pagamento"
                                class="form-control @error('valor') is-invalid @enderror"
                                value="{{ old('valor') }}"
                                min="0.01"
                                step="0.01"
                                required
                            >

                        </div>

                        @error('valor')
                            <div class="invalid-feedback d-block">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                    <div class="col-md-2">

                        <label for="data_pagamento" class="form-label">
                            Data
                        </label>

                        <input
                            type="date"
                            name="data_pagamento"
                            id="data_pagamento"
                            class="form-control @error('data_pagamento') is-invalid @enderror"
                            value="{{ old('data_pagamento', now()->format('Y-m-d')) }}"
                            required
                        >

                        @error('data_pagamento')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                    <div class="col-md-4">

                        <label for="forma_pagamento_id" class="form-label">
                            Forma de pagamento
                        </label>

                        <select
                            name="forma_pagamento_id"
                            id="forma_pagamento_id"
                            class="form-select @error('forma_pagamento_id') is-invalid @enderror"
                            required
                        >
                            <option value="">Selecione...</option>

                            @foreach ($formasPagamento as $forma)

                                <option
                                    value="{{ $forma->id }}"
                                    @selected(old('forma_pagamento_id') == $forma->id)
                                >
                                    {{ $forma->nome }}
                                </option>

                            @endforeach

                        </select>

                        @error('forma_pagamento_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                    <div class="col-md-10">

                        <label for="observacoes_pagamento" class="form-label">
                            Observações
                        </label>

                        <input
                            type="text"
                            name="observacoes"
                            id="observacoes_pagamento"
                            class="form-control @error('observacoes') is-invalid @enderror"
                            value="{{ old('observacoes') }}"
                        >

                        @error('observacoes')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                    <div class="col-md-2 d-flex align-items-end">

                        <button
                            type="submit"
                            class="btn btn-success w-100"
                        >
                            <i class="bi bi-check-circle"></i>
                            Registrar
                        </button>

                    </div>

                </div>

            </form>

        @endif

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h6 class="mb-0">
                Histórico de pagamentos
            </h6>

            <span class="badge bg-secondary">
                {{ $conta->pagamentos->count() }}
                {{ $conta->pagamentos->count() === 1 ? 'pagamento' : 'pagamentos' }}
            </span>

        </div>

        @if ($conta->pagamentos->isEmpty())

            <div class="text-center text-muted py-4">
                <i class="bi bi-cash-stack fs-2 d-block mb-2"></i>
                Nenhum pagamento registrado.
            </div>

        @else

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Parcela</th>
                            <th>Valor</th>
                            <th>Forma de pagamento</th>
                            <th>Status</th>
                            <th>Observações</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($conta->pagamentos as $pagamento)

                            <tr>

                                <td>
                                    {{ $pagamento->data_pagamento?->format('d/m/Y') }}
                                </td>

                                <td>

                                    @if ($pagamento->parcela)

                                        <span class="badge bg-light text-dark border">
                                            Parcela {{ $pagamento->parcela->numero }}
                                        </span>

                                    @else

                                        —

                                    @endif

                                </td>

                                <td class="fw-semibold">
                                    R$ {{ number_format((float) $pagamento->valor, 2, ',', '.') }}
                                </td>

                                <td>
                                    {{ $pagamento->formaPagamento?->nome ?? $pagamento->forma_pagamento ?? '—' }}
                                </td>

                                <td>

                                    @if ($pagamento->estaEstornado())

                                        <span class="badge bg-danger">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                            Estornado
                                        </span>

                                        @if ($pagamento->estornado_em)

                                            <div class="small text-muted mt-1">
                                                {{ $pagamento->estornado_em->format('d/m/Y H:i') }}
                                            </div>

                                        @endif

                                    @else

                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle"></i>
                                            Ativo
                                        </span>

                                    @endif

                                </td>

                                <td>

                                    {{ $pagamento->observacoes ?: '—' }}

                                    @if ($pagamento->motivo_estorno)

                                        <div class="small text-danger mt-1">
                                            <strong>Motivo:</strong>
                                            {{ $pagamento->motivo_estorno }}
                                        </div>

                                    @endif

                                </td>

                                <td class="text-end">

                                    @if (!$pagamento->estaEstornado() && $conta->status !== 'cancelada')

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEstornarPagamento{{ $pagamento->id }}"
                                            title="Estornar pagamento"
                                        >
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>

                                    @endif

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @endif

    </div>

</div>
