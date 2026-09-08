@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="DETALHES DA CONTA A PAGAR"
        icon="bi-wallet2"
    />

    @if (session('success'))
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i>
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Não foi possível realizar a operação.</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $valor = (float) $conta->valor;
        $valorPago = (float) $conta->valor_pago;
        $saldo = max(0, round($valor - $valorPago, 2));

        if ($conta->status === 'cancelada') {
            $statusConfig = [
                'class' => 'bg-danger',
                'icon' => 'bi-x-circle',
                'label' => 'Cancelada',
            ];
        } elseif ($saldo <= 0) {
            $statusConfig = [
                'class' => 'bg-success',
                'icon' => 'bi-check-circle',
                'label' => 'Paga',
            ];
        } elseif ($conta->data_vencimento->isBefore(today())) {
            $statusConfig = [
                'class' => 'bg-danger',
                'icon' => 'bi-exclamation-circle',
                'label' => 'Vencida',
            ];
        } elseif ($valorPago > 0) {
            $statusConfig = [
                'class' => 'bg-info text-dark',
                'icon' => 'bi-hourglass-split',
                'label' => 'Parcialmente paga',
            ];
        } else {
            $statusConfig = [
                'class' => 'bg-warning text-dark',
                'icon' => 'bi-clock',
                'label' => 'Aberta',
            ];
        }
    @endphp

    <div class="mb-3">
        <a href="{{ route('contas-pagar.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i>
            Voltar
        </a>

        @if ($conta->status !== 'cancelada')
            <a href="{{ route('contas-pagar.edit', $conta) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i>
                Editar
            </a>
        @endif
    </div>

    <div class="row g-3 mb-4">

        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">
                        <i class="bi bi-wallet2"></i>
                        Valor da conta
                    </small>
                    <h3 class="mt-2 mb-0">
                        R$ {{ number_format($valor, 2, ',', '.') }}
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">
                        <i class="bi bi-cash-coin"></i>
                        Total pago
                    </small>
                    <h3 class="mt-2 mb-0">
                        R$ {{ number_format($valorPago, 2, ',', '.') }}
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">
                        <i class="bi bi-hourglass-split"></i>
                        Saldo
                    </small>
                    <h3 class="mt-2 mb-0">
                        R$ {{ number_format($saldo, 2, ',', '.') }}
                    </h3>
                </div>
            </div>
        </div>

    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-info-circle"></i>
            Dados da conta
        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-12 col-md-6">
                    <strong>Descrição</strong>
                    <div>{{ $conta->descricao }}</div>
                </div>

                <div class="col-12 col-md-6">
                    <strong>Status</strong>
                    <div class="mt-1">
                        <span class="badge {{ $statusConfig['class'] }}">
                            <i class="bi {{ $statusConfig['icon'] }}"></i>
                            {{ $statusConfig['label'] }}
                        </span>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <strong>Fornecedor</strong>
                    <div>{{ $conta->fornecedor->nome ?? 'Não informado' }}</div>
                </div>

                <div class="col-12 col-md-4">
                    <strong>Categoria financeira</strong>
                    <div>
                        @if ($conta->categoriaFinanceira)
                            <i class="bi bi-tags"></i>
                            {{ $conta->categoriaFinanceira->nome }}
                        @else
                            Não definida
                        @endif
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <strong>Forma de pagamento planejada</strong>
                    <div>
                        @if ($conta->formaPagamento)
                            <i class="bi bi-credit-card"></i>
                            {{ $conta->formaPagamento->nome }}
                        @else
                            Não definida
                        @endif
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <strong>Data de emissão</strong>
                    <div>{{ $conta->data_emissao?->format('d/m/Y') ?? '-' }}</div>
                </div>

                <div class="col-12 col-md-4">
                    <strong>Data de vencimento</strong>
                    <div>
                        {{ $conta->data_vencimento?->format('d/m/Y') ?? '-' }}

                        @if ($conta->status !== 'cancelada' && $saldo > 0 && $conta->data_vencimento->isBefore(today()))
                            <span class="text-danger">
                                <i class="bi bi-exclamation-triangle"></i>
                                Em atraso
                            </span>
                        @endif
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <strong>Nota vinculada</strong>
                    <div>
                        @if ($conta->nota)
                            <i class="bi bi-receipt"></i>
                            #{{ $conta->nota->id }}
                        @else
                            Não vinculada
                        @endif
                    </div>
                </div>

                @if ($conta->observacoes)
                    <div class="col-12">
                        <strong>Observações</strong>
                        <div class="mt-1">
                            {!! nl2br(e($conta->observacoes)) !!}
                        </div>
                    </div>
                @endif

            </div>

        </div>
    </div>

    {{-- ANEXOS --}}
    <div class="card mb-4">
        <div class="card-body">

            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                <h2 class="h5 mb-0">
                    <i class="bi bi-paperclip"></i>
                    Documentos e anexos
                </h2>

                <span class="badge bg-secondary">
                    <i class="bi bi-files"></i>
                    {{ $conta->anexos->count() }}
                    {{ $conta->anexos->count() === 1 ? 'arquivo' : 'arquivos' }}
                </span>
            </div>

            @if ($conta->status !== 'cancelada')

                <form
                    method="POST"
                    action="{{ route('contas-pagar.anexos.store', $conta) }}"
                    enctype="multipart/form-data"
                    class="mb-4"
                >
                    @csrf

                    <div class="row g-3">

                        <div class="col-12 col-md-4">
                            <label for="tipo_anexo_conta" class="form-label">
                                Tipo do documento
                            </label>

                            <select
                                name="tipo"
                                id="tipo_anexo_conta"
                                class="form-select"
                                required
                            >
                                <option value="">Selecione...</option>

                                <option value="boleto" @selected(old('tipo') === 'boleto')>
                                    Boleto
                                </option>

                                <option value="comprovante" @selected(old('tipo') === 'comprovante')>
                                    Comprovante de pagamento
                                </option>

                                <option value="nf" @selected(old('tipo') === 'nf')>
                                    Nota fiscal
                                </option>

                                <option value="nf_xml" @selected(old('tipo') === 'nf_xml')>
                                    NF-e XML
                                </option>

                                <option value="recibo" @selected(old('tipo') === 'recibo')>
                                    Recibo
                                </option>

                                <option value="contrato" @selected(old('tipo') === 'contrato')>
                                    Contrato
                                </option>

                                <option value="outro" @selected(old('tipo') === 'outro')>
                                    Outro
                                </option>
                            </select>
                        </div>

                        <div class="col-12 col-md-5">
                            <label for="arquivo_anexo_conta" class="form-label">
                                Arquivo
                            </label>

                            <input
                                type="file"
                                name="arquivo"
                                id="arquivo_anexo_conta"
                                class="form-control"
                                accept=".pdf,.jpg,.jpeg,.png,.webp,.xml"
                                required
                            >

                            <small class="text-muted">
                                PDF, JPG, JPEG, PNG, WEBP ou XML — máximo de 20 MB.
                            </small>
                        </div>

                        <div class="col-12 col-md-3">
                            <label for="observacoes_anexo_conta" class="form-label">
                                Observações
                            </label>

                            <input
                                type="text"
                                name="observacoes"
                                id="observacoes_anexo_conta"
                                class="form-control"
                                maxlength="1000"
                                value="{{ old('observacoes') }}"
                            >
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-cloud-arrow-up"></i>
                                Enviar anexo
                            </button>
                        </div>

                    </div>
                </form>

            @else

                <div class="alert alert-warning">
                    <i class="bi bi-lock"></i>
                    Esta conta está cancelada. Novos anexos não podem ser adicionados.
                </div>

            @endif

            @if ($conta->anexos->isNotEmpty())

                <div class="table-responsive">

                    <table class="table table-striped table-hover align-middle mb-0">

                        <thead>
                            <tr>
                                <th>TIPO</th>
                                <th>ARQUIVO</th>
                                <th>TAMANHO</th>
                                <th>OBSERVAÇÕES</th>
                                <th>DATA</th>
                                <th class="text-end">AÇÕES</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach ($conta->anexos as $anexo)

                                @php
                                    $tipoAnexo = match ($anexo->tipo) {
                                        'nf' => ['label' => 'Nota fiscal', 'icon' => 'bi-receipt'],
                                        'nf_xml' => ['label' => 'NF-e XML', 'icon' => 'bi-filetype-xml'],
                                        'foto' => ['label' => 'Foto', 'icon' => 'bi-image'],
                                        'comprovante' => ['label' => 'Comprovante de pagamento', 'icon' => 'bi-file-earmark-check'],
                                        'boleto' => ['label' => 'Boleto', 'icon' => 'bi-upc'],
                                        'contrato' => ['label' => 'Contrato', 'icon' => 'bi-file-earmark-text'],
                                        'orcamento' => ['label' => 'Orçamento', 'icon' => 'bi-file-earmark-spreadsheet'],
                                        'conta_luz' => ['label' => 'Conta de luz', 'icon' => 'bi-lightbulb'],
                                        'conta_agua' => ['label' => 'Conta de água', 'icon' => 'bi-droplet'],
                                        'conta_telefone' => ['label' => 'Conta de telefone', 'icon' => 'bi-telephone'],
                                        'recibo' => ['label' => 'Recibo', 'icon' => 'bi-file-earmark-check'],
                                        default => ['label' => 'Outro', 'icon' => 'bi-file-earmark'],
                                    };
                                @endphp

                                <tr>

                                    <td>
                                        <span class="badge bg-secondary">
                                            <i class="bi {{ $tipoAnexo['icon'] }}"></i>
                                            {{ $tipoAnexo['label'] }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-break">
                                            {{ $anexo->nome_original }}
                                        </div>

                                        <small class="text-muted">
                                            {{ $anexo->mime_type }}
                                        </small>
                                    </td>

                                    <td>
                                        {{ number_format($anexo->tamanho / 1024 / 1024, 2, ',', '.') }} MB
                                    </td>

                                    <td>
                                        {{ $anexo->observacoes ?: '-' }}
                                    </td>

                                    <td>
                                        {{ $anexo->created_at?->format('d/m/Y H:i') }}
                                    </td>

                                    <td>
                                        <div class="d-flex justify-content-end gap-1">

                                            <a
                                                href="{{ route('anexos.download', $anexo) }}"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Baixar arquivo"
                                            >
                                                <i class="bi bi-download"></i>
                                            </a>

                                            @if (!in_array($conta->status, ['paga', 'cancelada'], true))

                                                <form
                                                    method="POST"
                                                    action="{{ route('anexos.destroy', $anexo) }}"
                                                    onsubmit="return confirm('Tem certeza que deseja excluir este anexo?');"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Excluir anexo"
                                                    >
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>

                                            @endif

                                        </div>
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @else

                <div class="alert alert-light border mb-0">
                    <i class="bi bi-info-circle"></i>
                    Nenhum documento ou anexo foi cadastrado para esta conta.
                </div>

            @endif

        </div>
    </div>

    @if ($conta->status !== 'cancelada' && $saldo > 0)

        <div class="card mb-4" id="registrar-pagamento">

            <div class="card-header">
                <i class="bi bi-cash-coin"></i>
                Registrar pagamento
            </div>

            <div class="card-body">

                <form
                    method="POST"
                    action="{{ route('contas-pagar.pagamentos.store', $conta) }}"
                >
                    @csrf

                    <div class="row g-3">

                        <div class="col-12 col-md-4">
                            <label for="valor_pagamento" class="form-label">
                                Valor do pagamento
                            </label>

                            <input
                                type="number"
                                name="valor"
                                id="valor_pagamento"
                                class="form-control @error('valor') is-invalid @enderror"
                                min="0.01"
                                max="{{ number_format($saldo, 2, '.', '') }}"
                                step="0.01"
                                value="{{ old('valor', number_format($saldo, 2, '.', '')) }}"
                                required
                            >

                            @error('valor')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <small class="text-muted">
                                Saldo disponível:
                                R$ {{ number_format($saldo, 2, ',', '.') }}
                            </small>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="data_pagamento" class="form-label">
                                Data do pagamento
                            </label>

                            <input
                                type="date"
                                name="data_pagamento"
                                id="data_pagamento"
                                class="form-control @error('data_pagamento') is-invalid @enderror"
                                value="{{ old('data_pagamento', today()->format('Y-m-d')) }}"
                                required
                            >

                            @error('data_pagamento')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-4">
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

                                @foreach ($formasPagamento as $formaPagamento)
                                    <option
                                        value="{{ $formaPagamento->id }}"
                                        @selected(
                                            (string) old(
                                                'forma_pagamento_id',
                                                $conta->forma_pagamento_id ?? ''
                                            ) === (string) $formaPagamento->id
                                        )
                                    >
                                        {{ $formaPagamento->nome }}
                                    </option>
                                @endforeach

                            </select>

                            @error('forma_pagamento_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <small class="text-muted">
                                A forma utilizada neste pagamento pode ser diferente da planejada.
                            </small>
                        </div>

                        <div class="col-12">
                            <label for="observacoes_pagamento" class="form-label">
                                Observações
                            </label>

                            <textarea
                                name="observacoes"
                                id="observacoes_pagamento"
                                class="form-control @error('observacoes') is-invalid @enderror"
                                rows="3"
                            >{{ old('observacoes') }}</textarea>

                            @error('observacoes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-cash-coin"></i>
                                Registrar pagamento
                            </button>
                        </div>

                    </div>

                </form>

            </div>

        </div>

    @endif

    <div class="card mb-4">

        <div class="card-header">
            <i class="bi bi-clock-history"></i>
            Histórico de pagamentos
        </div>

        <div class="card-body">

            @if ($conta->pagamentos->isEmpty())

                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i>
                    Nenhum pagamento registrado para esta conta.
                </div>

            @else

                <div class="table-responsive">

                    <table class="table table-striped table-hover align-middle mb-0">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>DATA</th>
                                <th>VALOR</th>
                                <th>FORMA</th>
                                <th>STATUS</th>
                                <th>OBSERVAÇÕES</th>
                                <th>AÇÕES</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach ($conta->pagamentos as $pagamento)

                                <tr>

                                    <td>{{ $pagamento->id }}</td>

                                    <td>
                                        {{ $pagamento->data_pagamento?->format('d/m/Y') ?? '-' }}
                                    </td>

                                    <td>
                                        <strong>
                                            R$ {{ number_format((float) $pagamento->valor, 2, ',', '.') }}
                                        </strong>
                                    </td>

                                    <td>
                                        {{ $pagamento->formaPagamento?->nome ?? $pagamento->forma_pagamento ?? '-' }}
                                    </td>

                                    <td>

                                        @if ($pagamento->estaEstornado())

                                            <span class="badge bg-danger">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                                Estornado
                                            </span>

                                            @if ($pagamento->estornado_em)
                                                <br>
                                                <small class="text-muted">
                                                    {{ $pagamento->estornado_em->format('d/m/Y H:i') }}
                                                </small>
                                            @endif

                                        @else

                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i>
                                                Ativo
                                            </span>

                                        @endif

                                    </td>

                                    <td>

                                        @if ($pagamento->observacoes)
                                            {{ $pagamento->observacoes }}
                                        @else
                                            -
                                        @endif

                                        @if ($pagamento->motivo_estorno)
                                            <br>
                                            <small class="text-danger">
                                                <strong>Motivo do estorno:</strong>
                                                {{ $pagamento->motivo_estorno }}
                                            </small>
                                        @endif

                                    </td>

                                    <td>

                                        @if (!$pagamento->estaEstornado() && $conta->status !== 'cancelada')

                                            <button
                                                type="button"
                                                class="btn btn-danger btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEstorno{{ $pagamento->id }}"
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

    @if ($conta->status !== 'cancelada')

        <div class="card border-danger">

            <div class="card-header text-danger">
                <i class="bi bi-exclamation-triangle"></i>
                Cancelar conta
            </div>

            <div class="card-body">

                @if ($valorPago > 0)

                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle"></i>
                        Esta conta possui pagamentos ativos. Para cancelá-la,
                        primeiro estorne todos os pagamentos.
                    </div>

                @else

                    <p>
                        O cancelamento não excluirá a conta nem seu histórico.
                        Informe o motivo para registrar a operação.
                    </p>

                    <form
                        method="POST"
                        action="{{ route('contas-pagar.cancelar', $conta) }}"
                        onsubmit="return confirm('Tem certeza que deseja cancelar esta conta?');"
                    >
                        @csrf

                        <div class="mb-3">

                            <label for="motivo_cancelamento" class="form-label">
                                Motivo do cancelamento
                            </label>

                            <textarea
                                name="motivo"
                                id="motivo_cancelamento"
                                class="form-control"
                                rows="3"
                                maxlength="1000"
                                required
                            ></textarea>

                        </div>

                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle"></i>
                            Cancelar conta
                        </button>

                    </form>

                @endif

            </div>

        </div>

    @endif

</div>

@foreach ($conta->pagamentos as $pagamento)

    @if (!$pagamento->estaEstornado() && $conta->status !== 'cancelada')

        <div
            class="modal fade"
            id="modalEstorno{{ $pagamento->id }}"
            tabindex="-1"
            aria-labelledby="modalEstornoLabel{{ $pagamento->id }}"
            aria-hidden="true"
        >

            <div class="modal-dialog">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5
                            class="modal-title"
                            id="modalEstornoLabel{{ $pagamento->id }}"
                        >
                            <i class="bi bi-arrow-counterclockwise"></i>
                            Estornar pagamento
                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Fechar"
                        ></button>

                    </div>

                    <form
                        method="POST"
                        action="{{ route('contas-pagar.pagamentos.estornar', [$conta, $pagamento]) }}"
                    >
                        @csrf

                        <div class="modal-body">

                            <p>
                                Pagamento:
                                <strong>
                                    R$ {{ number_format((float) $pagamento->valor, 2, ',', '.') }}
                                </strong>
                            </p>

                            <div class="mb-3">

                                <label
                                    for="motivo_estorno_{{ $pagamento->id }}"
                                    class="form-label"
                                >
                                    Motivo do estorno
                                </label>

                                <textarea
                                    name="motivo"
                                    id="motivo_estorno_{{ $pagamento->id }}"
                                    class="form-control"
                                    rows="4"
                                    maxlength="1000"
                                    required
                                ></textarea>

                            </div>

                        </div>

                        <div class="modal-footer">

                            <button
                                type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal"
                            >
                                <i class="bi bi-x-lg"></i>
                                Fechar
                            </button>

                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                Confirmar estorno
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    @endif

@endforeach

@endsection
