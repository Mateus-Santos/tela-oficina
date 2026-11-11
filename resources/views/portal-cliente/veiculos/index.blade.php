@extends('layouts.layout')

@section('content')
<main class="container mt-5 py-5" style="min-height: 70vh;">

    <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <span class="small text-secondary text-uppercase fw-semibold">
                Área do cliente
            </span>

            <h1 class="h2 fw-bold mt-2">Meus veículos</h1>

            <p class="text-secondary mb-0">
                Consulte os veículos associados ao seu cadastro.
            </p>
        </div>

        @if (auth()->user()->cliente)
            <a
                href="{{ route('veiculosclientes.create') }}"
                class="btn btn-warning"
            >
                <i class="bi bi-plus-lg me-1"></i>
                Cadastrar veículo
            </a>
        @endif
    </header>

    @if (session('success'))
        <div class="alert alert-success" role="status">
            {{ session('success') }}
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form
                method="GET"
                action="{{ route('cliente.veiculos.index') }}"
            >
                <label for="placa" class="form-label fw-semibold">
                    Pesquisar por placa
                </label>

                <div class="row g-2">
                    <div class="col-12 col-md-8">
                        <input
                            type="search"
                            id="placa"
                            name="placa"
                            class="form-control"
                            maxlength="8"
                            placeholder="Ex.: ABC1D23"
                            value="{{ $placa }}"
                            autocomplete="off"
                        >
                    </div>

                    <div class="col-12 col-md-4 d-flex gap-2">
                        <button class="btn btn-warning flex-grow-1" type="submit">
                            Buscar
                        </button>

                        <a
                            class="btn btn-outline-secondary"
                            href="{{ route('cliente.veiculos.index') }}"
                        >
                            Limpar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if ($veiculosclientes->isEmpty())

        <div class="alert alert-light border" role="status">
            Nenhum veículo encontrado.
        </div>

    @else

        <div class="row g-3 mb-4">
            @foreach ($veiculosclientes as $item)
                <div class="col-12 col-md-6 col-xl-4">
                    <article class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex flex-column">

                            <div class="mb-2">
                                <span class="badge text-bg-secondary">
                                    <i class="bi bi-car-front me-1"></i>
                                    Veículo cadastrado
                                </span>
                            </div>

                            <h2 class="h5 fw-bold mb-2">
                                {{ $item->veiculo?->nome ?? 'Veículo' }}
                            </h2>

                            <p class="text-secondary small mb-3">
                                {{ $item->veiculo?->montadora?->nome ?? 'Montadora não informada' }}
                            </p>

                            <dl class="row small mb-3">
                                <dt class="col-4">Placa</dt>
                                <dd class="col-8 fw-semibold">
                                    {{ $item->placa }}
                                </dd>

                                <dt class="col-4">Ano</dt>
                                <dd class="col-8">
                                    {{ $item->ano ?? 'Não informado' }}
                                </dd>

                                <dt class="col-4">Cor</dt>
                                <dd class="col-8">
                                    {{ $item->cor ?: 'Não informada' }}
                                </dd>
                            </dl>

                            @if ($item->clientes_count > 1)
                                <p class="small text-secondary">
                                    Veículo compartilhado. Alterações devem
                                    ser solicitadas à oficina.
                                </p>
                            @endif

                            <div class="d-flex flex-wrap gap-2 mt-auto">
                                <a
                                    class="btn btn-outline-primary btn-sm"
                                    href="{{ route('cliente.veiculos.show', $item->id) }}"
                                >
                                    Ver detalhes
                                </a>

                                @if ($item->clientes_count === 1)
                                    <a
                                        class="btn btn-outline-secondary btn-sm"
                                        href="{{ route('veiculosclientes.edit', $item->id) }}"
                                    >
                                        Editar
                                    </a>
                                @endif
                            </div>

                        </div>
                    </article>
                </div>
            @endforeach
        </div>

        <nav aria-label="Páginas dos meus veículos">
            {{ $veiculosclientes->links() }}
        </nav>

    @endif

</main>
@endsection
