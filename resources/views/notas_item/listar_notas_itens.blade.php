@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="LISTAR NOTAS"
        icon="bi-receipt"
    />

    <x-filtros-container
        action="{{ route('notas.index') }}"
        id="filtros-notas"
        :collapsible="false"
    >
        <div class="row g-3 align-items-end">

            <div class="col-12 col-md-5">
                <label for="cliente" class="form-label">Cliente</label>
                <input
                    type="text"
                    name="cliente"
                    id="cliente"
                    class="filtros-container__input"
                    placeholder="Nome do cliente"
                    value="{{ request('cliente') }}"
                >
            </div>

            <div class="col-12 col-md-5">
                <label for="status" class="form-label">Status</label>
                <select
                    name="status"
                    id="status"
                    class="filtros-container__select"
                >
                    <option value="">Status (Ativos por padrão)</option>
                    <option value="Aberto" @selected(request('status') === 'Aberto')>Aberto</option>
                    <option value="Andamento" @selected(request('status') === 'Andamento')>Em Andamento</option>
                    <option value="Concluido" @selected(request('status') === 'Concluido')>Concluído</option>
                    <option value="Cancelado" @selected(request('status') === 'Cancelado')>Cancelado</option>
                </select>
            </div>

            <div class="col-12 col-md-2">
                <div class="filtros-container__actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                        Filtrar
                    </button>

                    <a
                        href="{{ route('notas.index') }}"
                        class="btn btn-secondary"
                        title="Limpar filtros"
                    >
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </div>

        </div>
    </x-filtros-container>

    @if($notas->isEmpty())
        <div class="alert alert-{{ request()->hasAny(['cliente', 'status']) ? 'warning' : 'info' }}">
            <i class="bi {{ request()->hasAny(['cliente', 'status']) ? 'bi-exclamation-triangle' : 'bi-info-circle' }}"></i>

            {{ request()->hasAny(['cliente', 'status'])
                ? 'Nenhuma nota encontrada com os filtros informados.'
                : 'Nenhuma nota cadastrada.' }}
        </div>
    @endif

    @if($notas->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">STATUS</th>
                        <th scope="col">CLIENTE</th>
                        <th scope="col">VEÍCULO</th>
                        <th scope="col">PLACA</th>
                        <th scope="col">IMPRIMIR</th>
                        <th scope="col">VER</th>
                        <th scope="col">EXCLUIR</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($notas as $nota)
                        <tr>
                            <td>{{ $nota->id }}</td>

                            <td>
                                @livewire('status-nota-selector', ['nota' => $nota], key('status-nota-' . $nota->id))
                            </td>

                            <td>
                                {{ $nota->cliente?->pessoa?->nome ?? 'Cliente Geral / Balcão' }}
                            </td>

                            <td>
                                {{ $nota->veiculoscliente?->veiculo?->nome ?? 'N/A' }}
                                ({{ $nota->veiculoscliente?->veiculo?->montadora?->nome ?? 'N/A' }})
                            </td>

                            <td>
                                {{ $nota->veiculoscliente?->placa ?? 'N/A' }}
                            </td>

                            <td>
                                <a
                                    href="{{ route('notas.pdf', $nota->id) }}"
                                    target="_blank"
                                    class="btn btn-danger"
                                    title="Imprimir nota"
                                >
                                    <i class="bi bi-printer"></i>
                                    PDF
                                </a>
                            </td>

                            <td>
                                <a
                                    href="{{ route('notas.show', $nota->id) }}"
                                    class="btn btn-success"
                                    title="Visualizar nota"
                                >
                                    <i class="bi bi-list-task"></i>
                                </a>
                            </td>

                            <td>
                                <form
                                    action="{{ route('notas.destroy', $nota->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Deseja realmente excluir esta nota?');"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn btn-danger"
                                        title="Excluir nota"
                                    >
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(method_exists($notas, 'hasPages') && $notas->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $notas->links() }}
            </div>
        @endif
    @endif

</div>

@endsection
