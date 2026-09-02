@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="LISTAR ORDENS DE SERVIÇO"
        icon="bi-clipboard2-check"
        create-route="ordemservicos.create"
        create-text="Nova OS"
        create-icon="bi-plus-lg"
    />

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <x-filtros-container
        action="{{ route('ordemservicos.index') }}"
        id="filtros-os"
        :collapsible="true"
        :expanded="request()->hasAny(['id', 'setor', 'descricao', 'data_inicio', 'data_fim'])"
    >
        <x-slot:primary>
            <div class="row g-3 align-items-end">

                <div class="col-12 col-md-4">
                    <label for="cliente" class="form-label">Cliente</label>
                    <input
                        type="text"
                        name="cliente"
                        id="cliente"
                        class="filtros-container__input"
                        value="{{ request('cliente') }}"
                        placeholder="Nome do cliente"
                    >
                </div>

                <div class="col-12 col-md-3">
                    <label for="placa" class="form-label">Placa do veículo</label>
                    <input
                        type="text"
                        name="placa"
                        id="placa"
                        class="filtros-container__input"
                        value="{{ request('placa') }}"
                        placeholder="Placa do veículo"
                    >
                </div>

                <div class="col-12 col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="filtros-container__select">
                        <option value="">Todos os status</option>
                        <option value="aberta" @selected(request('status') === 'aberta')>Aberta</option>
                        <option value="em_andamento" @selected(request('status') === 'em_andamento')>Em andamento</option>
                        <option value="aguardando_aprovacao" @selected(request('status') === 'aguardando_aprovacao')>Aguardando aprovação</option>
                        <option value="finalizada" @selected(request('status') === 'finalizada')>Finalizada</option>
                        <option value="cancelada" @selected(request('status') === 'cancelada')>Cancelada</option>
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <div class="filtros-container__actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrar
                        </button>

                        <a
                            href="{{ route('ordemservicos.index') }}"
                            class="btn btn-secondary"
                            title="Limpar filtros"
                        >
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </div>

            </div>
        </x-slot:primary>

        <x-slot:advanced>
            <div class="row g-3">

                <div class="col-12 col-md-6 col-lg-3">
                    <label for="id" class="form-label">ID</label>
                    <input
                        type="number"
                        name="id"
                        id="id"
                        class="filtros-container__input"
                        value="{{ request('id') }}"
                        placeholder="Número da OS"
                    >
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <label for="setor" class="form-label">Setor</label>
                    <select name="setor" id="setor" class="filtros-container__select">
                        <option value="">Todos os setores</option>

                        @foreach ($setorservicos as $setor)
                            <option
                                value="{{ $setor->id }}"
                                @selected((string) request('setor') === (string) $setor->id)
                            >
                                {{ $setor->setor }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <label for="descricao" class="form-label">Descrição</label>
                    <input
                        type="text"
                        name="descricao"
                        id="descricao"
                        class="filtros-container__input"
                        value="{{ request('descricao') }}"
                        placeholder="Descrição da OS"
                    >
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <label for="data_inicio" class="form-label">Data inicial</label>
                    <input
                        type="date"
                        name="data_inicio"
                        id="data_inicio"
                        class="filtros-container__input"
                        value="{{ request('data_inicio') }}"
                    >
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <label for="data_fim" class="form-label">Data final</label>
                    <input
                        type="date"
                        name="data_fim"
                        id="data_fim"
                        class="filtros-container__input"
                        value="{{ request('data_fim') }}"
                    >
                </div>

            </div>
        </x-slot:advanced>
    </x-filtros-container>

    @if ($ordemservicos->isEmpty())
        <div class="alert alert-{{ request()->hasAny(['id', 'status', 'cliente', 'placa', 'setor', 'descricao', 'data_inicio', 'data_fim']) ? 'warning' : 'info' }}">
            <i class="bi {{ request()->hasAny(['id', 'status', 'cliente', 'placa', 'setor', 'descricao', 'data_inicio', 'data_fim']) ? 'bi-exclamation-triangle' : 'bi-info-circle' }}"></i>

            {{ request()->hasAny(['id', 'status', 'cliente', 'placa', 'setor', 'descricao', 'data_inicio', 'data_fim'])
                ? 'Nenhuma ordem de serviço encontrada com os filtros informados.'
                : 'Nenhuma ordem de serviço cadastrada.' }}
        </div>
    @endif

    @if ($ordemservicos->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>STATUS</th>
                        <th>DATA ABERTURA</th>
                        <th>CLIENTE</th>
                        <th>VEÍCULO</th>
                        <th>PLACA</th>
                        <th>DESCRIÇÃO</th>
                        <th>SETOR</th>
                        <th>EXCLUIR</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($ordemservicos as $ordemservico)
                        <tr>
                            <td>{{ $ordemservico->id }}</td>

                            <td>
                                <livewire:status-ordem-servico-selector
                                    :ordem-servico="$ordemservico"
                                    :key="'status-os-' . $ordemservico->id"
                                />
                            </td>

                            <td>
                                {{ \Carbon\Carbon::parse($ordemservico->data_abertura)->format('d/m/Y H\:i') }}
                            </td>

                            <td>
                                {{ $ordemservico->veiculosCliente?->cliente?->pessoa?->nome ?? 'N/A' }}
                            </td>

                            <td>
                                @if ($ordemservico->veiculosCliente?->veiculo)
                                    {{ $ordemservico->veiculosCliente->veiculo->nome }}

                                    @if ($ordemservico->veiculosCliente->veiculo->montadora)
                                        <br>
                                        <small class="text-muted">
                                            {{ $ordemservico->veiculosCliente->veiculo->montadora->nome }}
                                        </small>
                                    @endif
                                @else
                                    N/A
                                @endif
                            </td>

                            <td>
                                {{ $ordemservico->veiculosCliente?->placa ?? 'N/A' }}
                            </td>

                            <td>
                                {{ $ordemservico->descricao ?? 'N/A' }}
                            </td>

                            <td>
                                {{ $ordemservico->setorServico?->setor ?? 'N/A' }}
                            </td>

                            <td>
                                <form
                                    action="{{ route('ordemservicos.destroy', $ordemservico->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Tem certeza que deseja excluir esta ordem de serviço?');"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn btn-danger btn-sm"
                                        title="Excluir OS"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($ordemservicos->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $ordemservicos->links() }}
            </div>
        @endif
    @endif

</div>

@endsection
