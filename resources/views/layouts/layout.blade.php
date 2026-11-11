<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Bootstrap -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        crossorigin="anonymous"
    >

    <!-- Favicons -->
    <link href="{{ asset('img/favicon.png') }}" rel="icon">
    <link href="{{ asset('img/apple-touch-icon.png') }}" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Jost:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i"
        rel="stylesheet"
    >

    <!-- Waypoints -->
    <script src="{{ asset('/vendor/waypoints/noframework.waypoints.js') }}"></script>

    @vite([
        'resources/js/app.js',
        'resources/scss/_app.scss',
        'resources/js/cadError.js'
    ])

    {{-- CSS exclusivo da pagina, apos os estilos compartilhados --}}
    @stack('p04-css-pagina')

    <title>Oficina SOS Mecânica {{ env('APP_VERSION') }}</title>

    <!-- Inputmask -->
    @stack('p04-inputmask')
</head>

<body>

<!-- Navegacao publica e interna: um unico componente, sem links ficticios. -->
<nav id="header" class="navbar navbar-expand-lg fixed-top" aria-label="Menu principal">
    <div class="container-fluid">
        <a class="navbar-brand mb-0" href="{{ url('/') }}" aria-label="SOS Mecânica - início">
            <img src="{{ asset('img/New Logo.png') }}" alt="SOS Mecânica" width="160" height="48">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Abrir menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                @guest
                    @foreach([['Home', ''], ['Serviços', '#servicos'], ['Sobre', '#sobre'], ['Localização', '#localizacao'], ['Contato', '#contato']] as $link)
                        <li class="nav-item"><a class="nav-link" href="{{ url('/') }}{{ $link[1] }}">{{ $link[0] }}</a></li>
                    @endforeach
                    <li class="nav-item ms-lg-2"><a class="btn btn-success" href="{{ url('/login') }}"><i class="bi bi-box-arrow-in-right"></i> Entrar</a></li>
                    @if (Route::has('register'))
                        <li class="nav-item ms-lg-2"><a class="btn btn-warning" href="{{ route('register') }}">Cadastre-se</a></li>
                    @endif
                @endguest
                @auth
                    @php
                        $gruposMenu = [];
                        if (auth()->user()->isAdmin()) {
                            $gruposMenu = [
                                ['Atendimento', 'bi-tools', [
                                    ['Clientes', 'clientes.index', 'bi-people'],
                                    ['Veículos dos Clientes', 'veiculosclientes.index', 'bi-car-front'],
                                    ['Ordens de Serviço', 'ordemservicos.index', 'bi-wrench'],
                                    ['Notas', 'notasitem.index', 'bi-receipt'],
                                    ['Orçamentos de peças', 'marketing.leads.index', 'bi-chat-square-text'],
                                ]],
                                ['Operações', 'bi-boxes', [
                                    ['Compras', 'compras.index', 'bi-cart-check'],
                                    ['Produtos', 'produtos.index', 'bi-box-seam'],
                                    ['Estoque', 'estoque.index', 'bi-boxes'],
                                    ['Movimentações de Estoque', 'estoque.movimentacoes.index', 'bi-arrow-left-right'],
                                ]],
                                ['Financeiro', 'bi-cash-stack', [
                                    ['Contas a Receber', 'contas-receber.index', 'bi-arrow-down-circle'],
                                    ['Contas a Pagar', 'contas-pagar.index', 'bi-arrow-up-circle'],
                                    ['Financeiro dos Sócios', 'socios-financeiro.index', 'bi-people'],
                                ]],
                                ['Cadastros', 'bi-gear', [
                                    ['Fornecedores', 'fornecedores.index', 'bi-building'],
                                    ['Marcas', 'marcas.index', 'bi-tags'],
                                    ['Veículos', 'veiculos.index', 'bi-car-front'],
                                    ['Montadoras', 'montadoras.index', 'bi-buildings'],
                                    ['Setores de Serviço', 'setor-servicos.index', 'bi-diagram-3'],
                                    ['Etapas do Atendimento', 'etapas.index', 'bi-signpost-split'],
                                    ['Formas de Pagamento', 'formas-pagamento.index', 'bi-credit-card'],
                                    ['Categorias Financeiras', 'categorias-financeiras.index', 'bi-tags'],
                                    ['Usuários', 'users.index', 'bi-people'],
                                    ['Colaboradores', 'colaboradores.index', 'bi-person-badge'],
                                ]],
                            ];
                            if (\Illuminate\Support\Facades\Schema::hasTable('marketing_cliente_acessos')) {
                                $gruposMenu[0][2][] = ['Acessos do portal', 'marketing.clientes-acessos.index', 'bi-person-check'];
                            }
                        } elseif (auth()->user()->isCliente()) {
                            $itensCliente = [
                                ['Produtos disponíveis', 'cliente.produtos.index', 'bi-box-seam'],
                                ['Minhas notas', 'cliente.notas.index', 'bi-receipt'],
                                ['Minhas OS', 'cliente.os.index', 'bi-wrench'],
                                ['Meus veículos', 'cliente.veiculos.index', 'bi-car-front'],
                                ['Meus endereços', 'cliente.enderecos.index', 'bi-geo-alt'],
                            ];
                            $liberacaoMarketing = app(\App\Services\Marketing\LiberacaoPortalCliente::class);
                            if ($liberacaoMarketing->permitido(auth()->user(), 'portal_inicio')) {
                                array_unshift($itensCliente, ['Minha área', 'cliente.area.index', 'bi-grid']);
                            }
                            if ($liberacaoMarketing->permitido(auth()->user(), 'solicitacoes')) {
                                $itensCliente[] = ['Minhas solicitações de orçamento', 'cliente.solicitacoes.index', 'bi-chat-square-text'];
                            }
                            $gruposMenu = [['Atendimento', 'bi-tools', $itensCliente]];
                        }
                    @endphp
                    @foreach($gruposMenu as $grupo)
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi {{ $grupo[1] }}"></i> {{ $grupo[0] }}</a>
                            <ul class="dropdown-menu {{ $loop->last ? 'dropdown-menu-end' : '' }}">
                                @foreach($grupo[2] as $item)
                                    <li><a href="{{ route($item[1]) }}" class="dropdown-item {{ request()->routeIs($item[1]) ? 'active' : '' }}"><i class="bi {{ $item[2] }} me-2"></i>{{ $item[0] }}</a></li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                    <li class="nav-item dropdown ms-lg-2">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-person-circle"></i> {{ auth()->user()->name }}</a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ url('/perfil') }}"><i class="bi bi-person-circle me-2"></i>Meu Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><form action="{{ url('/logout') }}" method="POST">@csrf<button class="dropdown-item btn-sair" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Sair</button></form></li>
                        </ul>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

@include('errors.error-message')

@yield('content')

<footer id="footer" class="py-4" aria-label="Rodapé da SOS Mecânica">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <strong class="text-warning">Oficina SOS Mecânica</strong>
            <div class="small text-white-50">&copy; {{ date('Y') }} · Feira de Santana - BA</div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-3 small">
            <a href="https://wa.me/{{ preg_replace('/\D+/', '', config('oficina.whatsapp')) }}" target="_blank" rel="noopener noreferrer"><i class="bi bi-whatsapp"></i> WhatsApp</a>
            <a href="{{ url('/#localizacao') }}"><i class="bi bi-geo-alt"></i> Endereço</a>
            @if(config('oficina.email'))
                <a href="mailto:{{ config('oficina.email') }}"><i class="bi bi-envelope"></i> E-mail</a>
            @endif
            <details class="small text-white-50">
                <summary class="sos-credit-trigger">Créditos</summary>
                <span>Desenvolvido por Mateus Santos.</span>
            </details>
        </div>
    </div>
</footer>

<div id="preloader"></div>

<!-- ======= Back To Top ======= -->

<a
    href="#"
    class="back-to-top d-flex align-items-center justify-content-center"
>
    <img
        src="{{ asset('svg/arrow-up.svg') }}"
        alt="Voltar ao topo"
    >
</a>

<!-- ======= Vendor JS Files ======= -->

<script src="{{ asset('/vendor/aos/aos.js') }}"></script>

<!-- jQuery -->

<script
    src="https://code.jquery.com/jquery-3.7.1.js"
    integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
    crossorigin="anonymous"
></script>

<!-- Bootstrap JS -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

@yield('scripts')


<script id="bootstrap-modal-body-portal">
document.addEventListener('DOMContentLoaded', function () {
    /*
     * Bootstrap cria o backdrop diretamente sob o <body>.
     *
     * Se um .modal estiver dentro de um elemento que cria
     * stacking context (como .cadastro, que possui z-index),
     * o modal pode ficar atrás do backdrop mesmo tendo um
     * z-index numericamente maior.
     *
     * Todos os modais Bootstrap são promovidos para o body,
     * eliminando esse conflito de empilhamento globalmente.
     */
    const moverModalParaBody = function (modal) {
        if (
            modal instanceof HTMLElement
            && modal.classList.contains('modal')
            && modal.parentElement !== document.body
        ) {
            document.body.appendChild(modal);
        }
    };

    document
        .querySelectorAll('.modal')
        .forEach(moverModalParaBody);

    /*
     * Proteção adicional para modais inseridos
     * dinamicamente depois do carregamento.
     */
    document.addEventListener(
        'show.bs.modal',
        function (event) {
            moverModalParaBody(event.target);
        }
    );
});
</script>

</body>
</html>
