@extends('layouts.layout')

@section('content')
<main class="container mt-5 py-5" style="min-height: 70vh;">

    <header class="mb-4">
        <span class="small text-secondary text-uppercase fw-semibold">
            Área do cliente
        </span>
        <h1 class="h2 fw-bold mt-2">Minhas notas</h1>
        <p class="text-secondary">
            Consulte o histórico das notas vinculadas ao seu cadastro.
        </p>
    </header>

    @if(app(\App\Services\Marketing\LiberacaoPortalCliente::class)->permitido(auth()->user(), 'solicitacoes'))
        <p><a class="btn btn-outline-primary" href="{{ route('cliente.solicitacoes.index') }}">Minhas solicitações de orçamento</a></p>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('cliente.notas.index') }}">
                <label for="veiculo" class="form-label fw-semibold">
                    Filtrar por veículo
                </label>
                <div class="row g-2">
                    <div class="col-12 col-md-8">
                        <select name="veiculo" id="veiculo" class="form-select">
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
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-md-4 d-flex gap-2">
                        <button class="btn btn-warning flex-grow-1" type="submit">
                            Filtrar
                        </button>
                        <a class="btn btn-outline-secondary"
                           href="{{ route('cliente.notas.index') }}">
                            Limpar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if ($notas->isEmpty())
        <div class="alert alert-light border" role="status">
            Nenhuma nota encontrada.
        </div>
    @else
        <div class="row g-3 mb-4">
            @foreach ($notas as $nota)
                <div class="col-12 col-md-6">
                    <article class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between gap-2">
                                <h2 class="h5 fw-bold">
                                    Nota #{{ str_pad((string) $nota->id, 6, '0', STR_PAD_LEFT) }}
                                </h2>
                                <span class="badge text-bg-secondary">
                                    {{ $nota->status ?: 'Não informado' }}
                                </span>
                            </div>

                            <p class="text-secondary small mb-2">
                                {{ $nota->created_at?->format('d/m/Y') ?? 'Data não informada' }}
                            </p>

                            <p class="mb-2">
                                <strong>Veículo:</strong>
                                @if ($nota->veiculosCliente)
                                    {{ $nota->veiculosCliente->veiculo?->nome ?? 'Veículo' }}
                                    — {{ $nota->veiculosCliente->placa ?? 'Sem placa' }}
                                @else
                                    Não informado
                                @endif
                            </p>

                            <p class="mb-3">
                                <strong>Valor total:</strong>
                                R$ {{ number_format((float) $nota->total, 2, ',', '.') }}
                            </p>

                            <a class="btn btn-outline-primary"
                               href="{{ route('cliente.notas.show', $nota->id) }}">
                                Consultar nota
                            </a>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
        <nav aria-label="Paginação das minhas notas">
            {{ $notas->links() }}
        </nav>
    @endif
</main>
@endsection
