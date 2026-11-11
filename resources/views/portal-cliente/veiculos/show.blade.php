@extends('layouts.layout')

@section('content')
<main class="container mt-5 py-5" style="min-height: 70vh;">

    <a
        href="{{ route('cliente.veiculos.index') }}"
        class="btn btn-outline-secondary mb-4"
    >
        <i class="bi bi-arrow-left me-1"></i>
        Meus veículos
    </a>

    <article class="card border-0 shadow-sm">
        <div class="card-body p-4">

            <span class="small text-secondary text-uppercase">
                Dados do veículo
            </span>

            <h1 class="h3 fw-bold my-3">
                {{ $veiculoCliente->veiculo?->nome ?? 'Veículo' }}
            </h1>

            <dl class="row mb-4">
                <dt class="col-sm-4">Montadora</dt>
                <dd class="col-sm-8">
                    {{ $veiculoCliente->veiculo?->montadora?->nome ?? 'Não informada' }}
                </dd>

                <dt class="col-sm-4">Placa</dt>
                <dd class="col-sm-8 fw-semibold">
                    {{ $veiculoCliente->placa }}
                </dd>

                <dt class="col-sm-4">Ano</dt>
                <dd class="col-sm-8">
                    {{ $veiculoCliente->ano ?? 'Não informado' }}
                </dd>

                <dt class="col-sm-4">Cor</dt>
                <dd class="col-sm-8">
                    {{ $veiculoCliente->cor ?: 'Não informada' }}
                </dd>
            </dl>

            @if ($veiculoCliente->clientes_count === 1)
                <a
                    href="{{ route('veiculosclientes.edit', $veiculoCliente->id) }}"
                    class="btn btn-warning"
                >
                    <i class="bi bi-pencil me-1"></i>
                    Editar dados
                </a>
            @else
                <div class="alert alert-light border mb-0">
                    Este veículo possui mais de um responsável vinculado.
                    Solicite alterações diretamente à oficina.
                </div>
            @endif

        </div>
    </article>

</main>
@endsection
