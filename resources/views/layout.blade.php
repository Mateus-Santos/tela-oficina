<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- IMPORTANDO BOOTSTRAP -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  
  <!-- Favicons -->
  <link href="{{ asset('img/favicon.png') }}" rel="icon">
  <link href="{{ asset('img/apple-touch-icon.png') }}" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Jost:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
  
  <!-- Template Main CSS File -->
  @vite(['resources/js/app.js', 'resources/scss/_app.scss', 'resources/css/app.css'])
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <title>Oficina SOS Mecânica</title>
</head>

<body>

<!-- ======= Header ======= -->

<nav id="header" class="navbar navbar-expand-lg fixed-top">
    <!-- Example single danger button -->
  <div class="container-fluid">
    <h1><a href="/">Oficina SOS Mecânica</a></h1>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
  </div>


  <div class="collapse navbar-collapse" id="navbarNavDropdown">

        <ul class="navbar-nav">
          <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="#about">Sobre</a></li>
          <li class="nav-item"><a class="nav-link" href="#team">Equipe</a></li>

          @guest
          <li class="nav-item"><a class="btn btn-success" href="/login">Entrar</a></li>
          <li class="nav-item"><a class="btn btn-warning" href="/register">Cadastre-se</a></li>
          @endguest

          @auth
          <li class="nav-item dropdown">
            <a type="button" class="btn btn-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            Pessoas
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="{{ route('pessoas.create') }}">Cadastrar pessoas</a></li>
              <li><a class="dropdown-item" href="{{ route('pessoas.index') }}">Listar Pessoas</a></li>
            </ul>
          </li>

          <li class="nav-item dropdown">
              <a class="btn btn-danger dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Endereços
              </a>
            <ul class="dropdown-menu">
              <li>
                <a href="{{ route('enderecos.create') }}" type="button" class="dropdown-item">Cadastrar Endereços</a>
              </li>
              <li>
                <a href="{{ route('enderecos.index') }}" type="button" class="dropdown-item">Listar Endereços</a>
              </li>
            </ul>
          </li>

          <li class="nav-item dropdown">
              <a class="btn btn-danger dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Peças
              </a>
            <ul class="dropdown-menu">
              <li>
                <a href="{{ route('pecas.create') }}" type="button" class="dropdown-item">Cadastrar Peças</a>
              </li>
              <li>
                <a href="{{ route('pecas.index') }}" type="button" class="dropdown-item">Listar Peças</a>
              </li>
            </ul>
          </li>

          <li class="nav-item dropdown">
              <a class="btn btn-danger dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Colaboradores
              </a>
            <ul class="dropdown-menu">
              <li>
                <a href="{{ route('colaboradors.create') }}" type="button" class="dropdown-item">Cadastrar Colaboradores</a>
              </li>
              <li>
                <a href="{{ route('colaboradors.index') }}" type="button" class="dropdown-item">Listar Colaboradores</a>
              </li>
            </ul>
          </li>

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
        &copy; Copyright <strong><span>Oficina SOS Mecânica</span></strong>. All Rights Reserved
      </div>
      <div class="credits">
        Template base<a href="https://bootstrapmade.com/"> BootstrapMade</a>
      </div>
   </footer>

    </div>
  <div id="preloader"></div>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><img src="{{ asset('svg/arrow-up.svg') }}"  alt="Logo" /></a>
  <!-- Vendor JS Files -->
  <script src="{{ asset('/vendor/aos/aos.js') }}"></script>
  <script src="{{ asset('/vendor/glightbox/js/glightbox.min.js') }}"></script>
  <script src="{{ asset('/vendor/isotope-layout/isotope.pkgd.min.js') }}"></script>
  <script src="{{ asset('/vendor/php-email-form/validate.js') }}"></script>
  <script src="{{ asset('/vendor/swiper/swiper-bundle.min.js') }}"></script>
  <script src="{{ asset('/vendor/waypoints/noframework.waypoints.js') }}"></script>
  <!-- Template Main JS File -->
  <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>  
</body>
</html>