@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <x-list-header
        title="LISTAR CONTAS A PAGAR"
        icon="bi-wallet2"
        create-route="contas-pagar.create"
        create-text="Nova Conta"
        create-icon="bi-plus-lg"
    />

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small text-uppercase">Total</div>
                            <div class="fs-4 fw-bold">
                                R$ {{ number_format($resumo['total'], 2, ',', '.') }}
                            </div>
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
                            <div class="text-muted small text-uppercase">Pago</div>
                            <div class="fs-4 fw-bold text-success">
                                R$ {{ number_format($resumo['total_pago'], 2, ',', '.') }}
                            </div>
                        </div>
                        <i class="bi bi-check-circle fs-2 text-success"></i>
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
                            <div class="fs-4 fw-bold">
                                R$ {{ number_format($resumo['total_em_aberto'], 2, ',', '.') }}
                            </div>
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
                            <div class="text-muted small text-uppercase">Vencido</div>
                            <div class="fs-4 fw-bold text-danger">
                                R$ {{ number_format($resumo['total_vencido'], 2, ',', '.') }}
                            </div>
                        </div>
                        <i class="bi bi-exclamation-circle fs-2 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-filtros-container action="{{ route('contas-pagar.index') }}">
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <label for="descricao" class="form-label">Descrição</label>
                <input
                    type="text"
                    name="descricao"
                    id="descricao"
                    class="form-control"
                    value="{{ request('descricao') }}"
                    placeholder="Buscar por descrição..."
                >
            </div>

            <div class="col-12 col-md-4">
                <label for="fornecedor_id" class="form-label">Fornecedor</label>
                <select name="fornecedor_id" id="fornecedor_id" class="form-select">
                    <option value="">Todos os fornecedores</option>
                    @foreach($fornecedores as $fornecedor)
                        <option
                            value="{{ $fornecedor->id }}"
                            @selected(request('fornecedor_id') == $fornecedor->id)
                        >
                            {{ $fornecedor->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-4">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">Todas em aberto</option>
                    <option value="aberta" @selected(request('status') === 'aberta')>Aberta</option>
                    <option value="parcialmente_paga" @selected(request('status') === 'parcialmente_paga')>Parcialmente paga</option>
                    <option value="paga" @selected(request('status') === 'paga')>Paga</option>
                    <option value="vencida" @selected(request('status') === 'vencida')>Vencida</option>
                    <option value="cancelada" @selected(request('status') === 'cancelada')>Cancelada</option>
                </select>
            </div>

            <div class="col-12 col-md-6">
                <label for="data_emissao_inicio" class="form-label">Emissão inicial</label>
                <input
                    type="date"
                    name="data_emissao_inicio"
                    id="data_emissao_inicio"
                    class="form-control"
                    value="{{ request('data_emissao_inicio') }}"
                >
            </div>

            <div class="col-12 col-md-6">
                <label for="data_emissao_fim" class="form-label">Emissão final</label>
                <input
                    type="date"
                    name="data_emissao_fim"
                    id="data_emissao_fim"
                    class="form-control"
                    value="{{ request('data_emissao_fim') }}"
                >
            </div>

            <div class="col-12 col-md-6">
                <label for="data_vencimento_inicio" class="form-label">Vencimento inicial</label>
                <input
                    type="date"
                    name="data_vencimento_inicio"
                    id="data_vencimento_inicio"
                    class="form-control"
                    value="{{ request('data_vencimento_inicio') }}"
                >
            </div>

            <div class="col-12 col-md-6">
                <label for="data_vencimento_fim" class="form-label">Vencimento final</label>
                <input
                    type="date"
                    name="data_vencimento_fim"
                    id="data_vencimento_fim"
                    class="form-control"
                    value="{{ request('data_vencimento_fim') }}"
                >
            </div>

            <div class="col-12 d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i>
                    Filtrar
                </button>

                <a href="{{ route('contas-pagar.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>
                    Limpar
                </a>
            </div>
        </div>
    </x-filtros-container>

    @if($contas->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-wallet2 fs-1 text-muted"></i>
                <h5 class="mt-3">Nenhuma conta encontrada</h5>

                @if(request()->hasAny([
                    'descricao',
                    'fornecedor_id',
                    'status',
                    'data_emissao_inicio',
                    'data_emissao_fim',
                    'data_vencimento_inicio',
                    'data_vencimento_fim'
                ]))
                    <p class="text-muted mb-3">
                        Nenhuma conta corresponde aos filtros informados.
                    </p>

                    <a href="{{ route('contas-pagar.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg me-1"></i>
                        Limpar filtros
                    </a>
                @else
                    <p class="text-muted mb-3">
                        Ainda não existem contas a pagar cadastradas.
                    </p>

                    <a href="{{ route('contas-pagar.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>
                        Nova Conta
                    </a>
                @endif
            </div>
        </div>
    @else
        @php
            $gruposPorVencimento = $contas->getCollection()->groupBy(
                fn ($conta) => $conta->data_vencimento->format('Y-m-d')
            );
        @endphp

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Agenda de vencimentos</h5>
                <div class="text-muted small">
                    {{ $contas->total() }} {{ $contas->total() === 1 ? 'conta encontrada' : 'contas encontradas' }}
                </div>
            </div>
        </div>

        @foreach($gruposPorVencimento as $data => $contasDoDia)
            @php
                $dataVencimento = $contasDoDia->first()->data_vencimento;
                $totalGrupo = 0;
                $pagoGrupo = 0;
                $saldoGrupo = 0;

                foreach ($contasDoDia as $conta) {
                    $valorPago = (float) ($conta->valor_pago ?? 0);
                    $saldo = max(0, round((float) $conta->valor - $valorPago, 2));

                    $totalGrupo += (float) $conta->valor;
                    $pagoGrupo += $valorPago;
                    $saldoGrupo += $saldo;
                }

                $estaVencido = $dataVencimento->isBefore(today()) && $saldoGrupo > 0;
                $venceHoje = $dataVencimento->isToday();
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
                                    <div class="fw-bold fs-5">
                                        {{ $dataVencimento->format('d/m/Y') }}
                                    </div>

                                    <div class="small text-muted">
                                        {{ $contasDoDia->count() }}
                                        {{ $contasDoDia->count() === 1 ? 'conta' : 'contas' }}

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
                                    <div class="fw-bold">
                                        R$ {{ number_format($totalGrupo, 2, ',', '.') }}
                                    </div>
                                </div>

                                <div class="col-4">
                                    <div class="small text-muted">Pago</div>
                                    <div class="fw-bold text-success">
                                        R$ {{ number_format($pagoGrupo, 2, ',', '.') }}
                                    </div>
                                </div>

                                <div class="col-4">
                                    <div class="small text-muted">Saldo</div>
                                    <div class="fw-bold {{ $saldoGrupo > 0 && $estaVencido ? 'text-danger' : '' }}">
                                        R$ {{ number_format($saldoGrupo, 2, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">ID</th>
                                <th>Descrição</th>
                                <th>Fornecedor</th>
                                <th>Valor</th>
                                <th>Pago</th>
                                <th>Saldo</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($contasDoDia as $conta)
                                @php
                                    $valorPago = (float) ($conta->valor_pago ?? 0);
                                    $saldo = max(0, round((float) $conta->valor - $valorPago, 2));

                                    $estaCancelada = $conta->status === 'cancelada';
                                    $estaPaga = $saldo <= 0;
                                    $estaVencida = !$estaCancelada
                                        && !$estaPaga
                                        && $conta->data_vencimento->isBefore(today());
                                    $estaParcial = !$estaCancelada
                                        && !$estaPaga
                                        && $valorPago > 0;
                                @endphp

                                <tr>
                                    <td class="ps-3">
                                        #{{ $conta->id }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold">
                                            {{ $conta->descricao }}
                                        </div>

                                        @if($conta->categoriaFinanceira)
                                            <div class="small text-muted">
                                                {{ $conta->categoriaFinanceira->nome }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        @if($conta->fornecedor)
                                            {{ $conta->fornecedor->nome }}
                                        @else
                                            <span class="text-muted">Não informado</span>
                                        @endif
                                    </td>

                                    <td class="fw-semibold">
                                        R$ {{ number_format($conta->valor, 2, ',', '.') }}
                                    </td>

                                    <td class="text-success">
                                        R$ {{ number_format($valorPago, 2, ',', '.') }}
                                    </td>

                                    <td class="fw-semibold {{ $estaVencida ? 'text-danger' : '' }}">
                                        R$ {{ number_format($saldo, 2, ',', '.') }}
                                    </td>

                                    <td>
                                        @if($estaCancelada)
                                            <span class="badge bg-secondary">
                                                Cancelada
                                            </span>
                                        @elseif($estaPaga)
                                            <span class="badge bg-success">
                                                Paga
                                            </span>
                                        @elseif($estaVencida)
                                            <span class="badge bg-danger">
                                                Vencida
                                            </span>
                                        @elseif($estaParcial)
                                            <span class="badge bg-warning text-dark">
                                                Parcialmente paga
                                            </span>
                                        @else
                                            <span class="badge bg-primary">
                                                Aberta
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-end pe-3">
                                        <div class="btn-group" role="group">
                                            <a
                                                href="{{ route('contas-pagar.show', $conta) }}"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Visualizar"
                                            >
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            @if(!$estaCancelada)
                                                <a
                                                    href="{{ route('contas-pagar.edit', $conta) }}"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    title="Editar"
                                                >
                                                    <i class="bi bi-pencil"></i>
                                                </a>

                                                @if($saldo > 0)
                                                    <a
                                                        href="{{ route('contas-pagar.show', $conta) }}"
                                                        class="btn btn-sm btn-outline-success"
                                                        title="Registrar pagamento"
                                                    >
                                                        <i class="bi bi-cash-coin"></i>
                                                    </a>
                                                @endif
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

        <div class="mt-4">
            {{ $contas->links() }}
        </div>
    @endif
</div>
@endsection
