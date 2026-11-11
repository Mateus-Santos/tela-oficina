@extends('layouts.layout')

@section('content')
<main class="container" style="padding-top: 125px; padding-bottom: 65px; min-height: 75vh;">
    <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
        <div>
            <h1 class="h2 mb-1">Liberação individual do portal</h1>
            <p class="text-secondary mb-0">Configure somente os novos módulos de Marketing para cada cliente.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('marketing.leads.index') }}">Voltar aos orçamentos</a>
    </div>

    <div class="alert alert-info" role="note">
        As duas permissões exigem também a respectiva liberação global do Marketing.
        Nenhuma permissão desta tela libera notas, veículos, endereços, estoque ou operações financeiras.
    </div>

    @if(session('marketing_acessos_sucesso'))
        <div class="alert alert-success" role="status">{{ session('marketing_acessos_sucesso') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger" role="alert">Não foi possível salvar. Verifique os campos de permissão.</div>
    @endif

    <form method="GET" action="{{ route('marketing.clientes-acessos.index') }}" class="mb-4">
        <label class="form-label" for="busca-cliente">Buscar pelo nome ou ID do cliente</label>
        <div class="input-group">
            <input class="form-control" id="busca-cliente" name="busca" type="search" maxlength="80" value="{{ $busca }}">
            <button class="btn btn-dark" type="submit">Buscar</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Cliente</th><th>Painel novo</th><th>Solicitações</th><th>Ação</th></tr></thead>
                <tbody>
                    @forelse($clientes as $cliente)
                        <tr>
                            <td><strong>{{ $cliente->pessoa?->nome ?? 'Sem nome' }}</strong><div class="small text-secondary">ID #{{ $cliente->id }}</div></td>
                            <td colspan="3">
                                <form method="POST" action="{{ route('marketing.clientes-acessos.update', $cliente) }}" class="row g-2 align-items-center">
                                    @csrf
                                    @method('PATCH')
                                    <div class="col-12 col-md-4">
                                        <label class="form-label small" for="portal-{{ $cliente->id }}">Painel de {{ $cliente->pessoa?->nome ?? $cliente->id }}</label>
                                        <select class="form-select form-select-sm" id="portal-{{ $cliente->id }}" name="portal_inicio" required>
                                            <option value="0" @selected(!$cliente->marketingAcesso?->portal_inicio)>Bloqueado</option>
                                            <option value="1" @selected($cliente->marketingAcesso?->portal_inicio)>Liberado</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label small" for="solicitacoes-{{ $cliente->id }}">Solicitações de {{ $cliente->pessoa?->nome ?? $cliente->id }}</label>
                                        <select class="form-select form-select-sm" id="solicitacoes-{{ $cliente->id }}" name="solicitacoes" required>
                                            <option value="0" @selected(!$cliente->marketingAcesso?->solicitacoes)>Bloqueado</option>
                                            <option value="1" @selected($cliente->marketingAcesso?->solicitacoes)>Liberado</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <button class="btn btn-sm btn-outline-primary" type="submit">Salvar permissões</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-secondary py-4 text-center">Nenhum cliente encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($clientes->hasPages())
            <div class="card-footer">{{ $clientes->links() }}</div>
        @endif
    </div>
</main>
@endsection
