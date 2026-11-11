@extends('layouts.layout')

@section('content')
<main class="container" style="padding-top: 125px; padding-bottom: 65px; min-height: 75vh;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Orçamentos de peças</h1>
            <p class="text-secondary mb-0">Solicitações recebidas pelo formulário público da SOS Mecânica.</p>
        </div>
        <a href="{{ route('marketing.pecas.orcamento') }}" class="btn btn-outline-secondary" target="_blank" rel="noopener noreferrer">
            <i class="bi bi-box-arrow-up-right"></i> Ver formulário público
        </a>
    </div>

    @if(session('marketing_status_sucesso'))
        <div class="alert alert-success" role="status">{{ session('marketing_status_sucesso') }}</div>
    @endif
    @error('status')
        <div class="alert alert-danger" role="alert">{{ $message }}</div>
    @enderror

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="small text-secondary">Total de solicitações</div>
                <strong class="fs-3">{{ $totalLeads }}</strong>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="small text-secondary">Novas solicitações</div>
                <strong class="fs-3">{{ $leadsNovos }}</strong>
            </div></div>
        </div>
    </div>

    <section class="card mb-4" aria-label="Filtros de orçamentos">
        <div class="card-body">
            <form action="{{ route('marketing.leads.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label" for="mkt-busca">Buscar por protocolo, peça ou nome</label>
                    <input type="search" class="form-control" id="mkt-busca" name="busca" value="{{ $busca }}" maxlength="80" placeholder="Ex.: filtro de óleo">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="mkt-status">Situação</label>
                    <select class="form-select" id="mkt-status" name="status">
                        <option value="">Todas</option>
                        @foreach($opcoesStatus as $chave => $descricao)
                            <option value="{{ $chave }}" @selected($statusSelecionado === $chave)>{{ $descricao }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-dark"><i class="bi bi-search"></i> Filtrar</button>
                    <a href="{{ route('marketing.leads.index') }}" class="btn btn-outline-secondary">Limpar</a>
                </div>
            </form>
        </div>
    </section>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <caption class="visually-hidden">Lista de solicitações de orçamento recebidas</caption>
                <thead class="table-light">
                    <tr>
                        <th scope="col">Protocolo</th>
                        <th scope="col">Solicitante</th>
                        <th scope="col">Peça</th>
                        <th scope="col">Veículo</th>
                        <th scope="col">Origem</th>
                        <th scope="col">Status</th>
                        <th scope="col">Recebido em</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $lead)
                        <tr>
                            <td><a class="fw-semibold" href="{{ route('marketing.leads.show', $lead) }}">{{ $lead->protocolo }}</a></td>
                            <td>
                                <div class="fw-semibold">{{ $lead->nome }}</div>
                                <div class="small text-secondary">{{ $lead->whatsapp }}</div>
                            </td>
                            <td>{{ $lead->peca }}</td>
                            <td>{{ $lead->veiculo ?: 'Não informado' }}</td>
                            <td>
                                <div>{{ $lead->utm_source ?: 'Direta / não informada' }}</div>
                                @if($lead->utm_campaign)
                                    <div class="small text-secondary">{{ $lead->utm_campaign }}</div>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('marketing.leads.status.update', $lead) }}" method="POST" class="d-flex flex-wrap align-items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <label class="visually-hidden" for="mkt-lead-status-{{ $lead->id }}">Status do orçamento {{ $lead->protocolo }}</label>
                                    <select id="mkt-lead-status-{{ $lead->id }}" name="status" class="form-select form-select-sm" style="min-width: 155px;" required>
                                        @foreach($opcoesStatus as $chave => $descricao)
                                            <option value="{{ $chave }}" @selected($lead->status === $chave)>{{ $descricao }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Salvar</button>
                                </form>
                            </td>
                            <td class="text-nowrap">{{ $lead->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-5">Nenhuma solicitação encontrada com esses filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($leads->hasPages())
            <div class="card-footer">{{ $leads->links() }}</div>
        @endif
    </div>
    <p class="small text-secondary mt-3">Abra o protocolo para conferir os itens e, quando apropriado, gerar uma nota aberta vinculada.</p>
</main>
@endsection
