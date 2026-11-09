@extends('layouts.layout')

@section('content')
<main class="container mt-5 py-5" style="min-height: 70vh;">

    <header class="mb-4">
        <span class="small text-secondary text-uppercase fw-semibold">
            Área do cliente
        </span>

        <h1 class="h2 fw-bold mt-2">
            Minhas Ordens de Serviço
        </h1>

        <p class="text-secondary">
            Acompanhe a situação dos serviços vinculados ao seu cadastro.
        </p>
    </header>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form
                method="GET"
                action="{{ route('cliente.os.index') }}"
            >
                <div class="row g-3 align-items-end">

                    <div class="col-12 col-md-5">
                        <label for="veiculo" class="form-label fw-semibold">
                            Veículo
                        </label>

                        <select
                            id="veiculo"
                            name="veiculo"
                            class="form-select"
                        >
                            <option value="">Todos os veículos</option>

                            @foreach ($veiculos as $veiculo)
                                <option
                                    value="{{ $veiculo->id }}"
                                    @selected($veiculoSelecionado === $veiculo->id)
                                >
                                    {{ $veiculo->veiculo?->nome ?? 'Veículo' }}
                                    — {{ $veiculo->placa ?? 'Sem placa' }}
                                </option>
                            @endforeach
                        </select>

                        @error('veiculo')
                            <div class="text-danger small mt-1">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="status" class="form-label fw-semibold">
                            Situação
                        </label>

                        <select
                            id="status"
                            name="status"
                            class="form-select"
                        >
                            <option value="">Todas</option>

                            <option value="aberta"
                                @selected($statusSelecionado === 'aberta')>
                                Aberta
                            </option>

                            <option value="finalizada"
                                @selected($statusSelecionado === 'finalizada')>
                                Finalizada
                            </option>

                            <option value="cancelada"
                                @selected($statusSelecionado === 'cancelada')>
                                Cancelada
                            </option>
                        </select>

                        @error('status')
                            <div class="text-danger small mt-1">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button
                            type="submit"
                            class="btn btn-warning flex-grow-1"
                        >
                            Filtrar
                        </button>

                        <a
                            href="{{ route('cliente.os.index') }}"
                            class="btn btn-outline-secondary"
                        >
                            Limpar
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>

    @if ($ordens->isEmpty())

        <div class="alert alert-light border" role="status">
            Nenhuma ordem de serviço encontrada.
        </div>

    @else

        <div class="row g-3 mb-4">

            @foreach ($ordens as $ordem)

                <div class="col-12 col-md-6">
                    <article class="card border-0 shadow-sm h-100">
                        <div class="card-body">

                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <h2 class="h5 fw-bold">
                                    OS #{{ str_pad((string) $ordem->id, 6, '0', STR_PAD_LEFT) }}
                                </h2>

                                <span class="badge text-bg-secondary">
                                    {{ ucfirst($ordem->status ?? 'Não informado') }}
                                </span>
                            </div>

                            <p class="text-secondary small">
                                Abertura:
                                {{ $ordem->data_abertura?->format('d/m/Y') ?? 'Não informada' }}
                            </p>

                            <p class="mb-2">
                                <strong>Veículo:</strong>

                                @if ($ordem->veiculosCliente)
                                    {{ $ordem->veiculosCliente->veiculo?->nome ?? 'Veículo' }}
                                    — {{ $ordem->veiculosCliente->placa ?? 'Sem placa' }}
                                @else
                                    Não informado
                                @endif
                            </p>

                            <p class="mb-2">
                                <strong>Etapa atual:</strong>
                                {{ $ordem->etapa?->nome ?? 'Não informada' }}
                            </p>

                            <p class="mb-3">
                                <strong>Valor:</strong>
                                R$ {{ number_format((float) $ordem->valor, 2, ',', '.') }}
                            </p>

                            <a
                                class="btn btn-outline-primary"
                                href="{{ route('cliente.os.show', $ordem->id) }}"
                            >
                                Consultar OS
                            </a>

                        </div>
                    </article>
                </div>

            @endforeach

        </div>

        <nav aria-label="Paginação das ordens de serviço">
            {{ $ordens->links() }}
        </nav>

    @endif

</main>
@endsection
