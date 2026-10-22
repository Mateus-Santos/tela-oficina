@extends('layouts.layout')

@section('content')
<section class="container cadastro">
    <h1><i class="bi bi-cash-stack"></i> CONTA A RECEBER #{{ str_pad($contaReceber->id, 6, '0', STR_PAD_LEFT) }}</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- RESUMO FINANCEIRO --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">Valor Original</small>
                    <h4 class="mb-0">R$ {{ number_format($contaReceber->valor_original, 2, ',', '.') }}</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">Valor Devido</small>
                    <h4 class="mb-0">R$ {{ number_format($valorDevido, 2, ',', '.') }}</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">Total Recebido</small>
                    <h4 class="mb-0 text-success">R$ {{ number_format($valorRecebido, 2, ',', '.') }}</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">Saldo</small>
                    <h4 class="mb-0">R$ {{ number_format(max($saldo, 0), 2, ',', '.') }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- DADOS DA CONTA --}}
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-info-circle"></i> Dados da Conta</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <strong>Cliente:</strong><br>
                    {{ $contaReceber->cliente?->pessoa?->nome ?? $contaReceber->nota?->cliente?->pessoa?->nome ?? 'Sem cliente' }}
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Nota:</strong><br>
                    @if($contaReceber->nota)
                        #{{ str_pad($contaReceber->nota->id, 6, '0', STR_PAD_LEFT) }}
                    @else
                        Sem nota vinculada
                    @endif
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Categoria Financeira:</strong><br>
                    {{ $contaReceber->categoriaFinanceira?->nome ?? 'Não informada' }}
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Descrição:</strong><br>
                    {{ $contaReceber->descricao }}
                </div>

                <div class="col-md-3 mb-3">
                    <strong>Emissão:</strong><br>
                    {{ $contaReceber->data_emissao?->format('d/m/Y') ?? '-' }}
                </div>

                <div class="col-md-3 mb-3">
                    <strong>Primeiro Vencimento:</strong><br>
                    {{ $contaReceber->data_vencimento?->format('d/m/Y') ?? '-' }}
                </div>

                <div class="col-md-3 mb-3">
                    <strong>Desconto:</strong><br>
                    R$ {{ number_format($contaReceber->desconto, 2, ',', '.') }}
                </div>

                <div class="col-md-3 mb-3">
                    <strong>Juros + Multa:</strong><br>
                    R$ {{ number_format((float) $contaReceber->juros + (float) $contaReceber->multa, 2, ',', '.') }}
                </div>

                <div class="col-12 mb-3">
                    <strong>Status:</strong><br>
                    @if($contaReceber->status === 'quitada')
                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Quitada</span>
                    @elseif($contaReceber->status === 'cancelada')
                        <span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Cancelada</span>
                    @elseif($vencida)
                        <span class="badge bg-danger"><i class="bi bi-exclamation-circle"></i> Possui parcela vencida</span>
                    @elseif($contaReceber->status === 'parcial')
                        <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Parcial</span>
                    @else
                        <span class="badge bg-primary"><i class="bi bi-clock"></i> Aberta</span>
                    @endif
                </div>

                @if($contaReceber->data_quitacao)
                    <div class="col-md-3 mb-3">
                        <strong>Data de Quitação:</strong><br>
                        {{ $contaReceber->data_quitacao->format('d/m/Y') }}
                    </div>
                @endif

                @if($contaReceber->observacoes)
                    <div class="col-12">
                        <strong>Observações:</strong><br>
                        {!! nl2br(e($contaReceber->observacoes)) !!}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- DOCUMENTOS E ANEXOS --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <span>
                <i class="bi bi-paperclip"></i>
                Documentos e anexos
            </span>

            <span class="badge bg-secondary">
                {{ $contaReceber->anexosVinculos->count() }}
                {{ $contaReceber->anexosVinculos->count() === 1 ? 'arquivo' : 'arquivos' }}
            </span>
        </div>

        <div class="card-body">
            <form
                method="POST"
                action="{{ route('contas-receber.anexos.store', $contaReceber) }}"
                enctype="multipart/form-data"
                class="mb-4"
            >
                @csrf

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label
                            for="tipo-anexo-conta-receber"
                            class="form-label"
                        >
                            Tipo do documento
                        </label>

                        <select
                            name="tipo"
                            id="tipo-anexo-conta-receber"
                            class="form-select"
                            required
                        >
                            <option value="">Selecione...</option>
                            <option value="nf">Nota fiscal</option>
                            <option value="nf_xml">NF-e XML</option>
                            <option value="foto">Foto</option>
                            <option value="comprovante">Comprovante</option>
                            <option value="boleto">Boleto</option>
                            <option value="contrato">Contrato</option>
                            <option value="orcamento">Orçamento</option>
                            <option value="conta_luz">Conta de luz</option>
                            <option value="conta_agua">Conta de água</option>
                            <option value="conta_telefone">Conta de telefone</option>
                            <option value="recibo">Recibo</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-5">
                        <label
                            for="arquivo-anexo-conta-receber"
                            class="form-label"
                        >
                            Arquivo
                        </label>

                        <input
                            type="file"
                            name="arquivo"
                            id="arquivo-anexo-conta-receber"
                            class="form-control"
                            accept=".pdf,.jpg,.jpeg,.png,.webp,.xml"
                            required
                        >

                        <small class="text-muted">
                            PDF, JPG, JPEG, PNG, WEBP ou XML — máximo de 2 MB.
                        </small>
                    </div>

                    <div class="col-12 col-md-3">
                        <label
                            for="observacoes-anexo-conta-receber"
                            class="form-label"
                        >
                            Observações
                        </label>

                        <input
                            type="text"
                            name="observacoes"
                            id="observacoes-anexo-conta-receber"
                            class="form-control"
                            maxlength="1000"
                        >
                    </div>

                    <div class="col-12">
                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-cloud-arrow-up"></i>
                            Enviar anexo
                        </button>

                        @if(in_array($contaReceber->status, ['quitada', 'cancelada'], true))
                            <small class="text-muted ms-2">
                                O encerramento da conta não impede a gestão documental.
                            </small>
                        @endif
                    </div>
                </div>
            </form>

            @if($contaReceber->anexosVinculos->isEmpty())
                <div class="alert alert-light border mb-0">
                    <i class="bi bi-info-circle"></i>
                    Nenhum documento ou anexo cadastrado nesta conta.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Arquivo</th>
                                <th>Tamanho</th>
                                <th>Observações</th>
                                <th>Data</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($contaReceber->anexosVinculos as $vinculo)
                                @php
                                    $anexo = $vinculo->anexo;

                                    $tipoAnexo = match ($vinculo->tipo) {
                                        'nf' => 'Nota fiscal',
                                        'nf_xml' => 'NF-e XML',
                                        'foto' => 'Foto',
                                        'comprovante' => 'Comprovante',
                                        'boleto' => 'Boleto',
                                        'contrato' => 'Contrato',
                                        'orcamento' => 'Orçamento',
                                        'conta_luz' => 'Conta de luz',
                                        'conta_agua' => 'Conta de água',
                                        'conta_telefone' => 'Conta de telefone',
                                        'recibo' => 'Recibo',
                                        default => 'Outro',
                                    };
                                @endphp

                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ $tipoAnexo }}
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
                                        {{ number_format($anexo->tamanho / 1024, 1, ',', '.') }} KB
                                    </td>

                                    <td>
                                        {{ $vinculo->observacoes ?: '-' }}
                                    </td>

                                    <td>
                                        {{ $vinculo->created_at?->format('d/m/Y H:i') }}
                                    </td>

                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a
                                                href="{{ route('anexos.show', $anexo) }}"
                                                class="btn btn-sm btn-outline-secondary"
                                                title="Visualizar anexo"
                                            >
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <a
                                                href="{{ route('anexos.download', $anexo) }}"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Baixar anexo"
                                            >
                                                <i class="bi bi-download"></i>
                                            </a>

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEditarAnexoReceber{{ $vinculo->id }}"
                                                title="Editar anexo"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <form
                                                method="POST"
                                                action="{{ route('anexos.destroy', $vinculo) }}"
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
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @foreach($contaReceber->anexosVinculos as $vinculo)
                    @php
                        $anexo = $vinculo->anexo;
                    @endphp

                    <div
                        class="modal fade"
                        id="modalEditarAnexoReceber{{ $vinculo->id }}"
                        tabindex="-1"
                        aria-hidden="true"
                    >
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <form
                                    method="POST"
                                    action="{{ route('anexos.update', $vinculo) }}"
                                    enctype="multipart/form-data"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="bi bi-pencil-square"></i>
                                            Editar anexo
                                        </h5>

                                        <button
                                            type="button"
                                            class="btn-close"
                                            data-bs-dismiss="modal"
                                            aria-label="Fechar"
                                        ></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">
                                                    Tipo
                                                </label>

                                                <select
                                                    name="tipo"
                                                    class="form-select"
                                                    required
                                                >
                                                    @foreach([
                                                        'nf' => 'Nota fiscal',
                                                        'nf_xml' => 'NF-e XML',
                                                        'foto' => 'Foto',
                                                        'comprovante' => 'Comprovante',
                                                        'boleto' => 'Boleto',
                                                        'contrato' => 'Contrato',
                                                        'orcamento' => 'Orçamento',
                                                        'conta_luz' => 'Conta de luz',
                                                        'conta_agua' => 'Conta de água',
                                                        'conta_telefone' => 'Conta de telefone',
                                                        'recibo' => 'Recibo',
                                                        'outro' => 'Outro',
                                                    ] as $valor => $label)
                                                        <option
                                                            value="{{ $valor }}"
                                                            @selected($vinculo->tipo === $valor)
                                                        >
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">
                                                    Substituir arquivo
                                                </label>

                                                <input
                                                    type="file"
                                                    name="arquivo"
                                                    class="form-control"
                                                    accept=".pdf,.jpg,.jpeg,.png,.webp,.xml"
                                                >

                                                <small class="text-muted">
                                                    Atual: {{ $anexo->nome_original }}.
                                                    Deixe vazio para manter.
                                                </small>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label">
                                                    Observações
                                                </label>

                                                <textarea
                                                    name="observacoes"
                                                    class="form-control"
                                                    rows="3"
                                                    maxlength="1000"
                                                >{{ $vinculo->observacoes }}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button
                                            type="button"
                                            class="btn btn-secondary"
                                            data-bs-dismiss="modal"
                                        >
                                            Cancelar
                                        </button>

                                        <button
                                            type="submit"
                                            class="btn btn-primary"
                                        >
                                            <i class="bi bi-check-lg"></i>
                                            Salvar alterações
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- PARCELAS --}}
    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-calendar-check"></i> Parcelas
            <span class="badge bg-secondary ms-1">{{ $contaReceber->parcelas->count() }}</span>
        </div>

        <div class="card-body p-0">
            @if($contaReceber->parcelas->isEmpty())
                <div class="p-3 text-center text-muted">
                    <i class="bi bi-info-circle"></i> Nenhuma parcela cadastrada.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-3">Parcela</th>
                                <th>Vencimento</th>
                                <th>Valor</th>
                                <th>Recebido</th>
                                <th>Saldo</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contaReceber->parcelas as $parcela)
                                @php
                                    $recebidoParcela = (float) $parcela->recebimentosAtivos->sum('valor');
                                    $saldoParcela = max(0, round((float) $parcela->valor - $recebidoParcela, 2));
                                    $parcelaQuitada = $saldoParcela <= 0;
                                    $parcelaVencida = !$parcelaQuitada && $parcela->data_vencimento->isBefore(today());
                                    $parcelaParcial = !$parcelaQuitada && $recebidoParcela > 0;
                                @endphp

                                <tr>
                                    <td class="ps-3 fw-semibold">{{ $parcela->numero }}/{{ $contaReceber->parcelas->count() }}</td>
                                    <td>{{ $parcela->data_vencimento->format('d/m/Y') }}</td>
                                    <td class="fw-semibold">R$ {{ number_format($parcela->valor, 2, ',', '.') }}</td>
                                    <td class="text-success">R$ {{ number_format($recebidoParcela, 2, ',', '.') }}</td>
                                    <td class="{{ $parcelaVencida ? 'text-danger fw-semibold' : '' }}">R$ {{ number_format($saldoParcela, 2, ',', '.') }}</td>
                                    <td>
                                        @if($contaReceber->status === 'cancelada')
                                            <span class="badge bg-secondary">Cancelada</span>
                                        @elseif($parcelaQuitada)
                                            <span class="badge bg-success">Quitada</span>
                                        @elseif($parcelaVencida)
                                            <span class="badge bg-danger">Vencida</span>
                                        @elseif($parcelaParcial)
                                            <span class="badge bg-warning text-dark">Parcial</span>
                                        @else
                                            <span class="badge bg-primary">Aberta</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        @if($contaReceber->status !== 'cancelada' && !$parcelaQuitada)
                                            <a href="{{ route('recebimentos.create', ['contaReceber' => $contaReceber, 'parcela' => $parcela]) }}" class="btn btn-sm {{ $parcelaVencida ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                                <i class="bi bi-cash-coin"></i> Receber
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
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

    {{-- HISTÓRICO DE RECEBIMENTOS --}}
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-wallet2"></i> Histórico de Recebimentos</div>

        <div class="card-body p-0">
            @if($contaReceber->recebimentos->isEmpty())
                <div class="p-3 text-center text-muted">
                    <i class="bi bi-info-circle"></i> Nenhum recebimento registrado.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-3">ID</th>
                                <th>Parcela</th>
                                <th>Data</th>
                                <th>Forma de Pagamento</th>
                                <th>Valor</th>
                                <th>Usuário</th>
                                <th>Observações</th>
                                <th>Status</th>
                                <th class="pe-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contaReceber->recebimentos as $recebimento)
                                <tr @class(['table-secondary' => $recebimento->estaEstornado()])>
                                    <td class="ps-3">#{{ str_pad($recebimento->id, 6, '0', STR_PAD_LEFT) }}</td>
                                    <td>
                                        @if($recebimento->parcela)
                                            {{ $recebimento->parcela->numero }}/{{ $contaReceber->parcelas->count() }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $recebimento->data_pagamento?->format('d/m/Y H:i') }}</td>
                                    <td>{{ $recebimento->formaPagamento?->nome ?? '-' }}</td>
                                    <td>R$ {{ number_format($recebimento->valor, 2, ',', '.') }}</td>
                                    <td>{{ $recebimento->usuario?->name ?? '-' }}</td>
                                    <td>{{ $recebimento->observacoes ?? '-' }}</td>
                                    <td>
                                        @if($recebimento->estaEstornado())
                                            <span class="badge bg-secondary"><i class="bi bi-arrow-counterclockwise"></i> Estornado</span>
                                            <div class="small text-muted mt-1">{{ $recebimento->estornado_em?->format('d/m/Y H:i') }}</div>
                                        @else
                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Ativo</span>
                                        @endif
                                    </td>
                                    <td class="pe-3">
                                        @if(!$recebimento->estaEstornado() && $contaReceber->status !== 'cancelada')
                                            <form action="{{ route('recebimentos.estornar', ['contaReceber' => $contaReceber, 'recebimento' => $recebimento]) }}" method="POST">
                                                @csrf
                                                <textarea name="motivo" class="form-control form-control-sm mb-2" rows="2" placeholder="Motivo do estorno" minlength="3" maxlength="1000" required></textarea>
                                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Tem certeza que deseja estornar este recebimento? Esta operação ficará registrada no histórico financeiro.');">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Estornar
                                                </button>
                                            </form>
                                        @elseif($recebimento->estaEstornado())
                                            <div class="small text-muted">
                                                <strong>Motivo:</strong><br>
                                                {!! nl2br(e($recebimento->motivo_estorno)) !!}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Total recebido:</th>
                                <th>R$ {{ number_format($valorRecebido, 2, ',', '.') }}</th>
                                <th colspan="4"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="col text-center">
        <a href="{{ route('contas-receber.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>

        @if(!$contaReceber->recebimentos->count())
            <a href="{{ route('contas-receber.edit', $contaReceber) }}" class="btn btn-primary">
                <i class="bi bi-pencil-square"></i> Editar
            </a>

            <form action="{{ route('contas-receber.destroy', $contaReceber) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta conta a receber?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash"></i> Excluir
                </button>
            </form>
        @endif
    </div>
</section>
@endsection
