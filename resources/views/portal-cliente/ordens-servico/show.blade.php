@extends('layouts.layout')

@section('content')
<main class="container mt-5 py-5" style="min-height: 70vh;">

    <a
        href="{{ route('cliente.os.index') }}"
        class="btn btn-outline-secondary mb-4"
    >
        <i class="bi bi-arrow-left me-1"></i>
        Minhas Ordens de Serviço
    </a>

    <article class="card border-0 shadow-sm">
        <div class="card-body p-4">

            <span class="small text-secondary text-uppercase">
                Consulta do cliente
            </span>

            <h1 class="h3 fw-bold my-3">
                OS #{{ str_pad((string) $ordem->id, 6, '0', STR_PAD_LEFT) }}
            </h1>

            <dl class="row mb-0">

                <dt class="col-sm-4">Situação</dt>
                <dd class="col-sm-8">
                    {{ ucfirst($ordem->status ?? 'Não informado') }}
                </dd>

                <dt class="col-sm-4">Etapa atual</dt>
                <dd class="col-sm-8">
                    {{ $ordem->etapa?->nome ?? 'Não informada' }}
                </dd>

                <dt class="col-sm-4">Setor de serviço</dt>
                <dd class="col-sm-8">
                    {{ $ordem->setorServico?->setor ?? 'Não informado' }}
                </dd>

                <dt class="col-sm-4">Veículo</dt>
                <dd class="col-sm-8">
                    @if ($ordem->veiculosCliente)
                        {{ $ordem->veiculosCliente->veiculo?->nome ?? 'Veículo' }}
                        — {{ $ordem->veiculosCliente->placa ?? 'Sem placa' }}
                    @else
                        Não informado
                    @endif
                </dd>

                <dt class="col-sm-4">Data de abertura</dt>
                <dd class="col-sm-8">
                    {{ $ordem->data_abertura?->format('d/m/Y H:i') ?? 'Não informada' }}
                </dd>

                <dt class="col-sm-4">Data de fechamento</dt>
                <dd class="col-sm-8">
                    {{ $ordem->data_fechamento?->format('d/m/Y H:i') ?? 'Ainda não finalizada' }}
                </dd>

                <dt class="col-sm-4">Valor</dt>
                <dd class="col-sm-8 fw-bold">
                    R$ {{ number_format((float) $ordem->valor, 2, ',', '.') }}
                </dd>

            </dl>

            <div class="alert alert-light border small mt-3 mb-0">
                Este é um resumo para acompanhamento.
                Em caso de dúvidas, entre em contato com a oficina.
            </div>

        </div>
    </article>

</main>
@endsection
