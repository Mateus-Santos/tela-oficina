<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Oficina SOS Mecânica</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- IMPORTANDO BOOTSTRAP -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
  <!-- Favicons -->
  <link href="{{ asset('img/favicon.png') }}" rel="icon">
  
  <link href="{{ asset('img/apple-touch-icon.png') }}" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Jost:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  
  <!-- Template Main CSS File -->
  @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/scss/app.scss'])
  

</head>

<body>
<!-- ======= Header ======= -->

<nav id="header" class="navbar bg-body-tertiary fixed-top">
  <div class="container-fluid">
    <h1 class="logo navbar-brand">Oficina SOS Mecânica</h1>
    <ul class="nav navbar-text">
    <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
    <li class="nav-item"><a class="nav-link" href="#about">Sobre</a></li>
    <li class="nav-item"><a class="nav-link" href="#team">Equipe</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('chat.index') }}">Dúvidas</a></li>
    @guest
    <li class="nav-item"><a class="btn btn-light" href="/login"> Entrar</a></li>
    <li class="nav-item"><a class="btn btn-warning" href="/register"> Cadastre-se</a></li>
    @endguest
    </ul>
    @auth
    <form action="/logout" method="POST">
      @csrf
    <a action="/logout" method="POST" class="btn btn-light" href="/logout"
    onclick="event.preventDefault();
    this.closest('form').submit();"> 
      Sair
    </a>
    </form>

    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="offcanvas offcanvas-end" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
      <div class="offcanvas-header">
        <!-- TÍTULO MENU -->
        <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Oficina SOS Mecânica</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
          <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
            <li class="nav-item">
                <a class="nav-link active" href="#">OFICINA</a>
            </li>
          <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Pessoas
              </a>
            <ul class="dropdown-menu">
                <li>
                  <a href="{{ route('pessoas.create') }}" type="button" class="dropdown-item">Cadastrar pessoas</a>
                </li>
                <li>
                  <a href="{{ route('pessoas.index') }}" type="button" class="dropdown-item">Listar Pessoas</a>
                </li>
            </ul>
          </li>
          <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
      </div>
    </div>
  </div>
  @endauth
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
  <script src="{{ asset('/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('/vendor/glightbox/js/glightbox.min.js') }}"></script>
  <script src="{{ asset('/vendor/isotope-layout/isotope.pkgd.min.js') }}"></script>
  <script src="{{ asset('/vendor/php-email-form/validate.js') }}"></script>
  <script src="{{ asset('/vendor/swiper/swiper-bundle.min.js') }}"></script>
  <script src="{{ asset('/vendor/waypoints/noframework.waypoints.js') }}"></script>
  <!-- Template Main JS File -->
  <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>

  <script>

    $.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    })
    $('#btn-submit').on('click', function(){
      $message = $('#message').val();
      $('#historic').append(`<div class="mb-2">
      <div class="box-my-message">
        <p class="my-message"> `+$message+` </p>
      </div>
      </div>`);

      $.ajax({
        type: 'post',
        url: '{{url('chat')}}',
        data: {
          'input': $message
        },
        success: function(data){
          $('#historic').append(`<div class="d-flex mb-2">

            <div class="box-response-message">
                <p class="response-message"> `+data+` </p>
            </div>

          </div>
          `)
          $message = $('#message').val('');
        }
      })
    })
  </script>
</body>
</html>