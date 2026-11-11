@extends('layouts.layout')

@section('content')
<main class="container mt-5 py-5" style="min-height: 70vh;">
    <header class="mb-4">
        <span class="small text-secondary text-uppercase fw-semibold">Área do cliente</span>
        <h1 class="h2 fw-bold mt-2">Minha área</h1>
        <p class="text-secondary mb-0">Consulte seus atendimentos e seus dados. Os recursos são liberados por etapas, sem alterar os seus registros anteriores.</p>
    </header>

    <div class="row g-3">
        @if(app(\App\Services\Marketing\LiberacaoPortalCliente::class)->permitido(auth()->user(), 'solicitacoes'))
            <div class="col-12 col-md-6 col-lg-4">
                <article class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h5"><i class="bi bi-chat-square-text me-2"></i>Solicitações de orçamento</h2>
                        <p class="text-secondary small">Acompanhe apenas pedidos associados à sua conta pela oficina.</p>
                        <a class="btn btn-outline-primary" href="{{ route('cliente.solicitacoes.index') }}">Ver solicitações</a>
                    </div>
                </article>
            </div>
        @endif

        @foreach([
            ['Produtos disponíveis', 'cliente.produtos.index', 'bi-box-seam', 'Consulte produtos disponíveis para atendimento.'],
            ['Minhas notas', 'cliente.notas.index', 'bi-receipt', 'Consulte as notas associadas ao seu cadastro.'],
            ['Minhas ordens de serviço', 'cliente.os.index', 'bi-wrench', 'Acompanhe os serviços cadastrados pela oficina.'],
            ['Meus veículos', 'cliente.veiculos.index', 'bi-car-front', 'Consulte seus veículos vinculados.'],
            ['Meus endereços', 'cliente.enderecos.index', 'bi-geo-alt', 'Consulte ou atualize seus endereços.'],
        ] as $recurso)
            <div class="col-12 col-md-6 col-lg-4">
                <article class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h5"><i class="bi {{ $recurso[2] }} me-2"></i>{{ $recurso[0] }}</h2>
                        <p class="text-secondary small">{{ $recurso[3] }}</p>
                        <a class="btn btn-outline-primary" href="{{ route($recurso[1]) }}">Acessar</a>
                    </div>
                </article>
            </div>
        @endforeach
    </div>

    <p class="small text-secondary mt-4">Uma solicitação não confirma disponibilidade, compatibilidade ou venda. A equipe valida as condições comerciais antes de concluir o atendimento.</p>
</main>
@endsection
