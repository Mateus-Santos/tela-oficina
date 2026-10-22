<div class="row g-3">

    <div class="col-12 col-md-8">

        <label for="descricao" class="form-label">
            <i class="bi bi-card-text"></i>
            Descrição
        </label>

        <input
            type="text"
            name="descricao"
            id="descricao"
            class="form-control @error('descricao') is-invalid @enderror"
            value="{{ old('descricao', $conta->descricao ?? '') }}"
            maxlength="255"
            placeholder="Ex.: Compra de peças"
            required
        >

        @error('descricao')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

    </div>

    <div class="col-12 col-md-4">

        <label for="valor" class="form-label">
            <i class="bi bi-currency-dollar"></i>
            Valor
        </label>

        @php
            $valorPago = isset($conta)
                ? (float) ($conta->valor_pago ?? 0)
                : 0;

            $valorConta = isset($conta)
                ? (float) $conta->valor
                : 0;
        @endphp

        <input
            type="number"
            name="valor"
            id="valor"
            class="form-control @error('valor') is-invalid @enderror"
            value="{{ old('valor', isset($conta) ? number_format($valorConta, 2, '.', '') : '') }}"
            min="{{ number_format($valorPago > 0 ? $valorPago : 0.01, 2, '.', '') }}"
            step="0.01"
            required
        >

        @error('valor')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        @if ($valorPago > 0)

            <div class="alert alert-info d-flex align-items-start gap-2 py-2 px-3 mt-2 mb-0">

                <i class="bi bi-info-circle-fill mt-1"></i>

                <div>
                    <strong>Já pago: R$ {{ number_format($valorPago, 2, ',', '.') }}</strong>

                    <div class="small mt-1">
                        O novo valor não pode ser inferior ao total já pago.
                    </div>
                </div>

            </div>

        @endif

    </div>

    <div class="col-12 col-md-6">

        <label for="fornecedor_id" class="form-label">
            <i class="bi bi-building"></i>
            Fornecedor
        </label>

        <select
            name="fornecedor_id"
            id="fornecedor_id"
            class="form-select @error('fornecedor_id') is-invalid @enderror"
        >
            <option value="">Não informado</option>

            @foreach ($fornecedores as $fornecedor)

                <option
                    value="{{ $fornecedor->id }}"
                    @selected((string) old('fornecedor_id', $conta->fornecedor_id ?? '') === (string) $fornecedor->id)
                >
                    {{ $fornecedor->nome }}

                    @if ($fornecedor->cnpj)
                        — {{ $fornecedor->cnpj }}
                    @endif
                </option>

            @endforeach

        </select>

        @error('fornecedor_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

    </div>

    @php
        $podeGerenciarCompras =
            !isset($conta)
            || (
                $conta->status !== 'cancelada'
                && $valorPago <= 0
            );

        $comprasSelecionadas = collect(
            old(
                'compras',
                isset($conta)
                    ? $conta->compras->pluck('id')->all()
                    : []
            )
        )
            ->map(fn ($id) => (int) $id)
            ->all();
    @endphp

    <div class="col-12">
        <div class="card border">
            <div
                class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2"
            >
                <div>
                    <div class="fw-semibold">
                        <i class="bi bi-cart-check me-1"></i>
                        Compras vinculadas
                    </div>

                    <div class="small text-muted mt-1">
                        Agrupe uma ou mais compras aprovadas na mesma conta a pagar.
                    </div>
                </div>

                <span
                    class="badge bg-light text-dark border"
                    id="compras-vinculadas-resumo"
                >
                    0 selecionadas
                </span>
            </div>

            <div class="card-body">
                @if (!$podeGerenciarCompras)

                    <div class="alert alert-info mb-3">
                        <i class="bi bi-lock me-1"></i>
                        Os vínculos das compras não podem ser alterados porque
                        esta conta possui pagamentos ou está bloqueada.
                    </div>

                    @if (isset($conta) && $conta->compras->isNotEmpty())
                        <div class="row g-2">
                            @foreach ($conta->compras as $compraVinculada)
                                <div class="col-12 col-lg-6">
                                    <div class="border rounded p-3 h-100">
                                        <div
                                            class="d-flex justify-content-between gap-3"
                                        >
                                            <div>
                                                <div class="fw-semibold">
                                                    Compra
                                                    #{{ str_pad($compraVinculada->id, 6, '0', STR_PAD_LEFT) }}
                                                </div>

                                                <div class="small text-muted mt-1">
                                                    NF
                                                    {{ $compraVinculada->numero_nf ?: 'não informada' }}
                                                </div>
                                            </div>

                                            <div class="text-end">
                                                <div class="fw-semibold">
                                                    R$
                                                    {{ number_format((float) $compraVinculada->pivot->valor_vinculado, 2, ',', '.') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-muted">
                            Nenhuma compra vinculada.
                        </div>
                    @endif

                @else

                    <input
                        type="hidden"
                        name="gerenciar_compras"
                        value="1"
                    >

                    @error('compras')
                        <div class="alert alert-danger py-2">
                            {{ $message }}
                        </div>
                    @enderror

                    @if ($comprasDisponiveis->isEmpty())
                        <div class="text-muted">
                            Nenhuma compra aprovada e disponível para vínculo.
                        </div>
                    @else
                        <div class="row g-2">
                            @foreach ($comprasDisponiveis as $compraDisponivel)
                                @php
                                    $selecionada = in_array(
                                        (int) $compraDisponivel->id,
                                        $comprasSelecionadas,
                                        true
                                    );
                                @endphp

                                <div
                                    class="col-12 col-lg-6"
                                    data-compra-item
                                >
                                    <label
                                        class="border rounded p-3 h-100 d-block"
                                        style="cursor: pointer;"
                                    >
                                        <div
                                            class="d-flex align-items-start gap-3"
                                        >
                                            <input
                                                type="checkbox"
                                                name="compras[]"
                                                value="{{ $compraDisponivel->id }}"
                                                class="form-check-input mt-1"
                                                data-compra-checkbox
                                                data-fornecedor-id="{{ $compraDisponivel->fornecedor_id }}"
                                                data-valor="{{ number_format((float) $compraDisponivel->valor_total, 2, '.', '') }}"
                                                @checked($selecionada)
                                            >

                                            <div class="flex-grow-1">
                                                <div
                                                    class="d-flex justify-content-between gap-3"
                                                >
                                                    <div>
                                                        <div class="fw-semibold">
                                                            Compra
                                                            #{{ str_pad($compraDisponivel->id, 6, '0', STR_PAD_LEFT) }}
                                                        </div>

                                                        <div class="small text-muted">
                                                            {{ $compraDisponivel->fornecedor?->nome ?? 'Fornecedor não informado' }}
                                                        </div>
                                                    </div>

                                                    <div class="text-end">
                                                        <div class="fw-semibold">
                                                            R$
                                                            {{ number_format((float) $compraDisponivel->valor_total, 2, ',', '.') }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="small text-muted mt-2">
                                                    NF
                                                    {{ $compraDisponivel->numero_nf ?: 'não informada' }}

                                                    <span class="mx-1">•</span>

                                                    Emissão:
                                                    {{ $compraDisponivel->data_emissao?->format('d/m/Y') ?? 'não informada' }}
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div
                            class="alert alert-light border mt-3 mb-0"
                        >
                            <div
                                class="d-flex flex-wrap justify-content-between align-items-center gap-2"
                            >
                                <span>
                                    Total das compras selecionadas
                                </span>

                                <strong
                                    id="compras-vinculadas-total"
                                >
                                    R$ 0,00
                                </strong>
                            </div>

                            <div class="small text-muted mt-2">
                                Ao selecionar compras, o valor da conta deve ser
                                exatamente igual à soma dos documentos vinculados.
                                Todas devem pertencer ao mesmo fornecedor.
                            </div>
                        </div>
                    @endif

                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6">

        <label for="nota_id" class="form-label">
            <i class="bi bi-receipt"></i>
            Nota vinculada
        </label>

        <select
            name="nota_id"
            id="nota_id"
            class="form-select @error('nota_id') is-invalid @enderror"
        >
            <option value="">Nenhuma nota vinculada</option>

            @foreach ($notas as $nota)

                <option
                    value="{{ $nota->id }}"
                    @selected((string) old('nota_id', $conta->nota_id ?? '') === (string) $nota->id)
                >
                    #{{ $nota->id }}
                    — {{ $nota->cliente?->pessoa?->nome ?? 'Cliente Geral / Balcão' }}
                    — R$ {{ number_format((float) $nota->total, 2, ',', '.') }}
                </option>

            @endforeach

        </select>

        @error('nota_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        <div class="alert alert-light border d-flex align-items-center gap-2 py-2 px-3 mt-2 mb-0 small">
            <i class="bi bi-info-circle text-secondary"></i>
            <span>A vinculação é opcional.</span>
        </div>

    </div>

    <div class="col-12 col-md-6">

        <label for="categoria_financeira_id" class="form-label">
            <i class="bi bi-tags"></i>
            Categoria financeira
        </label>

        <select
            name="categoria_financeira_id"
            id="categoria_financeira_id"
            class="form-select @error('categoria_financeira_id') is-invalid @enderror"
        >
            <option value="">Nenhuma categoria</option>

            @foreach ($categoriasFinanceiras as $categoria)

                <option
                    value="{{ $categoria->id }}"
                    @selected((string) old('categoria_financeira_id', $conta->categoria_financeira_id ?? '') === (string) $categoria->id)
                >
                    {{ $categoria->nome }}
                </option>

            @endforeach

        </select>

        @error('categoria_financeira_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        <div class="alert alert-light border d-flex align-items-center gap-2 py-2 px-3 mt-2 mb-0 small">
            <i class="bi bi-info-circle text-secondary"></i>
            <span>Classificação da despesa no financeiro.</span>
        </div>

    </div>

    <div class="col-12 col-md-6">

        <label for="forma_pagamento_id" class="form-label">
            <i class="bi bi-credit-card"></i>
            Forma de pagamento planejada
        </label>

        <select
            name="forma_pagamento_id"
            id="forma_pagamento_id"
            class="form-select @error('forma_pagamento_id') is-invalid @enderror"
        >
            <option value="">Nenhuma forma definida</option>

            @foreach ($formasPagamento as $formaPagamento)

                <option
                    value="{{ $formaPagamento->id }}"
                    @selected((string) old('forma_pagamento_id', $conta->forma_pagamento_id ?? '') === (string) $formaPagamento->id)
                >
                    {{ $formaPagamento->nome }}
                </option>

            @endforeach

        </select>

        @error('forma_pagamento_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        <div class="alert alert-light border d-flex align-items-center gap-2 py-2 px-3 mt-2 mb-0 small">
            <i class="bi bi-info-circle text-secondary"></i>
            <span>Forma preferencial. A forma realmente utilizada pode ser diferente no pagamento.</span>
        </div>

    </div>

    <div class="col-12 col-md-6">

        <label for="data_emissao" class="form-label">
            <i class="bi bi-calendar-event"></i>
            Data de emissão
        </label>

        <input
            type="date"
            name="data_emissao"
            id="data_emissao"
            class="form-control @error('data_emissao') is-invalid @enderror"
            value="{{ old('data_emissao', isset($conta) ? $conta->data_emissao?->format('Y-m-d') : today()->format('Y-m-d')) }}"
            required
        >

        @error('data_emissao')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

    </div>

    <div class="col-12 col-md-6">

        <label for="data_vencimento" class="form-label">
            <i class="bi bi-calendar-check"></i>
            Data de vencimento
        </label>

        <input
            type="date"
            name="data_vencimento"
            id="data_vencimento"
            class="form-control @error('data_vencimento') is-invalid @enderror"
            value="{{ old('data_vencimento', isset($conta) ? $conta->data_vencimento?->format('Y-m-d') : '') }}"
            required
        >

        @error('data_vencimento')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

    </div>

    <div class="col-12">

        <label for="observacoes" class="form-label">
            <i class="bi bi-chat-left-text"></i>
            Observações
        </label>

        <textarea
            name="observacoes"
            id="observacoes"
            class="form-control @error('observacoes') is-invalid @enderror"
            rows="4"
            placeholder="Observações adicionais..."
        >{{ old('observacoes', $conta->observacoes ?? '') }}</textarea>

        @error('observacoes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

    </div>

    <div class="col-12">

        <div class="d-flex gap-2 mt-2">

            <button
                type="submit"
                class="btn btn-primary"
            >
                <i class="bi bi-check-lg"></i>
                {{ isset($conta) ? 'Salvar alterações' : 'Cadastrar conta' }}
            </button>

            <a
                href="{{ isset($conta) ? route('contas-pagar.show', $conta) : route('contas-pagar.index') }}"
                class="btn btn-secondary"
            >
                <i class="bi bi-x-lg"></i>
                Cancelar
            </a>

        </div>

    </div>

</div>


@once
<script id="compras-vinculadas-script">
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = Array.from(
        document.querySelectorAll(
            '[data-compra-checkbox]'
        )
    );

    if (!checkboxes.length) {
        return;
    }

    const fornecedor = document.getElementById(
        'fornecedor_id'
    );

    const valor = document.getElementById(
        'valor'
    );

    const resumo = document.getElementById(
        'compras-vinculadas-resumo'
    );

    const total = document.getElementById(
        'compras-vinculadas-total'
    );

    const formatarMoeda = function (numero) {
        return numero.toLocaleString(
            'pt-BR',
            {
                style: 'currency',
                currency: 'BRL',
            }
        );
    };

    const selecionadas = function () {
        return checkboxes.filter(
            checkbox => checkbox.checked
        );
    };

    const atualizar = function () {
        const atuais = selecionadas();

        const totalSelecionado = atuais.reduce(
            function (soma, checkbox) {
                return soma + Number(
                    checkbox.dataset.valor || 0
                );
            },
            0
        );

        if (resumo) {
            resumo.textContent =
                atuais.length === 1
                    ? '1 selecionada'
                    : atuais.length + ' selecionadas';
        }

        if (total) {
            total.textContent =
                formatarMoeda(totalSelecionado);
        }

        /*
         * Havendo compras selecionadas, o valor
         * financeiro deve refletir exatamente
         * a soma delas.
         */
        if (valor && atuais.length > 0) {
            valor.value =
                totalSelecionado.toFixed(2);
        }

        /*
         * Todas as compras de um agrupamento
         * devem ter o mesmo fornecedor.
         */
        let fornecedorBase = null;

        if (atuais.length > 0) {
            fornecedorBase =
                atuais[0].dataset.fornecedorId;

            if (
                fornecedor
                && fornecedorBase
            ) {
                fornecedor.value =
                    fornecedorBase;
            }
        } else if (
            fornecedor
            && fornecedor.value
        ) {
            fornecedorBase =
                fornecedor.value;
        }

        checkboxes.forEach(
            function (checkbox) {
                const incompatível =
                    fornecedorBase
                    && checkbox.dataset.fornecedorId
                        !== fornecedorBase
                    && !checkbox.checked;

                checkbox.disabled =
                    Boolean(incompatível);

                const item =
                    checkbox.closest(
                        '[data-compra-item]'
                    );

                if (item) {
                    item.classList.toggle(
                        'opacity-50',
                        Boolean(incompatível)
                    );
                }
            }
        );
    };

    checkboxes.forEach(
        function (checkbox) {
            checkbox.addEventListener(
                'change',
                atualizar
            );
        }
    );

    if (fornecedor) {
        fornecedor.addEventListener(
            'change',
            atualizar
        );
    }

    atualizar();
});
</script>
@endonce
