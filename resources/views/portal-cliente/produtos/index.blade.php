@extends('layouts.layout')

@section('content')

<main class="container mt-5 py-5" style="min-height: 70vh;">

    <div class="row mb-4">
        <div class="col-12 col-lg-9">

            <span class="text-uppercase text-secondary small fw-semibold">
                Área do cliente
            </span>

            <h1 class="h2 fw-bold mt-2">
                Produtos disponíveis
            </h1>

            <p class="text-secondary mb-0">
                Consulte produtos disponíveis no estoque da oficina.
                A disponibilidade e os valores podem mudar
                até a confirmação do atendimento.
            </p>

        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">

            <form
                method="GET"
                action="{{ route('cliente.produtos.index') }}"
                role="search"
            >

                <label for="busca-produto" class="form-label fw-semibold">
                    Buscar produto ou marca
                </label>

                <div class="row g-2">
                    <div class="col-12 col-md-8">

                        <input
                            id="busca-produto"
                            name="busca"
                            type="search"
                            class="form-control @error('busca') is-invalid @enderror"
                            placeholder="Ex.: filtro de óleo"
                            maxlength="80"
                            autocomplete="off"
                            value="{{ $busca }}"
                        >

                        @error('busca')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                    <div class="col-12 col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-warning flex-grow-1">
                            <i class="bi bi-search me-1"></i>
                            Buscar
                        </button>

                        <a
                            href="{{ route('cliente.produtos.index') }}"
                            class="btn btn-outline-secondary"
                            aria-label="Limpar busca"
                        >
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </div>

            </form>

        </div>
    </div>

    @if ($produtos->isEmpty())

        <div class="alert alert-light border" role="status">
            <i class="bi bi-info-circle me-2"></i>
            Nenhum produto disponível encontrado.
        </div>

    @else

        <div class="row g-3 mb-4">

            @foreach ($produtos as $produto)

                <div class="col-12 col-md-6 col-xl-4">

                    <article class="card h-100 border-0 shadow-sm">

                        <div class="card-body d-flex flex-column">

                            <div class="mb-3">
                                <span class="badge bg-success-subtle text-success-emphasis">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Disponível na oficina
                                </span>
                            </div>

                            <div class="text-secondary small mb-2">
                                {{ $produto->marcaRelacionada?->nome ?? 'Marca não informada' }}
                            </div>

                            <h2 class="h5 fw-semibold mb-3">
                                {{ $produto->nome }}
                            </h2>

                            <div class="mt-auto pt-3 border-top">

                                @if ((float) $produto->preco_uni > 0)

                                    <div class="small text-secondary mb-1">
                                        Preço de venda
                                    </div>

                                    <div class="h5 fw-bold mb-0">
                                        R$ {{ number_format((float) $produto->preco_uni, 2, ',', '.') }}
                                    </div>

                                @else

                                    <div class="text-secondary fw-semibold">
                                        Consulte o preço com a oficina
                                    </div>

                                @endif

                            </div>

                        </div>

                    </article>

                </div>

            @endforeach

        </div>

        <nav aria-label="Páginas dos produtos disponíveis">
            {{ $produtos->links() }}
        </nav>

    @endif

</main>

@endsection
