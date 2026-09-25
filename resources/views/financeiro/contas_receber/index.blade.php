@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <x-list-header
        title="LISTAR CONTAS A RECEBER"
        icon="bi-cash-stack"
        create-route="contas-receber.create"
        create-text="Nova Conta"
        create-icon="bi-plus-lg"
    />

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- RESUMO --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small text-uppercase">Valor total</div>
                            <div class="fs-4 fw-bold">R$ {{ number_format($resumo['valor_total'], 2, ',', '.') }}</div>
                        </div>
                        <i class="bi bi-cash-stack fs-2 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small text-uppercase">Contas</div>
                            <div class="fs-4 fw-bold">{{ $resumo['total'] }}</div>
                        </div>
                        <i class="bi bi-receipt fs-2 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small text-uppercase">Em aberto</div>
                            <div class="fs-4 fw-bold">{{ $resumo['em_aberto'] }}</div>
                        </div>
                        <i class="bi bi-hourglass-split fs-2 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small text-uppercase">Total vencido</div>
                            <div class="fs-4 fw-bold text-danger">R$ {{ number_format($resumo['total_vencido'], 2, ',', '.') }}</div>
                            @if($resumo['vencidas'] > 0)
                                <div class="small text-muted">
                                    {{ $resumo['vencidas'] }} {{ $resumo['vencidas'] === 1 ? 'parcela vencida' : 'parcelas vencidas' }}
                                </div>
                            @endif
                        </div>
                        <i class="bi bi-exclamation-circle fs-2 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTROS --}}
    <x-filtros-container
        action="{{ route('contas-receber.index') }}"
        id="filtros-contas-receber"
        :collapsible="true"
        :expanded="request()->hasAny(['data_inicio', 'data_fim'])"
    >
        <x-slot:primary>
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="cliente" class="form-label"><i class="bi bi-person"></i> Cliente</label>
                    <input type="text" id="cliente" name="cliente" class="filtros-container__input" placeholder="Nome do cliente" value="{{ request('cliente') }}">
                </div>

                <div class="col-12 col-md-3">
                    <label for="status" class="form-label"><i class="bi bi-info-circle"></i> Status</label>
                    <select id="status" name="status" class="filtros-container__select">
                        <option value="">Todos</option>
                        <option value="aberta" @selected(request('status') === 'aberta')>Aberta</option>
                        <option value="parcial" @selected(request('status') === 'parcial')>Parcial</option>
                        <option value="quitada" @selected(request('status') === 'quitada')>Quitada</option>
                        <option value="vencida" @selected(request('status') === 'vencida')>Vencida</option>
                        <option value="cancelada" @selected(request('status') === 'cancelada')>Cancelada</option>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label for="nota_id" class="form-label"><i class="bi bi-receipt"></i> Nota</label>
                    <input type="number" id="nota_id" name="nota_id" class="filtros-container__input" placeholder="Nº da nota" value="{{ request('nota_id') }}" min="1">
                </div>

                <div class="col-12 col-md-2">
                    <div class="filtros-container__actions">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button>
                        <a href="{{ route('contas-receber.index') }}" class="btn btn-outline-secondary" title="Limpar filtros">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
        </x-slot:primary>

        <x-slot:advanced>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="data_inicio" class="form-label"><i class="bi bi-calendar-event"></i> Vencimento de</label>
                    <input type="date" id="data_inicio" name="data_inicio" class="filtros-container__input" value="{{ request('data_inicio') }}">
                </div>

                <div class="col-12 col-md-6">
                    <label for="data_fim" class="form-label"><i class="bi bi-calendar-event"></i> Vencimento até</label>
                    <input type="date" id="data_fim" name="data_fim" class="filtros-container__input" value="{{ request('data_fim') }}">
                </div>
            </div>
        </x-slot:advanced>
    </x-filtros-container>

    {{-- RESULTADOS --}}
    @if($parcelasReceber->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-cash-stack fs-1 text-muted"></i>
                <h5 class="mt-3">Nenhum recebimento encontrado</h5>

                @if(request()->hasAny(['cliente', 'status', 'nota_id', 'data_inicio', 'data_fim']))
                    <p class="text-muted mb-3">Nenhuma parcela corresponde aos filtros informados.</p>
                    <a href="{{ route('contas-receber.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg me-1"></i> Limpar filtros
                    </a>
                @else
                    <p class="text-muted mb-3">Ainda não existem parcelas de contas a receber cadastradas.</p>
                    <a href="{{ route('contas-receber.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Nova Conta
                    </a>
                @endif
            </div>
        </div>
    @else
        @php
            $gruposPorVencimento = $parcelasReceber->getCollection()->groupBy(fn ($parcela) => $parcela->data_vencimento->format('Y-m-d'));
        @endphp

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Agenda de recebimentos</h5>
                <div class="text-muted small">
                    {{ $parcelasReceber->total() }} {{ $parcelasReceber->total() === 1 ? 'parcela encontrada' : 'parcelas encontradas' }}
                </div>
            </div>
        </div>

        @foreach($gruposPorVencimento as $parcelasDoDia)
            @php
                $dataVencimento = $parcelasDoDia->first()->data_vencimento;
                $totalGrupo = $parcelasDoDia->sum(fn ($parcela) => (float) $parcela->valor);
                $recebidoGrupo = $parcelasDoDia->sum(fn ($parcela) => (float) ($parcela->valor_recebido ?? 0));
                $saldoGrupo = $parcelasDoDia->sum(fn ($parcela) => max(0, round((float) $parcela->valor - (float) ($parcela->valor_recebido ?? 0), 2)));
                $estaVencido = $dataVencimento->isBefore(today()) && $saldoGrupo > 0;
                $venceHoje = $dataVencimento->isToday() && $saldoGrupo > 0;
            @endphp

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-lg-4">
                            <div class="d-flex align-items-center gap-2">
                                @if($estaVencido)
                                    <i class="bi bi-exclamation-circle-fill text-danger fs-4"></i>
                                @elseif($venceHoje)
                                    <i class="bi bi-calendar-event-fill text-warning fs-4"></i>
                                @else
                                    <i class="bi bi-calendar3 text-primary fs-4"></i>
                                @endif

                                <div>
                                    <div class="fw-bold fs-5">{{ $dataVencimento->format('d/m/Y') }}</div>
                                    <div class="small text-muted">
                                        {{ $parcelasDoDia->count() }} {{ $parcelasDoDia->count() === 1 ? 'parcela' : 'parcelas' }}

                                        @if($estaVencido)
                                            <span class="badge bg-danger ms-1">VENCIDO</span>
                                        @elseif($venceHoje)
                                            <span class="badge bg-warning text-dark ms-1">VENCE HOJE</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-8">
                            <div class="row g-2 text-lg-end">
                                <div class="col-4">
                                    <div class="small text-muted">Total</div>
                                    <div class="fw-bold">R$ {{ number_format($totalGrupo, 2, ',', '.') }}</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Recebido</div>
                                    <div class="fw-bold text-success">R$ {{ number_format($recebidoGrupo, 2, ',', '.') }}</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Saldo</div>
                                    <div class="fw-bold {{ $estaVencido ? 'text-danger' : '' }}">R$ {{ number_format($saldoGrupo, 2, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Conta</th>
                                <th>Parcela</th>
                                <th>Cliente</th>
                                <th>Descrição</th>
                                <th>Nota</th>
                                <th>Valor</th>
                                <th>Recebido</th>
                                <th>Saldo</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($parcelasDoDia as $parcela)
                                @php
                                    $contaReceber = $parcela->contaReceber;
                                    $valorParcela = (float) $parcela->valor;
                                    $valorRecebido = (float) ($parcela->valor_recebido ?? 0);
                                    $saldo = max(0, round($valorParcela - $valorRecebido, 2));
                                    $nomeCliente = $contaReceber->cliente?->pessoa?->nome ?? $contaReceber->nota?->cliente?->pessoa?->nome ?? 'Sem cliente';
                                    $estaCancelada = $contaReceber->status === 'cancelada';
                                    $estaQuitada = !$estaCancelada && $saldo <= 0;
                                    $estaVencida = !$estaCancelada && !$estaQuitada && $parcela->data_vencimento->isBefore(today());
                                    $estaParcial = !$estaCancelada && !$estaQuitada && $valorRecebido > 0;
                                    $totalParcelas = $contaReceber->parcelas_count ?? 1;
                                @endphp

                                <tr>
                                    <td class="ps-3 text-nowrap">#{{ str_pad($contaReceber->id, 6, '0', STR_PAD_LEFT) }}</td>
                                    <td class="text-nowrap"><span class="fw-semibold">{{ $parcela->numero }}/{{ $totalParcelas }}</span></td>
                                    <td><div class="fw-semibold">{{ $nomeCliente }}</div></td>
                                    <td><div class="fw-semibold">{{ $contaReceber->descricao }}</div></td>
                                    <td>
                                        @if($contaReceber->nota)
                                            <span class="fw-semibold">#{{ str_pad($contaReceber->nota->id, 6, '0', STR_PAD_LEFT) }}</span>
                                        @else
                                            <span class="text-muted">Sem nota</span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold text-nowrap">R$ {{ number_format($valorParcela, 2, ',', '.') }}</td>
                                    <td class="text-success text-nowrap">R$ {{ number_format($valorRecebido, 2, ',', '.') }}</td>
                                    <td class="fw-semibold text-nowrap {{ $estaVencida ? 'text-danger' : '' }}">R$ {{ number_format($saldo, 2, ',', '.') }}</td>
                                    <td>
                                        @if($estaCancelada)
                                            <span class="badge bg-secondary">Cancelada</span>
                                        @elseif($estaQuitada)
                                            <span class="badge bg-success">Quitada</span>
                                        @elseif($estaVencida)
                                            <span class="badge bg-danger">Vencida</span>
                                        @elseif($estaParcial)
                                            <span class="badge bg-warning text-dark">Parcial</span>
                                        @else
                                            <span class="badge bg-primary">Aberta</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('contas-receber.show', $contaReceber) }}" class="btn btn-sm btn-outline-primary" title="Visualizar conta">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            @if(!$contaReceber->recebimentos_exists && !$estaCancelada)
                                                <a href="{{ route('contas-receber.edit', $contaReceber) }}" class="btn btn-sm btn-outline-secondary" title="Editar conta">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            @endif

                                            @if(!$estaQuitada && !$estaCancelada && $saldo > 0)
                                                <a
                                                    href="{{ route('recebimentos.create', ['contaReceber' => $contaReceber, 'parcela' => $parcela]) }}"
                                                    class="btn btn-sm {{ $estaVencida ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                    title="{{ $estaVencida ? 'Registrar recebimento de parcela vencida' : 'Registrar recebimento' }}"
                                                >
                                                    <i class="bi bi-cash-coin"></i>
                                                </a>
                                            @endif

                                            @if(!$contaReceber->recebimentos_exists)
                                                <form action="{{ route('contas-receber.destroy', $contaReceber) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta conta a receber?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir conta">
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
            </div>
        @endforeach

        <div class="mt-4">{{ $parcelasReceber->links() }}</div>
    @endif
</div>
@endsection
