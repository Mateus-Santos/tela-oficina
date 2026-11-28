@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <x-list-header title="DEVOLUÇÃO DE COMPRA — CONTROLE INTERNO" icon="bi-arrow-return-left" />
    <div class="alert alert-warning" role="alert">
        Este registro movimenta o estoque da oficina, mas <strong>não emite NF-e</strong>,
        não comprova autorização fiscal e não ajusta Contas a Pagar. Registre somente
        mercadorias fisicamente separadas para devolução, com documentação adequada.
    </div>
    <div class="mb-3">
        <a class="btn btn-outline-secondary" href="{{ route('compras.show', $compra) }}">Voltar à compra #{{ $compra->id }}</a>
    </div>
    @if (session('error')) <div class="alert alert-danger" role="alert">{{ session('error') }}</div> @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert"><ul class="mb-0">
            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
        </ul></div>
    @endif
    <form method="POST" data-feux-devolucao-operacional action="{{ route('compras.devolucoes.store', $compra) }}">
        @csrf
        <input type="hidden" name="operacao_uuid" value="{{ old('operacao_uuid', $operacaoUuid) }}">
        <div class="card mb-4"><div class="card-body">
            <h2 class="h5">Compra #{{ $compra->id }} — {{ $compra->fornecedor?->nome }}</h2>
            <p class="text-body-secondary">NF de compra: {{ $compra->numero_nf }}</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="referencia_documento" class="form-label">Referência do documento de devolução *</label>
                    <input class="form-control" id="referencia_documento" name="referencia_documento"
                           value="{{ old('referencia_documento') }}" maxlength="80" required>
                    <div class="form-text">Documento emitido fora deste sistema; não é emissão fiscal.</div>
                </div>
                <div class="col-md-6">
                    <label for="data_documento" class="form-label">Data do documento *</label>
                    <input type="date" class="form-control" id="data_documento" name="data_documento"
                           value="{{ old('data_documento', now()->toDateString()) }}" required>
                </div>
                <div class="col-12">
                    <label for="chave_nfe" class="form-label">Chave da NF-e de devolução (se já autorizada)</label>
                    <input class="form-control" id="chave_nfe" name="chave_nfe" inputmode="numeric"
                           maxlength="44" pattern="[0-9]{44}" value="{{ old('chave_nfe') }}">
                </div>
                <div class="col-12">
                    <label for="motivo" class="form-label">Motivo detalhado *</label>
                    <textarea class="form-control" id="motivo" name="motivo" rows="3"
                              minlength="5" maxlength="2000" required>{{ old('motivo') }}</textarea>
                </div>
            </div>
        </div></div>
        <div class="card mb-4"><div class="card-body">
            <h2 class="h5">Quantidades efetivamente devolvidas</h2>
            <p class="text-body-secondary">Informe 0 nos itens que não serão devolvidos. Limite baseado na entrada de estoque menos devoluções anteriores; o saldo físico é validado novamente ao salvar.</p>
            <div class="row g-3">
                @foreach ($compra->itens as $item)
                    <div class="col-12 col-lg-6">
                        <div class="border rounded p-3 h-100 feux-compras-v2__item" data-feux-item-operacional data-item-id="{{ $item->id }}" data-limite="{{ $disponiveis[$item->id] }}">
                            <label for="item_{{ $item->id }}" class="form-label fw-semibold d-block">
                                {{ $item->descricao }} (item #{{ $item->id }})
                            </label>
                            <div class="small text-body-secondary mb-2">
                                Máximo disponível desta compra: <strong>{{ $disponiveis[$item->id] }}</strong>
                            </div>
                            <input type="number" class="form-control" id="item_{{ $item->id }}" data-feux-quantidade-operacional
                                   name="itens[{{ $item->id }}]" min="0" step="0.001"
                                   max="{{ $disponiveis[$item->id] }}" inputmode="decimal"
                                   value="{{ old('itens.'.$item->id, '0') }}"
                                   @disabled((float) $disponiveis[$item->id] <= 0)>
                            <p class="small text-danger d-none mt-2 mb-0" role="alert" data-feux-erro-item></p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div></div>
        <div class="alert alert-light border feux-compras-v2__previa" role="status" aria-live="polite">
            <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center">
                <strong>Prévia da devolução operacional</strong>
                <span data-feux-resumo-operacional>Selecione ao menos um item.</span>
            </div>
            <p class="small text-danger mb-0 mt-2 d-none" data-feux-erro-global></p>
            <p class="small text-body-secondary mb-0 mt-2">A prévia não registra estoque nem movimenta Contas a Pagar. O servidor reconfere os limites físicos ao salvar.</p>
        </div>
        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="confirmacao_fisica"
                   name="confirmacao_fisica" value="1" required @checked(old('confirmacao_fisica'))>
            <label class="form-check-label" for="confirmacao_fisica">
                Confirmo que as quantidades informadas correspondem a mercadorias separadas fisicamente,
                e estou ciente de que o estoque será reduzido ao registrar.
            </label>
        </div>
        <button type="submit" class="btn btn-danger" data-feux-submit-operacional>Registrar devolução e baixar estoque</button>
    </form>
</div>
@endsection

@section('scripts')
@vite(['resources/js/compras/devolucao-operacional.js'])
@endsection
