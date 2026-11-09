@extends('layouts.layout')

@section('content')
<main class="container mt-5 py-5" style="min-height: 70vh;">

    <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <span class="small text-secondary text-uppercase fw-semibold">
                Área do cliente
            </span>
            <h1 class="h2 fw-bold mt-2">Meus endereços</h1>
            <p class="text-secondary mb-0">
                Consulte e atualize os endereços do seu cadastro.
            </p>
        </div>

        <a href="{{ route('cliente.enderecos.create') }}"
           class="btn btn-warning">
            <i class="bi bi-plus-lg me-1"></i>
            Novo endereço
        </a>
    </header>

    @if (session('success'))
        <div class="alert alert-success" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if ($enderecos->isEmpty())
        <div class="alert alert-light border" role="status">
            Nenhum endereço cadastrado.
        </div>
    @else

        <div class="row g-3 mb-4">
            @foreach ($enderecos as $endereco)
                <div class="col-12 col-md-6">
                    <article class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex flex-column">

                            <h2 class="h5 fw-semibold mb-3">
                                {{ $endereco->rua }},
                                {{ $endereco->numero }}
                            </h2>

                            <p class="mb-2">
                                {{ $endereco->bairro }}
                            </p>

                            <p class="text-secondary mb-2">
                                {{ $endereco->cidade }}
                                — {{ $endereco->estado }}
                            </p>

                            <p class="mb-2">
                                <strong>CEP:</strong>
                                {{ $endereco->cep }}
                            </p>

                            @if ($endereco->ponto_referencia)
                                <p class="small text-secondary">
                                    Referência:
                                    {{ $endereco->ponto_referencia }}
                                </p>
                            @endif

                            <div class="mt-auto pt-3">
                                <a
                                    class="btn btn-outline-primary btn-sm"
                                    href="{{ route('cliente.enderecos.edit', $endereco->id) }}"
                                >
                                    <i class="bi bi-pencil-square me-1"></i>
                                    Editar endereço
                                </a>
                            </div>

                        </div>
                    </article>
                </div>
            @endforeach
        </div>

        <nav aria-label="Paginação dos endereços">
            {{ $enderecos->links() }}
        </nav>
    @endif

</main>
@endsection
