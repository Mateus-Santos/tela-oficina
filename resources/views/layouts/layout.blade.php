<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- IMPORTANDO BOOTSTRAP -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Favicons -->
  <link href="{{ asset('img/favicon.png') }}" rel="icon">
  <link href="{{ asset('img/apple-touch-icon.png') }}" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Jost:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Template Main CSS File -->
  <script src="{{ asset('/vendor/waypoints/noframework.waypoints.js') }}"></script>
  @vite(['resources/js/app.js', 'resources/scss/_app.scss'])
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <title>Oficina SOS Mecânica {{ env('APP_VERSION') }}</title>

  <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.6/dist/inputmask.min.js"></script>

</head>

<body>

  <!-- ======= Header ======= -->

  <nav id="header" class="navbar navbar-expand-lg fixed-top">
    <!-- Example single danger button -->
    <div class="container-fluid">
      <h1><a href="/">Oficina SOS Mecânica {{ env('APP_VERSION') }}</a></h1>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
    </div>


    <div class="collapse navbar-collapse" id="navbarNavDropdown">

      <ul class="navbar-nav">
        @guest
        <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="#about">Sobre</a></li>
        <li class="nav-item"><a class="nav-link" href="#team">Equipe</a></li>


        <li class="nav-item"><a class="btn btn-success" href="/login"><i class="bi bi-box-arrow-in-right"></i> Entrar</a></li>
        <li class="nav-item"><a class="btn btn-warning" href="/register"><i class="bi bi-person-plus"></i> Cadastre-se</a></li>
        @endguest

        @auth

        @if(auth()->user() && auth()->user()->permitions === 2)
            <li class="nav-item dropdown">
                <a class="btn btn-warning dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-car-front"></i>
                    Veículos
                </a>
                <ul class="dropdown-menu">
                    <li>
                        <a href="{{ route('veiculos.index') }}" type="button" class="dropdown-item">Listar Veículos</a>
                    </li>
                    <li>
                        <a href="{{ route('veiculos.create') }}" type="button" class="dropdown-item">Cadastrar Veículo</a>
                    </li>
                </ul>
            </li>
        <li class="nav-item dropdown">
          <a class="btn btn-warning" href="{{ route('contratoservico.index') }}" aria-expanded="false">
            <i class="bi bi-wrench-adjustable-circle"></i>
            Históricos
          </a>
        </li>

        <li class="nav-item dropdown">
          <a class="btn btn-warning" href="/perfil" aria-expanded="false">
            <i class="bi bi-person-square"></i>
            {{auth()->user()->name}}
          </a>
        </li>

        @endif
        @if(auth()->user() && auth()->user()->permitions === 1)
        <!-- Colaborator -->
        <li class="nav-item dropdown">
          <a class="btn btn-warning dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-lines-fill"></i>
            Usuários
          </a>
          <ul class="dropdown-menu">
            <li>
              <a href="{{ route('users.index') }}" type="button" class="dropdown-item">Listar todos os Usuários</a>
            </li>
            <li>
              <a href="{{ route('colaboradors.create') }}" type="button" class="dropdown-item">Cadastrar Colaboradores</a>
            </li>
            <li>
              <a href="{{ route('colaboradors.index') }}" type="button" class="dropdown-item">Listar Colaboradores</a>
            </li>
            <li>
              <a href="{{ route('clientes.index') }}" type="button" class="dropdown-item">Listar Clientes</a>
            </li>
            <li>
              <a href="{{ route('colaboradors.index') }}" type="button" class="dropdown-item">Listar Colaboradores</a>
            </li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="btn btn-warning dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-car-front"></i>
            Veículos
          </a>
          <ul class="dropdown-menu">
            <li>
              <a href="{{ route('veiculos.index') }}" type="button" class="dropdown-item">Listar Veículos</a>
            </li>
            <li>
              <a href="{{ route('veiculos.create') }}" type="button" class="dropdown-item">Cadastrar Veículo</a>
            </li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="btn btn-warning dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-wrench-adjustable-circle"></i>
            Históricos de Serviços
          </a>
          <ul class="dropdown-menu">
            <li>
              <a href="{{ route('contratoservico.create') }}" type="button" class="dropdown-item">Cadastrar Histórico</a>
            </li>
            <li>
              <a href="{{ route('contratoservico.index') }}" type="button" class="dropdown-item">Listar Históricos</a>
            </li>
            <li>
              <a href="{{ route('manutencoes.create') }}" type="button" class="dropdown-item">Cadastrar Manutenções</a>
            </li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="btn btn-warning dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-bag-fill"></i>
            Loja
          </a>
          <ul class="dropdown-menu">
            <li>
              <a href="{{ route('produtos.create') }}" type="button" class="dropdown-item">Cadastrar Produto</a>
            </li>
            <li>
              <a href="{{ route('produtos.index') }}" type="button" class="dropdown-item">Listar Produtos</a>
            </li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a type="button" class="btn btn-warning dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-cart-fill"></i>
            Vendas
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ route('produtovendas.create') }}">Cadastrar Vendas</a></li>
            <li><a class="dropdown-item" href="{{ route('produtovendas.index') }}">Listar Vendas</a></li>
          </ul>
        </li>
        @endif
        <li class="nav-item">
          <form action="/logout" method="POST">
            @csrf
            <a action="/logout" method="POST" class="btn btn-light" href="/logout"
              onclick="event.preventDefault();
              this.closest('form').submit();">
              Sair
            </a>
          </form>
        </li>
        @endauth
      </ul>
    </div>
  </nav>

  @yield('content')

  <footer id="footer">
    <div class="container footer-bottom clearfix">
      <div class="copyright">
        &copy; Copyright 2024 <strong><span>Oficina SOS Mecânica</span></strong>. All Rights Reserved
      </div>
  </footer>

  </div>
  <div id="preloader"></div>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><img src="{{ asset('svg/arrow-up.svg') }}" alt="Logo" /></a>
  <script src="{{ asset('/vendor/aos/aos.js') }}"></script>
  <script src="{{ asset('/vendor/glightbox/js/glightbox.min.js') }}"></script>
  <script src="{{ asset('/vendor/isotope-layout/isotope.pkgd.min.js') }}"></script>
  <script src="{{ asset('/vendor/php-email-form/validate.js') }}"></script>
  <script src="{{ asset('/vendor/swiper/swiper-bundle.min.js') }}"></script>
  <script src="{{ asset('/vendor/waypoints/noframework.waypoints.js') }}"></script>
  <!-- Template Main JS File -->
  <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
  @yield('scripts')
</body>

</html>
