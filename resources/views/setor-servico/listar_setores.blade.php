@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="LISTAR SETORES DE SERVIÇO"
        icon="bi-diagram-3"
        create-route="setor-servicos.create"
        create-text="Novo Setor"
        create-icon="bi-plus-lg"
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

    <x-filtros-container
        action="{{ route('setor-servicos.index') }}"
        id="filtros-setores"
        :collapsible="false"
    >
        <div class="row g-3 align-items-end">

            <div class="col-12 col-md-5">
                <label for="setor" class="form-label">
                    <i class="bi bi-diagram-3"></i>
                    Setor
                </label>

                <input
                    type="text"
                    name="setor"
                    id="setor"
                    class="filtros-container__input"
                    value="{{ request('setor') }}"
                    placeholder="Nome do setor"
                >
            </div>

            <div class="col-12 col-md-5">
                <label for="nivel" class="form-label">
                    <i class="bi bi-bar-chart-steps"></i>
                    Nível
                </label>

                <select
                    name="nivel"
                    id="nivel"
                    class="filtros-container__select"
                >
                    <option value="">Todos os níveis</option>

                    @foreach ($niveis as $nivel)
                        <option
                            value="{{ $nivel }}"
                            @selected(request('nivel') === $nivel)
                        >
                            {{ $nivel }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-2">
                <div class="filtros-container__actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                        title="Filtrar setores"
                    >
                        <i class="bi bi-search"></i>
                        Filtrar
                    </button>

                    <a
                        href="{{ route('setor-servicos.index') }}"
                        class="btn btn-secondary"
                        title="Limpar filtros"
                    >
                        <i class="bi bi-x-lg"></i>
                    </a>

                </div>
            </div>

        </div>
    </x-filtros-container>

    @php
        $possuiFiltros = request()->filled('setor') || request()->filled('nivel');
    @endphp

    @if ($setores->isEmpty())
        <div class="alert alert-{{ $possuiFiltros ? 'warning' : 'info' }}">
            <i class="bi {{ $possuiFiltros ? 'bi-exclamation-triangle' : 'bi-info-circle' }}"></i>

            {{ $possuiFiltros
                ? 'Nenhum setor de serviço encontrado com os filtros informados.'
                : 'Nenhum setor de serviço cadastrado.' }}
        </div>
    @endif

    @if ($setores->isNotEmpty())

        <div class="table-responsive">

            <table class="table table-striped table-hover align-middle">

                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">SETOR</th>
                        <th scope="col">NÍVEL</th>
                        <th scope="col">ORDENS DE SERVIÇO</th>
                        <th scope="col">AÇÕES</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach ($setores as $setor)

                        @php
                            $possuiOrdens = $setor->ordem_servicos_count ?? $setor->ordemServicos()->count();
                        @endphp

                        <tr>

                            <td>
                                {{ $setor->id }}
                            </td>

                            <td>
                                <strong>
                                    {{ $setor->setor }}
                                </strong>
                            </td>

                            <td>
                                <span class="badge bg-secondary">
                                    <i class="bi bi-bar-chart-steps"></i>
                                    {{ $setor->nivel }}
                                </span>
                            </td>

                            <td>
                                @if ($possuiOrdens > 0)
                                    <span class="badge bg-primary">
                                        <i class="bi bi-clipboard2-check"></i>
                                        {{ $possuiOrdens }}
                                    </span>
                                @else
                                    <span class="text-muted">
                                        <i class="bi bi-dash-circle"></i>
                                        Nenhuma
                                    </span>
                                @endif
                            </td>

                            <td>

                                <div class="d-flex gap-1">

                                    <a
                                        href="{{ route('setor-servicos.show', $setor) }}"
                                        class="btn btn-success btn-sm"
                                        title="Visualizar setor"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <a
                                        href="{{ route('setor-servicos.edit', $setor) }}"
                                        class="btn btn-primary btn-sm"
                                        title="Editar setor"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    @if ($possuiOrdens == 0)

                                        <form
                                            action="{{ route('setor-servicos.destroy', $setor) }}"
                                            method="POST"
                                            onsubmit="return confirm('Tem certeza que deseja excluir este setor de serviço?');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="btn btn-danger btn-sm"
                                                title="Excluir setor"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>

                                    @else

                                        <button
                                            type="button"
                                            class="btn btn-secondary btn-sm"
                                            disabled
                                            title="Não é possível excluir: existem ordens de serviço vinculadas"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        @if ($setores->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $setores->links() }}
            </div>
        @endif

    @endif

</div>

@endsection
