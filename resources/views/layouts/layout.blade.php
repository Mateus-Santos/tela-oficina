<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="utf-8">

    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- IMPORTANDO BOOTSTRAP -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
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

    <!-- Template Main CSS File -->

    <script src="{{ asset('/vendor/waypoints/noframework.waypoints.js') }}"></script>

    @vite([
        'resources/js/app.js',
        'resources/scss/_app.scss',
        'resources/js/cadError.js'
    ])

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"
    ></script>

    <title>Oficina SOS Mecânica {{ env('APP_VERSION') }}</title>

    <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.6/dist/inputmask.min.js"></script>

</head>

<body>

<!-- ======= Header ======= -->

<nav id="header" class="navbar navbar-expand-lg fixed-top">

    <div class="container-fluid">

        <h1>
            <a href="/">

                <img
                    src="/img/New Logo.png"
                    alt="SOS Mecânica"
                >

            </a>

        </h1>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarNavDropdown"
            aria-controls="navbarNavDropdown"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

    </div>

    <div class="collapse navbar-collapse" id="navbarNavDropdown">

        <ul class="navbar-nav">

            {{-- ========================================================= --}}
            {{-- USUÁRIO NÃO AUTENTICADO --}}
            {{-- ========================================================= --}}

            @guest

                <li class="nav-item">

                    <a
                        class="nav-link active"
                        href="#"
                    >

                        <i class="bi bi-house-door-fill"></i>
                        Home
                    </a>
                </li>

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="#about"
                    >

                        <i class="bi bi-info-circle-fill"></i>

                        Sobre

                    </a>

                </li>

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="#team"
                    >

                        <i class="bi bi-people-fill"></i>
                        Equipe
                    </a>
                </li>

                <li class="nav-item">

                    <a
                        class="btn btn-success"
                        href="/login"
                    >

                        <i class="bi bi-box-arrow-in-right"></i>

                        Entrar

                    </a>

                </li>

                <li class="nav-item">

                    <a
                        class="btn btn-warning"
                        href="/register"
                    >

                        <i class="bi bi-person-plus-fill"></i>

                        Cadastre-se

                    </a>

                </li>

            @endguest


            {{-- ========================================================= --}}
            {{-- USUÁRIO AUTENTICADO --}}
            {{-- ========================================================= --}}

            @auth

                {{-- ========================================================= --}}
                {{-- ATENDIMENTO --}}
                {{-- ========================================================= --}}

                @if(auth()->user()->permitions === 1)

                    <li class="nav-item dropdown">

                        <a
                            class="btn btn-warning dropdown-toggle"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >

                            <i class="bi bi-tools"></i>

                            Atendimento

                        </a>

                        <ul class="dropdown-menu">

                            {{-- ================================================= --}}
                            {{-- CLIENTES --}}
                            {{-- ================================================= --}}

                            <li>

                                <a
                                    href="{{ route('clientes.index') }}"
                                    class="dropdown-item"
                                >

                                    <i class="bi bi-people-fill me-2"></i>

                                    Clientes

                                </a>

                            </li>


                            {{-- ================================================= --}}
                            {{-- VEÍCULOS DOS CLIENTES --}}
                            {{-- ================================================= --}}

                            <li>

                                <a
                                    href="{{ route('veiculosclientes.index') }}"
                                    class="dropdown-item"
                                >

                                    <i class="bi bi-car-front-fill me-2"></i>

                                    Veículos dos Clientes

                                </a>

                            </li>


                            {{-- ================================================= --}}
                            {{-- ORDEM DE SERVIÇO --}}
                            {{-- ================================================= --}}

                            <li>

                                <a
                                    href="{{ route('ordemservicos.index') }}"
                                    class="dropdown-item"
                                >

                                    <i class="bi bi-wrench-adjustable-circle-fill me-2"></i>

                                    Ordens de Serviço

                                </a>

                            </li>


                            {{-- ================================================= --}}
                            {{-- HISTÓRICO --}}
                            {{-- ================================================= --}}

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

                @endif


                {{-- ========================================================= --}}
                {{-- LOJA --}}
                {{-- ========================================================= --}}

                @if(auth()->user()->permitions === 1)

                    <li class="nav-item dropdown">

                        <a
                            class="btn btn-warning dropdown-toggle"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >

                            <i class="bi bi-bag-fill"></i>

                            Loja

                        </a>

                        <ul class="dropdown-menu">

                            <li>

                                <a
                                    href="{{ route('produtos.index') }}"
                                    class="dropdown-item"
                                >

                                    <i class="bi bi-box-seam me-2"></i>

                                    Produtos

                                </a>

                            </li>

                        </ul>

                    </li>

                @endif


                {{-- ========================================================= --}}
                {{-- FINANCEIRO --}}
                {{-- ========================================================= --}}

                @if(auth()->user()->permitions === 1)

                    <li class="nav-item dropdown">

                        <a
                            class="btn btn-warning dropdown-toggle"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >

                            <i class="bi bi-cash-stack"></i>

                            Financeiro

                        </a>

                        <ul class="dropdown-menu">

                            {{-- CONTAS A RECEBER --}}

                            <li>

                                <a
                                    href="{{ route('contas-receber.index') }}"
                                    class="dropdown-item"
                                >

                                    <i class="bi bi-cash-stack me-2"></i>

                                    Contas a Receber

                                </a>

                            </li>

                            <li>

                                <a
                                    href="{{ route('contas-receber.create') }}"
                                    class="dropdown-item"
                                >

                                    <i class="bi bi-plus-circle me-2"></i>

                                    Nova Conta a Receber

                                </a>

                            </li>

                        </ul>

                    </li>

                @endif


                {{-- ========================================================= --}}
                {{-- NOTAS --}}
                {{-- ========================================================= --}}

                @if(auth()->user()->permitions === 1)

                    <li class="nav-item dropdown">

                        <a
                            class="btn btn-warning dropdown-toggle"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >

                            <i class="bi bi-receipt"></i>

                            Notas

                        </a>

                        <ul class="dropdown-menu">

                            <li>

                                <a
                                    href="{{ route('notasitem.index') }}"
                                    class="dropdown-item"
                                >

                                    <i class="bi bi-list-ul me-2"></i>

                                    Notas

                                </a>

                            </li>

                            <li>

                                <a
                                    href="{{ route('notasitem.create') }}"
                                    class="dropdown-item"
                                >

                                    <i class="bi bi-plus-circle me-2"></i>

                                    Nova Nota

                                </a>

                            </li>

                        </ul>

                    </li>

                @endif


                {{-- ========================================================= --}}
                {{-- SETUP --}}
                {{-- ========================================================= --}}

                @if(auth()->user()->permitions === 1)

                    <li class="nav-item dropdown">

                        <a
                            class="btn btn-warning dropdown-toggle"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >

                            <i class="bi bi-gear-fill"></i>

                            Setup

                        </a>


                        {{-- ================================================= --}}
                        {{-- MENU PRINCIPAL DO SETUP --}}
                        {{-- ================================================= --}}

                        <ul class="dropdown-menu dropdown-menu-end">


                            {{-- ================================================= --}}
                            {{-- ADMINISTRAÇÃO --}}
                            {{-- ================================================= --}}

                            <li class="dropdown-hover-submenu">

                                <a
                                    class="dropdown-item dropdown-toggle d-flex align-items-center justify-content-between"
                                    href="#"
                                >

                                    <span>

                                        <i class="bi bi-shield-lock-fill me-2"></i>

                                        Administração

                                    </span>

                                </a>


                                {{-- SUBMENU ABRE PARA A ESQUERDA --}}

                                <ul
                                    class="dropdown-menu"
                                    style="right: 100%; left: auto; top: 0; margin-right: 2px;"
                                >

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


                            {{-- ================================================= --}}
                            {{-- VEÍCULOS --}}
                            {{-- ================================================= --}}

                            <li class="dropdown-hover-submenu">

                                <a
                                    class="dropdown-item dropdown-toggle d-flex align-items-center justify-content-between"
                                    href="#"
                                >

                                    <span>

                                        <i class="bi bi-car-front-fill me-2"></i>

                                        Veículos

                                    </span>

                                </a>


                                {{-- SUBMENU ABRE PARA A ESQUERDA --}}

                                <ul
                                    class="dropdown-menu"
                                    style="right: 100%; left: auto; top: 0; margin-right: 2px;"
                                >

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

                                </ul>

                            </li>


                            {{-- ================================================= --}}
                            {{-- SERVIÇOS --}}
                            {{-- ================================================= --}}

                            <li class="dropdown-hover-submenu">

                                <a
                                    class="dropdown-item dropdown-toggle d-flex align-items-center justify-content-between"
                                    href="#"
                                >

                                    <span>

                                        <i class="bi bi-wrench-adjustable me-2"></i>

                                        Serviços

                                    </span>

                                </a>


                                {{-- SUBMENU ABRE PARA A ESQUERDA --}}

                                <ul
                                    class="dropdown-menu"
                                    style="right: 100%; left: auto; top: 0; margin-right: 2px;"
                                >

                                    <li>

                                        <a
                                            href="{{ route('setor-servicos.index') }}"
                                            class="dropdown-item"
                                        >

                                            <i class="bi bi-diagram-3-fill me-2"></i>

                                            Setores de Serviço

                                        </a>

                                    </li>

                                </ul>

                            </li>


                            {{-- ================================================= --}}
                            {{-- FINANCEIRO --}}
                            {{-- ================================================= --}}

                            <li class="dropdown-hover-submenu">

                                <a
                                    class="dropdown-item dropdown-toggle d-flex align-items-center justify-content-between"
                                    href="#"
                                >

                                    <span>

                                        <i class="bi bi-cash-coin me-2"></i>

                                        Financeiro

                                    </span>

                                </a>


                                {{-- SUBMENU ABRE PARA A ESQUERDA --}}

                                <ul
                                    class="dropdown-menu"
                                    style="right: 100%; left: auto; top: 0; margin-right: 2px;"
                                >

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

                                </ul>

                            </li>

                        </ul>

                    </li>

                @endif

                {{-- ========================================================= --}}
                {{-- ADMINISTRADOR / PERMISSÃO 2 --}}
                {{-- ========================================================= --}}

                @if(auth()->user()->permitions === 2)

                    {{-- VEÍCULOS DO SISTEMA --}}

                    <li class="nav-item dropdown">

                        <a
                            class="btn btn-warning dropdown-toggle"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >

                            <i class="bi bi-car-front-fill"></i>

                            Veículos

                        </a>

                        <ul class="dropdown-menu">

                            <li>

                                <a
                                    href="{{ route('veiculosclientes.index') }}"
                                    class="dropdown-item"
                                >

                                    <i class="bi bi-list-ul me-2"></i>

                                    Veículos
                                </a>

                            </li>

                            <li>

                                <a
                                    href="{{ route('veiculosclientes.create') }}"
                                    class="dropdown-item"
                                >

                                    <i class="bi bi-plus-circle me-2"></i>

                                    Cadastrar Veículo

                                </a>

                            </li>

                        </ul>

                    </li>


                    {{-- HISTÓRICOS --}}

                    <li class="nav-item">

                        <a
                            class="btn btn-warning"
                            href="#"
                        >

                            <i class="bi bi-clock-history"></i>

                            Históricos

                        </a>

                    </li>


                    {{-- PERFIL --}}

                    <li class="nav-item">

                        <a
                            class="btn btn-warning"
                            href="/perfil"
                        >

                            <i class="bi bi-person-circle"></i>

                            {{ auth()->user()->name }}

                        </a>

                    </li>

                @endif

                {{-- ========================================================= --}}
                {{-- SAIR --}}
                {{-- ========================================================= --}}

                <li class="nav-item">

                    <form
                        action="/logout"
                        method="POST"
                    >

                        @csrf

                        <a
                            class="btn btn-light"
                            href="/logout"
                            onclick="
                                event.preventDefault();
                                this.closest('form').submit();
                            "
                        >
                            <i class="bi bi-box-arrow-right"></i>
                            Sair
                        </a>

                    </form>

                </li>

            @endauth

        </ul>

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
    />

</a>

<!-- Vendor JS Files -->

<script src="{{ asset('/vendor/aos/aos.js') }}"></script>
<script src="{{ asset('/vendor/glightbox/js/glightbox.min.js') }}"></script>
<script src="{{ asset('/vendor/isotope-layout/isotope.pkgd.min.js') }}"></script>
<script src="{{ asset('/vendor/php-email-form/validate.js') }}"></script>
<script src="{{ asset('/vendor/swiper/swiper-bundle.min.js') }}"></script>
<script src="{{ asset('/vendor/waypoints/noframework.waypoints.js') }}"></script>

<!-- Template Main JS File -->

<script
    src="https://code.jquery.com/jquery-3.7.1.js"
    integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
    crossorigin="anonymous"
></script>

@yield('scripts')

</body>

</html>
