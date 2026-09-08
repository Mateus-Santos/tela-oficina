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
        <input
            type="number"
            name="valor"
            id="valor"
            class="form-control @error('valor') is-invalid @enderror"
            value="{{ old('valor', isset($conta) ? number_format((float) $conta->valor, 2, '.', '') : '') }}"
            min="0.01"
            step="0.01"
            required
        >
        @error('valor')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
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
        <small class="text-muted">
            A vinculação é opcional.
        </small>
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
