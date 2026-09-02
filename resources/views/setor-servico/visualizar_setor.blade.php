@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="VISUALIZAR SETOR DE SERVIÇO"
        icon="bi-eye"
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

    <div class="card shadow-sm">

        <div class="card-body">

            <div class="row g-4">

                <div class="col-12 col-md-4">
                    <div class="text-muted small">
                        <i class="bi bi-hash"></i>
                        ID
                    </div>

                    <div class="fs-5 fw-semibold">
                        {{ $setorServico->id }}
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="text-muted small">
                        <i class="bi bi-diagram-3"></i>
                        Setor
                    </div>

                    <div class="fs-5 fw-semibold">
                        {{ $setorServico->setor }}
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="text-muted small">
                        <i class="bi bi-bar-chart-steps"></i>
                        Nível
                    </div>

                    <div class="fs-5">
                        <span class="badge bg-secondary">
                            {{ $setorServico->nivel }}
                        </span>
                    </div>
                </div>

                <div class="col-12">
                    <hr>
                </div>

                <div class="col-12 col-md-6">
                    <div class="text-muted small">
                        <i class="bi bi-clipboard2-check"></i>
                        Ordens de Serviço Vinculadas
                    </div>

                    <div class="fs-4 fw-semibold">
                        {{ $setorServico->ordem_servicos_count }}

                        @if ($setorServico->ordem_servicos_count == 1)
                            <span class="fs-6 text-muted">
                                ordem de serviço
                            </span>
                        @else
                            <span class="fs-6 text-muted">
                                ordens de serviço
                            </span>
                        @endif
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="text-muted small">
                        <i class="bi bi-calendar-plus"></i>
                        Cadastrado em
                    </div>

                    <div class="fs-5">
                        {{ $setorServico->created_at?->format('d/m/Y H:i') ?? 'N/A' }}
                    </div>
                </div>

                @if ($setorServico->updated_at && $setorServico->updated_at != $setorServico->created_at)
                    <div class="col-12 col-md-6">
                        <div class="text-muted small">
                            <i class="bi bi-calendar-check"></i>
                            Última atualização
                        </div>

                        <div class="fs-5">
                            {{ $setorServico->updated_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                @endif

            </div>

        </div>

        <div class="card-footer bg-transparent">

            <div class="d-flex justify-content-between flex-wrap gap-2">

                <a
                    href="{{ route('setor-servicos.index') }}"
                    class="btn btn-secondary"
                >
                    <i class="bi bi-arrow-left"></i>
                    Voltar
                </a>

                <div class="d-flex gap-2">

                    <a
                        href="{{ route('setor-servicos.edit', $setorServico) }}"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-pencil"></i>
                        Editar
                    </a>

                    @if ($setorServico->ordem_servicos_count > 0)

                        <button
                            type="button"
                            class="btn btn-secondary"
                            disabled
                            title="Este setor possui ordens de serviço vinculadas"
                        >
                            <i class="bi bi-trash"></i>
                            Excluir
                        </button>

                    @else

                        <form
                            action="{{ route('setor-servicos.destroy', $setorServico) }}"
                            method="POST"
                            onsubmit="return confirm('Tem certeza que deseja excluir este setor de serviço?');"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="btn btn-danger"
                            >
                                <i class="bi bi-trash"></i>
                                Excluir
                            </button>
                        </form>

                    @endif

                </div>

            </div>

            @if ($setorServico->ordem_servicos_count > 0)
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="bi bi-exclamation-triangle"></i>
                    Este setor possui
                    <strong>{{ $setorServico->ordem_servicos_count }}</strong>
                    ordem(ns) de serviço vinculada(s) e não pode ser excluído.
                </div>
            @endif

        </div>

    </div>

</div>

@endsection
