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

    <title>Oficina SOS Mecânica {{ env('APP_VERSION') }}</title>

    <!-- Inputmask -->
    <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.6/dist/inputmask.min.js"></script>
</head>

<body>

<!-- ======= Header ======= -->
<nav id="header" class="navbar navbar-expand-lg fixed-top">

    <div class="container-fluid">

        <!-- Logo -->
        <h1 class="mb-0">
            <a href="/">
                <img src="/img/New Logo.png" alt="SOS Mecânica">
            </a>
        </h1>

        <!-- Mobile Toggle -->
        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarNavDropdown"
            aria-controls="navbarNavDropdown"
            aria-expanded="false"
            aria-label="Abrir menu"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNavDropdown">

            <ul class="navbar-nav ms-auto align-items-lg-center">

                {{-- ========================================================= --}}
                {{-- USUÁRIO NÃO AUTENTICADO --}}
                {{-- ========================================================= --}}

                @guest

                    <li class="nav-item">
                        <a class="nav-link active" href="/">
                            <i class="bi bi-house-door-fill"></i>
                            Home
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#about">
                            <i class="bi bi-info-circle-fill"></i>
                            Sobre
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#team">
                            <i class="bi bi-people-fill"></i>
                            Equipe
                        </a>
                    </li>

                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-success" href="/login">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Entrar
                        </a>
                    </li>

                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-warning" href="/register">
                            <i class="bi bi-person-plus-fill"></i>
                            Cadastre-se
                        </a>
                    </li>

                @endguest


                {{-- ========================================================= --}}
                {{-- USUÁRIO AUTENTICADO --}}
                {{-- ========================================================= --}}

                @auth

                    {{-- ===================================================== --}}
                    {{-- PERMISSÃO 1 --}}
                    {{-- ===================================================== --}}

                    @if(auth()->user()->permitions === 1)

                        {{-- ================================================= --}}
                        {{-- ATENDIMENTO --}}
                        {{-- ================================================= --}}

                        <li class="nav-item dropdown">

                            <a
                                class="nav-link dropdown-toggle"
                                href="#"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                <i class="bi bi-tools"></i>
                                Atendimento
                            </a>

                            <ul class="dropdown-menu">

                                <li>
                                    <a
                                        href="{{ route('clientes.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-people-fill me-2"></i>
                                        Clientes
                                    </a>
                                </li>

                                <li>
                                    <a
                                        href="{{ route('veiculosclientes.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-car-front-fill me-2"></i>
                                        Veículos dos Clientes
                                    </a>
                                </li>

                                <li>
                                    <a
                                        href="{{ route('ordemservicos.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-wrench-adjustable-circle-fill me-2"></i>
                                        Ordens de Serviço
                                    </a>
                                </li>

                                <li>
                                    <a
                                        href="{{ route('notasitem.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-receipt me-2"></i>
                                        Notas
                                    </a>
                                </li>

                                <li>
                                    <a
                                        href="#"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-clock-history me-2"></i>
                                        Histórico
                                    </a>
                                </li>

                            </ul>

                        </li>


                        {{-- ================================================= --}}
                        {{-- OPERAÇÕES --}}
                        {{-- ================================================= --}}

                        <li class="nav-item dropdown">

                            <a
                                class="nav-link dropdown-toggle"
                                href="#"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                <i class="bi bi-boxes"></i>
                                Operações
                            </a>

                            <ul class="dropdown-menu">

                                <li>
                                    <a
                                        href="{{ route('compras.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-cart-check me-2"></i>
                                        Compras
                                    </a>
                                </li>

                                <li>
                                    <a
                                        href="{{ route('produtos.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-box-seam me-2"></i>
                                        Produtos
                                    </a>
                                </li>

                                <li>
                                    <a
                                        href="{{ route('estoque.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-boxes me-2"></i>
                                        Estoque
                                    </a>
                                </li>

                                <li>
                                    <a
                                        href="{{ route('estoque.movimentacoes.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-arrow-left-right me-2"></i>
                                        Movimentações de Estoque
                                    </a>
                                </li>

                            </ul>

                        </li>


                        {{-- ================================================= --}}
                        {{-- FINANCEIRO --}}
                        {{-- ================================================= --}}

                        <li class="nav-item dropdown">

                            <a
                                class="nav-link dropdown-toggle"
                                href="#"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                <i class="bi bi-cash-stack"></i>
                                Financeiro
                            </a>

                            <ul class="dropdown-menu">

                                <li>
                                    <a
                                        href="{{ route('contas-receber.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-arrow-down-circle me-2"></i>
                                        Contas a Receber
                                    </a>
                                </li>

                                <li>
                                    <a
                                        href="{{ route('contas-pagar.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-arrow-up-circle me-2"></i>
                                        Contas a Pagar
                                    </a>
                                </li>

                                <li>
                                    <a
                                        href="#"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-graph-up-arrow me-2"></i>
                                        Fluxo de Caixa
                                    </a>
                                </li>

                                <li>
                                    <a
                                        href="#"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-bar-chart-line me-2"></i>
                                        Relatórios
                                    </a>
                                </li>

                            </ul>

                        </li>


                        {{-- ================================================= --}}
                        {{-- CADASTROS --}}
                        {{-- ================================================= --}}

                        <li class="nav-item dropdown">

                            <a
                                class="nav-link dropdown-toggle"
                                href="#"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                <i class="bi bi-gear-fill"></i>
                                Cadastros
                            </a>

                            <ul class="dropdown-menu dropdown-menu-end">

                                {{-- FORNECEDORES --}}

                                <li>
                                    <a
                                        href="{{ route('fornecedores.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-building me-2"></i>
                                        Fornecedores
                                    </a>
                                </li>

                                <li>
                                    <hr class="dropdown-divider">
                                </li>

                                {{-- VEÍCULOS --}}

                                <li>
                                    <a
                                        href="{{ route('veiculos.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-car-front me-2"></i>
                                        Veículos
                                    </a>
                                </li>

                                <li>
                                    <a
                                        href="{{ route('montadoras.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-buildings me-2"></i>
                                        Montadoras
                                    </a>
                                </li>

                                <li>
                                    <hr class="dropdown-divider">
                                </li>

                                {{-- SERVIÇOS --}}

                                <li>
                                    <a
                                        href="{{ route('setor-servicos.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-diagram-3-fill me-2"></i>
                                        Setores de Serviço
                                    </a>
                                </li>

                                <li>
                                    <hr class="dropdown-divider">
                                </li>

                                {{-- FINANCEIRO --}}

                                <li>
                                    <a
                                        href="{{ route('formas-pagamento.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-credit-card-fill me-2"></i>
                                        Formas de Pagamento
                                    </a>
                                </li>

                                <li>
                                    <a
                                        href="{{ route('categorias-financeiras.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-tags-fill me-2"></i>
                                        Categorias Financeiras
                                    </a>
                                </li>

                                <li>
                                    <hr class="dropdown-divider">
                                </li>

                                {{-- ADMINISTRAÇÃO --}}

                                <li>
                                    <a
                                        href="{{ route('users.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-people-fill me-2"></i>
                                        Usuários
                                    </a>
                                </li>

                                <li>
                                    <a
                                        href="{{ route('colaboradores.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-person-badge-fill me-2"></i>
                                        Colaboradores
                                    </a>
                                </li>

                            </ul>

                        </li>

                    @endif


                    {{-- ===================================================== --}}
                    {{-- PERMISSÃO 2 --}}
                    {{-- ===================================================== --}}

                    @if(auth()->user()->permitions === 2)

                        {{-- ATENDIMENTO --}}

                        <li class="nav-item dropdown">

                            <a
                                class="nav-link dropdown-toggle"
                                href="#"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                <i class="bi bi-tools"></i>
                                Atendimento
                            </a>

                            <ul class="dropdown-menu">

                                <li>
                                    <a
                                        href="{{ route('veiculosclientes.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-car-front-fill me-2"></i>
                                        Veículos
                                    </a>
                                </li>

                                <li>
                                    <a
                                        href="#"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-clock-history me-2"></i>
                                        Históricos
                                    </a>
                                </li>

                            </ul>

                        </li>

                    @endif


                    {{-- ===================================================== --}}
                    {{-- USUÁRIO --}}
                    {{-- ===================================================== --}}

                    <li class="nav-item dropdown ms-lg-2">

                        <a
                            class="nav-link dropdown-toggle"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            <i class="bi bi-person-circle"></i>
                            {{ auth()->user()->name }}
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end">

                            <li>
                                <a
                                    href="/perfil"
                                    class="dropdown-item"
                                >
                                    <i class="bi bi-person-circle me-2"></i>
                                    Meu Perfil
                                </a>
                            </li>

                            <li>
                                <hr class="dropdown-divider">
                            </li>

                            <li>

                                <form action="/logout" method="POST">
                                    @csrf

                                    <button type="submit" class="dropdown-item btn-sair">
                                        <i class="bi bi-box-arrow-right me-2"></i>
                                        Sair
                                    </button>

                                </form>

                            </li>

                        </ul>

                    </li>

                @endauth

            </ul>

        </div>

    </div>

</nav>


@include('errors.error-message')

@yield('content')


<!-- ======= Footer ======= -->

<footer id="footer">

    <div class="container footer-bottom clearfix">

        <div class="copyright">

            &copy; Copyright 2026

            <strong>
                <span>Oficina SOS Mecânica</span>
            </strong>.

            All Rights Reserved

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

<script src="{{ asset('/vendor/glightbox/js/glightbox.min.js') }}"></script>

<script src="{{ asset('/vendor/isotope-layout/isotope.pkgd.min.js') }}"></script>

<script src="{{ asset('/vendor/php-email-form/validate.js') }}"></script>

<script src="{{ asset('/vendor/swiper/swiper-bundle.min.js') }}"></script>

<script src="{{ asset('/vendor/waypoints/noframework.waypoints.js') }}"></script>


<!-- jQuery -->

<script
    src="https://code.jquery.com/jquery-3.7.1.js"
    integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
    crossorigin="anonymous"
></script>


<!-- Bootstrap JS -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

@yield('scripts')

</body>

</html>
