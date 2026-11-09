@extends('layouts.layout')

@section('content')
<main class="container mt-5 py-5" style="min-height: 70vh;">

    <a href="{{ route('cliente.notas.index') }}"
       class="btn btn-outline-secondary mb-4">
        <i class="bi bi-arrow-left me-1"></i> Minhas notas
    </a>

    <article class="card border-0 shadow-sm">
        <div class="card-body p-4">

            <span class="text-secondary small text-uppercase">
                Consulta do cliente
            </span>

            <h1 class="h3 fw-bold my-3">
                Nota #{{ str_pad((string) $nota->id, 6, '0', STR_PAD_LEFT) }}
            </h1>

            <dl class="row mb-0">
                <dt class="col-sm-4">Data</dt>
                <dd class="col-sm-8">
                    {{ $nota->created_at?->format('d/m/Y') ?? 'Não informada' }}
                </dd>

                <dt class="col-sm-4">Situação</dt>
                <dd class="col-sm-8">
                    {{ $nota->status ?: 'Não informada' }}
                </dd>

                <dt class="col-sm-4">Tipo</dt>
                <dd class="col-sm-8">
                    {{ $nota->tipo ?: 'Não informado' }}
                </dd>

                <dt class="col-sm-4">Veículo</dt>
                <dd class="col-sm-8">
                    @if ($nota->veiculosCliente)
                        {{ $nota->veiculosCliente->veiculo?->nome ?? 'Veículo' }}
                        — {{ $nota->veiculosCliente->placa ?? 'Sem placa' }}
                    @else
                        Não informado
                    @endif
                </dd>

                <dt class="col-sm-4">Valor total</dt>
                <dd class="col-sm-8 fw-bold">
                    R$ {{ number_format((float) $nota->total, 2, ',', '.') }}
                </dd>
            </dl>

            <div class="alert alert-light border small mt-3 mb-0">
                Esta página apresenta um resumo para consulta.
                Para informações adicionais, entre em contato com a oficina.
            </div>

        </div>
    </article>
</main>
@endsection
