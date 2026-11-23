@extends('layouts.layout')

@section('content')
<div class="container cadastro feux-compra">
    <x-list-header title="DEVOLUÇÃO DOCUMENTADA DE COMPRA" icon="bi-arrow-return-left" />

    <div class="alert alert-warning text-start" role="alert">
        <strong>Registro interno, não é emissão de NF-e.</strong>
        Informe a referência de documento emitido externamente e autorizado, quando aplicável.
        A NF de entrada permanece com seu valor original. Para itens que <strong>não foram aceitos</strong>,
        o sistema não movimenta estoque; para itens <strong>já lançados</strong>, registra uma saída.
        Contas com pagamentos ou inconsistências ficam com abatimento financeiro pendente de conciliação.
    </div>

    <a class="btn btn-outline-secondary mb-3" href="{{ route('compras.show', $compra) }}">
        Voltar à compra #{{ $compra->id }}
    </a>

    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger text-start" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card mb-3"><div class="card-body text-start">
        <h2 class="h5">Compra #{{ $compra->id }} — {{ $compra->fornecedor?->nome }}</h2>
        <p class="mb-1">NF de entrada: <strong>{{ $compra->numero_nf }}</strong></p>
        <p class="mb-1">Valor original: R$ {{ number_format((float) $compra->valor_total, 2, ',', '.') }}</p>
        <p class="mb-0">Valor líquido já reconhecido: <strong>R$ {{ number_format($valorLiquido, 2, ',', '.') }}</strong></p>
    </div></div>

    <form method="POST" action="{{ route('compras.devolucoes.documental.store', $compra) }}">
        @csrf
        <input type="hidden" name="operacao_uuid" value="{{ old('operacao_uuid', $operacaoUuid) }}">

        <div class="card mb-3"><div class="card-body text-start">
            <h2 class="h5">Documento de devolução</h2>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="referencia_documento" class="form-label">Número ou referência do documento *</label>
                    <input class="form-control" type="text" name="referencia_documento"
                        id="referencia_documento" maxlength="80" required
                        value="{{ old('referencia_documento') }}">
                </div>
                <div class="col-12 col-md-6">
                    <label for="data_documento" class="form-label">Data *</label>
                    <input class="form-control" type="date" name="data_documento" id="data_documento" required
                        value="{{ old('data_documento', now()->toDateString()) }}">
                </div>
                <div class="col-12">
                    <label for="chave_nfe" class="form-label">Chave da NF-e de devolução (se existente)</label>
                    <input class="form-control" type="text" name="chave_nfe" id="chave_nfe"
                        maxlength="44" pattern="[0-9]{44}" inputmode="numeric"
                        value="{{ old('chave_nfe') }}">
                </div>
                <div class="col-12">
                    <label for="motivo" class="form-label">Motivo e referência da NF original *</label>
                    <textarea class="form-control" name="motivo" id="motivo"
                        minlength="5" maxlength="2000" rows="3" required>{{ old('motivo') }}</textarea>
                </div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-body text-start">
            <h2 class="h5">Itens e tratamento</h2>
            <p>Selecione <strong>Recusado, sem entrada</strong> para a bomba errada que não entrou no estoque.
                Selecione <strong>Já entrou, devolver ao fornecedor</strong> somente para mercadorias realmente existentes no estoque.</p>
            <div class="row g-3">
                @foreach($compra->itens as $item)
                    @php
                        $saldo = $saldos[$item->id];
                        $podeDevolver = $saldo['estoque'] > 0 || $saldo['recusado'] > 0;
                        $modoPadrao = $saldo['recusado'] > 0 ? 'recusado' : 'estoque';
                    @endphp
                    <div class="col-12 col-xl-6">
                        <div class="border rounded p-3 h-100">
                            <label class="form-label d-block fw-bold" for="quantidade_{{ $item->id }}">
                                {{ $item->descricao }} · item #{{ $item->id }}
                            </label>
                            <p class="small text-muted mb-2">
                                NF: {{ $item->quantidade }} · Disponível em estoque: {{ \App\Support\Estoque\Quantidade::formatar($saldo['estoque']) }}
                                · Recusável sem entrada: {{ \App\Support\Estoque\Quantidade::formatar($saldo['recusado']) }}
                            </p>
                            <div class="mb-2">
                                <label class="form-label" for="modalidade_{{ $item->id }}">Tratamento</label>
                                <select class="form-select" name="itens[{{ $item->id }}][modalidade]"
                                        id="modalidade_{{ $item->id }}" @disabled(!$podeDevolver)>
                                    <option value="recusado" @selected(old('itens.'.$item->id.'.modalidade', $modoPadrao) === 'recusado')>
                                        Recusado, sem entrada no estoque
                                    </option>
                                    <option value="estoque" @selected(old('itens.'.$item->id.'.modalidade', $modoPadrao) === 'estoque')>
                                        Já entrou: baixar do estoque
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="quantidade_{{ $item->id }}">Quantidade</label>
                                <input class="form-control" type="number" min="0" step="0.001"
                                    id="quantidade_{{ $item->id }}" name="itens[{{ $item->id }}][quantidade]"
                                    value="{{ old('itens.'.$item->id.'.quantidade', '0') }}"
                                    @disabled(!$podeDevolver)>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div></div>

        <div class="form-check text-start mb-2">
            <input class="form-check-input" id="confirmacao_fisica" name="confirmacao_fisica"
                type="checkbox" value="1" required @checked(old('confirmacao_fisica'))>
            <label class="form-check-label" for="confirmacao_fisica">
                Confirmo o destino físico de cada item: recusado sem entrada ou separado para devolver após entrada.
            </label>
        </div>
        <div class="form-check text-start mb-3">
            <input class="form-check-input" id="confirmacao_financeira" name="confirmacao_financeira"
                type="checkbox" value="1" required @checked(old('confirmacao_financeira'))>
            <label class="form-check-label" for="confirmacao_financeira">
                Confirmei a devolução com o fornecedor e entendo que o ajuste de Conta a Pagar pode ficar pendente.
            </label>
        </div>
        <button class="btn btn-danger" type="submit"
            onclick="return confirm('Registrar esta devolução? A saída física será irreversível por esta tela.');">
            Registrar devolução documentada
        </button>
    </form>
</div>
@endsection
