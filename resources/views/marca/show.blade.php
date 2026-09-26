@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="mb-1">Detalhes da Marca</h1>
            <p class="text-muted mb-0">
                Visualize as informações cadastradas para esta marca.
            </p>
        </div>

        <div class="d-flex gap-2">
            <a
                href="{{ route('marcas.edit', $marca) }}"
                class="btn btn-warning"
            >
                <i class="bi bi-pencil me-1"></i>
                Editar
            </a>

            <a
                href="{{ route('marcas.index') }}"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-1"></i>
                Voltar
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="text-muted small mb-1">
                        Nome
                    </div>

                    <div class="fw-semibold">
                        {{ $marca->nome }}
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted small mb-1">
                        Status
                    </div>

                    @if($marca->ativo)
                        <span class="badge text-bg-success">
                            Ativa
                        </span>
                    @else
                        <span class="badge text-bg-secondary">
                            Inativa
                        </span>
                    @endif
                </div>

                <div class="col-md-4">
                    <div class="text-muted small mb-1">
                        Produtos vinculados
                    </div>

                    <div class="fw-semibold">
                        {{ $marca->produtos_count }}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted small mb-2">
                        Logo
                    </div>

                    @if($marca->logo_path)
                        <img
                            src="{{ asset('storage/' . $marca->logo_path) }}"
                            alt="Logo {{ $marca->nome }}"
                            class="img-thumbnail"
                            style="max-width: 220px; max-height: 140px;"
                        >
                    @elseif($marca->logo_url)
                        <img
                            src="{{ $marca->logo_url }}"
                            alt="Logo {{ $marca->nome }}"
                            class="img-thumbnail"
                            style="max-width: 220px; max-height: 140px;"
                        >
                    @else
                        <div class="text-muted">
                            <i class="bi bi-image me-1"></i>
                            Nenhuma logo cadastrada.
                        </div>
                    @endif
                </div>

                <div class="col-md-6">
                    <div class="text-muted small mb-1">
                        URL da Logo
                    </div>

                    @if($marca->logo_url)
                        <a
                            href="{{ $marca->logo_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {{ $marca->logo_url }}
                        </a>
                    @else
                        <span class="text-muted">
                            Não informada
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
