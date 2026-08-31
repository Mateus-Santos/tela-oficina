@extends('layouts.layout')

@section('content')

<div class="container cadastro">


<div class="d-flex justify-content-between align-items-center mb-3">

    <h1>LISTAR ORDENS DE SERVIÇO</h1>

    <a
        href="{{ route('ordemservicos.create') }}"
        class="btn btn-primary"
    >
        <i class="bi bi-plus-lg"></i> Nova OS
    </a>

</div>

{{-- Mensagens --}}

@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

{{-- Filtros --}}

<div class="filtros-container">

<form
    method="GET"
    action="{{ route('ordemservicos.index') }}"
    class="filtros-container__form"
>

    {{-- Primeira linha --}}

    <div class="row g-3 mb-3">

        {{-- ID --}}

        <div class="col-md-2">

            <label for="id" class="form-label">
                Nº da OS
            </label>

            <input
                type="number"
                id="id"
                name="id"
                class="form-control"
                placeholder="Ex.: 125"
                value="{{ request('id') }}"
                min="1"
            >

        </div>

        {{-- Cliente --}}

        <div class="col-md-3">

            <label for="cliente" class="form-label">
                Cliente
            </label>

            <input
                type="text"
                id="cliente"
                name="cliente"
                class="form-control"
                placeholder="Nome do cliente..."
                value="{{ request('cliente') }}"
            >

        </div>

        {{-- Placa --}}

        <div class="col-md-2">

            <label for="placa" class="form-label">
                Placa
            </label>

            <input
                type="text"
                id="placa"
                name="placa"
                class="form-control"
                placeholder="ABC-1234"
                value="{{ request('placa') }}"
                maxlength="8"
            >

        </div>

        {{-- Status --}}

        <div class="col-md-2">

            <label for="status" class="form-label">
                Status
            </label>

            <select
                id="status"
                name="status"
                class="form-select"
            >

                <option value="">
                    Todos
                </option>

                <option
                    value="aberta"
                    @selected(request('status') === 'aberta')
                >
                    Aberta
                </option>

                <option
                    value="em_andamento"
                    @selected(request('status') === 'em_andamento')
                >
                    Em andamento
                </option>

                <option
                    value="finalizada"
                    @selected(request('status') === 'finalizada')
                >
                    Finalizada
                </option>

                <option
                    value="cancelada"
                    @selected(request('status') === 'cancelada')
                >
                    Cancelada
                </option>

            </select>

        </div>

        {{-- Setor --}}

        <div class="col-md-3">

            <label for="setor" class="form-label">
                Setor
            </label>

            <select
                id="setor"
                name="setor"
                class="form-select"
            >

                <option value="">
                    Todos os setores
                </option>

                @foreach ($setorservicos as $setor)

                    <option
                        value="{{ $setor->id }}"
                        @selected(request('setor') == $setor->id)
                    >
                        {{ $setor->setor }}
                    </option>

                @endforeach

            </select>

        </div>

    </div>

    {{-- Segunda linha --}}

    <div class="row g-3 align-items-end">

        {{-- Data inicial --}}

        <div class="col-md-2">

            <label
                for="data_inicio"
                class="form-label"
            >
                Data inicial
            </label>

            <input
                type="date"
                id="data_inicio"
                name="data_inicio"
                class="form-control"
                value="{{ request('data_inicio') }}"
            >

        </div>

        {{-- Data final --}}

        <div class="col-md-2">

            <label
                for="data_fim"
                class="form-label"
            >
                Data final
            </label>

            <input
                type="date"
                id="data_fim"
                name="data_fim"
                class="form-control"
                value="{{ request('data_fim') }}"
            >

        </div>

        {{-- Descrição --}}

        <div class="col-md-4">

            <label
                for="descricao"
                class="form-label"
            >
                Descrição
            </label>

            <input
                type="text"
                id="descricao"
                name="descricao"
                class="form-control"
                placeholder="Buscar na descrição..."
                value="{{ request('descricao') }}"
            >

        </div>

        {{-- Botões --}}

        <div class="col-md-4">

            <div class="d-flex gap-2">

                <button
                    type="submit"
                    class="btn btn-warning"
                >
                    <i class="bi bi-funnel"></i>
                    Filtrar
                </button>

                <a
                    href="{{ route('ordemservicos.index') }}"
                    class="btn btn-secondary"
                >
                    <i class="bi bi-x-lg"></i>
                    Limpar Filtros
                </a>

            </div>

        </div>

    </div>

</form>


</div>


{{-- Tabela --}}

<div class="table-responsive">

    <table class="table table-striped table-hover align-middle">

        <thead>

            <tr>
                <th scope="col">ID</th>
                <th scope="col">STATUS</th>
                <th scope="col">DATA ABERTURA</th>
                <th scope="col">CLIENTE</th>
                <th scope="col">VEÍCULO</th>
                <th scope="col">PLACA</th>
                <th scope="col">DESCRIÇÃO</th>
                <th scope="col">SETOR</th>
                <th scope="col" class="text-center">EXCLUIR</th>
            </tr>

        </thead>

        <tbody>

            @forelse ($ordemservicos as $ordemservico)

                <tr>

                    <th scope="row">
                        {{ $ordemservico->id }}
                    </th>

                    <td>
                        @livewire(
                            'status-ordem-servico-selector',
                            ['ordemServico' => $ordemservico],
                            key('status-' . $ordemservico->id)
                        )
                    </td>

                    <td>
                        {{ $ordemservico->data_abertura
                            ? \Carbon\Carbon::parse($ordemservico->data_abertura)->format('d/m/Y H:i')
                            : 'N/A'
                        }}
                    </td>

                    <td>
                        {{ $ordemservico->veiculosCliente?->cliente?->pessoa?->nome ?? 'N/A' }}
                    </td>

                    <td>
                        @if ($ordemservico->veiculosCliente?->veiculo)

                            {{ $ordemservico->veiculosCliente->veiculo->nome }}

                            @if ($ordemservico->veiculosCliente->veiculo->montadora)
                                ({{ $ordemservico->veiculosCliente->veiculo->montadora->nome }})
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

                    <td class="text-center">

                        <form
                            action="{{ route('ordemservicos.destroy', $ordemservico->id) }}"
                            method="POST"
                            onsubmit="return confirm('Tem certeza que deseja excluir esta Ordem de Serviço?');"
                        >

                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="btn btn-sm btn-danger"
                                title="Excluir"
                            >
                                <i class="bi bi-trash3"></i>
                            </button>

                        </form>

                    </td>

                </tr>

            @empty

                <tr>

                    <td
                        colspan="9"
                        class="text-center text-muted py-4"
                    >

                        @if (
                            request()->hasAny([
                                'id',
                                'status',
                                'cliente',
                                'placa',
                                'setor',
                                'descricao',
                                'data_inicio',
                                'data_fim'
                            ])
                        )

                            Nenhuma Ordem de Serviço encontrada com os filtros informados.

                        @else

                            Nenhuma Ordem de Serviço cadastrada até o momento.

                        @endif

                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>

</div>

{{-- Paginação --}}

@if ($ordemservicos->hasPages())

    <div class="d-flex justify-content-center mt-4">
        {{ $ordemservicos->links() }}
    </div>

@endif


</div>

@endsection
