@php
    $isEdit = isset($compra);
@endphp
@if ($errors->any())
    <div class="alert alert-danger">
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle"></i>

            <div>
                <strong>Não foi possível salvar a compra.</strong>

                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

<div class="row g-3">

    {{-- FORNECEDOR --}}
    <div class="col-12 col-md-8">

        <label for="fornecedor_id" class="form-label">
            <i class="bi bi-truck"></i>
            Fornecedor *
        </label>

        <select
            name="fornecedor_id"
            id="fornecedor_id"
            class="form-select"
            required
        >
            <option value="">Selecione o fornecedor</option>

            @foreach ($fornecedores as $fornecedor)
                <option
                    value="{{ $fornecedor->id }}"
                    @selected((string) old('fornecedor_id', $compra->fornecedor_id ?? '') === (string) $fornecedor->id)
                >
                    {{ $fornecedor->nome }}
                </option>
            @endforeach

        </select>

    </div>

    {{-- NÚMERO NF --}}
    <div class="col-12 col-md-4">

        <label for="numero_nf" class="form-label">
            <i class="bi bi-receipt"></i>
            Número da NF *
        </label>

        <input
            type="text"
            name="numero_nf"
            id="numero_nf"
            class="form-control"
            value="{{ old('numero_nf', $compra->numero_nf ?? '') }}"
            maxlength="255"
            required
        >

    </div>

    {{-- SÉRIE --}}
    <div class="col-12 col-md-4">

        <label for="serie_nf" class="form-label">
            <i class="bi bi-hash"></i>
            Série da NF
        </label>

        <input
            type="text"
            name="serie_nf"
            id="serie_nf"
            class="form-control"
            value="{{ old('serie_nf', $compra->serie_nf ?? '') }}"
            maxlength="50"
        >

    </div>

    {{-- CHAVE NF --}}
    <div class="col-12 col-md-8">

        <label for="chave_nf" class="form-label">
            <i class="bi bi-upc-scan"></i>
            Chave de acesso
        </label>

        <input
            type="text"
            name="chave_nf"
            id="chave_nf"
            class="form-control"
            value="{{ old('chave_nf', $compra->chave_nf ?? '') }}"
            maxlength="44"
            minlength="44"
            inputmode="numeric"
            placeholder="44 dígitos"
        >

        <small class="text-muted">
            Informe os 44 dígitos da chave da NF-e, quando disponível.
        </small>

    </div>

    {{-- DATA EMISSÃO --}}
    <div class="col-12 col-md-4">

        <label for="data_emissao" class="form-label">
            <i class="bi bi-calendar-event"></i>
            Data de emissão
        </label>

        <input
            type="date"
            name="data_emissao"
            id="data_emissao"
            class="form-control"
            value="{{ old('data_emissao', isset($compra->data_emissao) ? $compra->data_emissao->format('Y-m-d') : '') }}"
        >

    </div>

    {{-- DATA ENTRADA --}}
    <div class="col-12 col-md-4">

        <label for="data_entrada" class="form-label">
            <i class="bi bi-calendar-check"></i>
            Data de entrada *
        </label>

        <input
            type="date"
            name="data_entrada"
            id="data_entrada"
            class="form-control"
            value="{{ old('data_entrada', isset($compra->data_entrada) ? $compra->data_entrada->format('Y-m-d') : now()->format('Y-m-d')) }}"
            required
        >

    </div>

</div>


{{-- ITENS --}}
<div class="mt-4">

    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">

        <h2 class="h5 mb-0">
            <i class="bi bi-box-seam"></i>
            Produtos da compra
        </h2>

        <button
            type="button"
            id="btn-adicionar-item"
            class="btn btn-primary btn-sm"
        >
            <i class="bi bi-plus-lg"></i>
            Adicionar produto
        </button>

    </div>

    <div
        id="itens-container"
        class="d-flex flex-column gap-3"
    >

        @php
            $itens = old('itens');

            if ($itens === null && isset($compra)) {
                $itens = $compra->itens->map(function ($item) {
                    return [
                        'produto_id' => $item->produto_id,
                        'descricao' => $item->descricao,
                        'quantidade' => $item->quantidade,
                        'quantidade_conferida' => $item->quantidade_conferida,
                        'valor_unitario' => $item->valor_unitario,
                        'desconto' => $item->desconto,
                        'valor_total' => $item->valor_total,
                    ];
                })->toArray();
            }

            $itens = $itens ?: [[]];
        @endphp

        @foreach ($itens as $index => $item)

            <div
                class="card shadow-sm compra-item"
                data-item-index="{{ $index }}"
            >

                <div class="card-body">

                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">

                        <h3 class="h6 mb-0">
                            <i class="bi bi-box"></i>
                            Item <span class="item-numero">{{ $index + 1 }}</span>
                        </h3>

                        <button
                            type="button"
                            class="btn btn-danger btn-sm btn-remover-item"
                            title="Remover item"
                        >
                            <i class="bi bi-trash"></i>
                        </button>

                    </div>

                    <div class="row g-3">

                        {{-- PRODUTO --}}
                        <div class="col-12 col-md-6">

                            <label class="form-label">
                                <i class="bi bi-box-seam"></i>
                                Produto *
                            </label>

                            <select
                                name="itens[{{ $index }}][produto_id]"
                                class="form-select item-produto"
                                required
                            >
                                <option value="">Selecione o produto</option>

                                @foreach ($produtos as $produto)

                                    <option
                                        value="{{ $produto->id }}"
                                        data-descricao="{{ $produto->nome }}"
                                        data-preco="{{ $produto->preco_uni }}"
                                        @selected((string) ($item['produto_id'] ?? '') === (string) $produto->id)
                                    >
                                        {{ $produto->nome }}

                                        @if ($produto->codigo_fabricante)
                                            — {{ $produto->codigo_fabricante }}
                                        @endif
                                    </option>

                                @endforeach

                            </select>

                        </div>

                        {{-- DESCRIÇÃO --}}
                        <div class="col-12 col-md-6">

                            <label class="form-label">
                                <i class="bi bi-card-text"></i>
                                Descrição *
                            </label>

                            <input
                                type="text"
                                name="itens[{{ $index }}][descricao]"
                                class="form-control item-descricao"
                                value="{{ $item['descricao'] ?? '' }}"
                                maxlength="255"
                                required
                            >

                        </div>

                        {{-- QUANTIDADE --}}
                        <div class="col-12 col-md-3">

                            <label class="form-label">
                                <i class="bi bi-boxes"></i>
                                Quantidade *
                            </label>

                            <input
                                type="number"
                                name="itens[{{ $index }}][quantidade]"
                                class="form-control item-quantidade"
                                value="{{ $item['quantidade'] ?? 1 }}"
                                min="0.001"
                                step="0.001"
                                required
                            >

                        </div>

                        {{-- QUANTIDADE CONFERIDA --}}
                        <div class="col-12 col-md-3">

                            <label class="form-label">
                                <i class="bi bi-check2-square"></i>
                                Quantidade conferida
                            </label>

                            <input
                                type="number"
                                name="itens[{{ $index }}][quantidade_conferida]"
                                class="form-control"
                                value="{{ $item['quantidade_conferida'] ?? '' }}"
                                min="0"
                                step="0.001"
                            >

                        </div>

                        {{-- VALOR UNITÁRIO --}}
                        <div class="col-12 col-md-3">

                            <label class="form-label">
                                <i class="bi bi-currency-dollar"></i>
                                Valor unitário *
                            </label>

                            <input
                                type="number"
                                name="itens[{{ $index }}][valor_unitario]"
                                class="form-control item-valor-unitario"
                                value="{{ $item['valor_unitario'] ?? '' }}"
                                min="0"
                                step="0.01"
                                required
                            >

                        </div>

                        {{-- DESCONTO --}}
                        <div class="col-12 col-md-3">

                            <label class="form-label">
                                <i class="bi bi-percent"></i>
                                Desconto
                            </label>

                            <input
                                type="number"
                                name="itens[{{ $index }}][desconto]"
                                class="form-control item-desconto"
                                value="{{ $item['desconto'] ?? 0 }}"
                                min="0"
                                step="0.01"
                            >

                        </div>

                        {{-- TOTAL ITEM --}}
                        <div class="col-12">

                            <div class="d-flex justify-content-end">

                                <div class="text-end">

                                    <small class="text-muted">
                                        Total do item
                                    </small>

                                    <div class="fw-bold item-valor-total">
                                        R$
                                        {{ number_format((float) ($item['valor_total'] ?? 0), 2, ',', '.') }}
                                    </div>

                                </div>

                            </div>

                            <input
                                type="hidden"
                                name="itens[{{ $index }}][valor_total]"
                                class="item-valor-total-input"
                                value="{{ $item['valor_total'] ?? 0 }}"
                            >

                        </div>

                    </div>

                </div>

            </div>

        @endforeach

    </div>

</div>


{{-- VALORES --}}
<div class="card shadow-sm mt-4">

    <div class="card-body">

        <h2 class="h5 mb-3">
            <i class="bi bi-calculator"></i>
            Valores da compra
        </h2>

        <div class="row g-3">

            {{-- VALOR PRODUTOS --}}
            <div class="col-12 col-md-3">

                <label for="valor_produtos" class="form-label">
                    <i class="bi bi-box-seam"></i>
                    Valor dos produtos
                </label>

                <input
                    type="number"
                    name="valor_produtos"
                    id="valor_produtos"
                    class="form-control"
                    value="{{ old('valor_produtos', $compra->valor_produtos ?? 0) }}"
                    min="0"
                    step="0.01"
                    readonly
                >

            </div>

            {{-- DESCONTO --}}
            <div class="col-12 col-md-3">

                <label for="desconto" class="form-label">
                    <i class="bi bi-tag"></i>
                    Desconto
                </label>

                <input
                    type="number"
                    name="desconto"
                    id="desconto"
                    class="form-control"
                    value="{{ old('desconto', $compra->desconto ?? 0) }}"
                    min="0"
                    step="0.01"
                >

            </div>

            {{-- FRETE --}}
            <div class="col-12 col-md-3">

                <label for="frete" class="form-label">
                    <i class="bi bi-truck"></i>
                    Frete
                </label>

                <input
                    type="number"
                    name="frete"
                    id="frete"
                    class="form-control"
                    value="{{ old('frete', $compra->frete ?? 0) }}"
                    min="0"
                    step="0.01"
                >

            </div>

            {{-- OUTRAS DESPESAS --}}
            <div class="col-12 col-md-3">

                <label for="outras_despesas" class="form-label">
                    <i class="bi bi-plus-circle"></i>
                    Outras despesas
                </label>

                <input
                    type="number"
                    name="outras_despesas"
                    id="outras_despesas"
                    class="form-control"
                    value="{{ old('outras_despesas', $compra->outras_despesas ?? 0) }}"
                    min="0"
                    step="0.01"
                >

            </div>

            {{-- TOTAL --}}
            <div class="col-12">

                <div class="d-flex justify-content-end">

                    <div class="text-end">

                        <span class="text-muted">
                            Valor total da compra
                        </span>

                        <div
                            id="valor-total-exibicao"
                            class="fs-4 fw-bold"
                        >
                            R$
                            {{ number_format((float) old('valor_total', $compra->valor_total ?? 0), 2, ',', '.') }}
                        </div>

                    </div>

                </div>

                <input
                    type="hidden"
                    name="valor_total"
                    id="valor_total"
                    value="{{ old('valor_total', $compra->valor_total ?? 0) }}"
                >

            </div>

        </div>

    </div>

</div>


{{-- OBSERVAÇÕES --}}
<div class="mt-4">

    <label for="observacoes" class="form-label">
        <i class="bi bi-chat-left-text"></i>
        Observações
    </label>

    <textarea
        name="observacoes"
        id="observacoes"
        class="form-control"
        rows="4"
        placeholder="Observações sobre a compra..."
    >{{ old('observacoes', $compra->observacoes ?? '') }}</textarea>

</div>


{{-- AÇÕES --}}
<div class="d-flex justify-content-end gap-2 mt-4">

    <a
        href="{{ $isEdit ? route('compras.show', $compra) : route('compras.index') }}"
        class="btn btn-secondary"
    >
        <i class="bi bi-x-lg"></i>
        Cancelar
    </a>

    <button
        type="submit"
        class="btn btn-primary"
    >
        <i class="bi bi-check-lg"></i>
        {{ $isEdit ? 'Atualizar Compra' : 'Cadastrar Compra' }}
    </button>

</div>
