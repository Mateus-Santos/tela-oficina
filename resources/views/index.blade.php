@extends('layout')

@section('content')

  <!-- ======= Hero Section ======= -->
  <section id="hero" class="d-flex align-items-center">

    <div class="container">
      <div class="row">
        <div class="col-lg-6 d-flex flex-column justify-content-center pt-4 pt-lg-0 order-2 order-lg-1" data-aos="fade-up" data-aos-delay="200">
          <h1>Melhor oficina para o seu automóvel</h1>
          <h2>Mecânico profissional e competente para solucionar o problema do seu veículo.</h2>
          <div class="d-flex justify-content-center justify-content-lg-start">
            <a href=" https://wa.me/557581086649" class="btn-get-started scrollto">Entre em contato! <img src="svg/whatsapp.svg" alt="Logo" /></a> 
          </div>
        </div>
        <div class="col-lg-6 order-1 order-lg-2 hero-img" data-aos="zoom-in" data-aos-delay="200">
          <img href='https://br.freepik.com/vetores/carro' src="img/ilustracao.jpg" class="img-fluid animated">
        </div>
      </div>
    </div>

  </section>

  <main id="main">

    <!-- ======= About Us Section ======= -->
    <section id="about" class="about">
      <div class="container" data-aos="fade-up">

        <div class="section-title">
          <h2>Sobre</h2>
        </div>

        <div class="row content">
          <div class="col-lg-6">
            <p>
              A Oficina SOS Mecânica possui como objetivo o melhor atendimento ao cliente e solução de problemas na área mecânica do 
              automóvel com prestação de serviço e vendas de peças de qualidade.
            </p>
            <ul>
              <li><img src="svg/check2-all.svg" alt="Logo" />  Manutenção e revisão do automóvel.</li>
              <li><img src="svg/check2-all.svg" alt="Logo" />  Venda de peças de qualidade.</li>
              <li><img src="svg/check2-all.svg" alt="Logo" />  Garantia do serviço por 30 dias.</li>
            </ul>
          </div>
          <div class="col-lg-6 pt-4 pt-lg-0">
            <p>
              Endereço: Rua Vasco da Gama, 160 - Cruzeiro, Feira de Santana - BA, 44022-012.
              Clicando no botão Localização abrira um link direto para o Google Maps.
            </p>
            <a href="https://goo.gl/maps/AGLPatm3h8J4u2Pv9" target="_blank" class="btn-learn-more">Localização Google Maps</a>
          </div>
        </div>
      </div>
    </section><!-- End About Us Section -->

    <!-- ======= Team Section ======= -->
    <section id="team" class="team section-bg">
      <div class="container" data-aos="fade-up">

        <div class="section-title">
          <h2>Team</h2>
          <p>Equipe de trabalho da oficina.</p>
        </div>

        <div class="row">
        
          <div class="col-lg-6">
            <div class="member d-flex align-items-start" data-aos="zoom-in" data-aos-delay="100">
              <div class="pic"><img src="img/team/team-1.jpeg" class="img-fluid" alt=""></div>
              <div class="member-info">
                <h4>Jaime Pinto de Jesus</h4>
                <span>Mecânico Chefe</span>
                <p>Informe o problema ao mecânico e logo ele será solucionado.</p>
                <div class="social">
                  <a href=""><img src="svg/facebook.svg" alt="Logo" /></a>
                  <a href=""><img src="svg/instagram.svg" alt="Logo" /></i></a>
                </div>
              </div>
            </div>
          </div>

      </div>
    </section><!-- End Team Section -->

  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <footer id="footer">

    <div class="footer-top">
      <div class="container">
        <div class="row">

          <div class="col-lg-3 col-md-6 footer-contact">
            <h3>Desenvolvedor do site</h3>
            <p>
              Mateus Santos <br>
              <br>
              <strong>Telefone:</strong> +55 75 98702-8960<br>
              <strong>Email:</strong> mateus11_santos@hotmail.com<br>
            </p>
          </div>

          <div class="col-lg-3 col-md-6 footer-links">
            <h4>Links</h4>
            <ul>
              <li><img src="svg/chevron-double-right.svg" alt="Logo" /><a href="#"> Home</a></li>
              <li><img src="svg/chevron-double-right.svg" alt="Logo" /><a href="#about"> Sobre</a></li>
              <li><img src="svg/chevron-double-right.svg" alt="Logo" /><a href="#team"> Equipe</a></li>
            </ul>
          </div>

          <div class="col-lg-3 col-md-6 footer-links">
            <h4>Outros Serviços</h4>
            <ul>
              <li><img src="svg/chevron-double-right.svg" alt="Logo" /> <a href="https://www.youtube.com/watch?v=zFOWSusSr_Q&ab_channel=MateusSantos">Loja virtual</a></li>
            </ul>
          </div>

          <div class="col-lg-3 col-md-6 footer-links">
            <h4>Redes sociais</h4>
            <p class="redes"><img src="svg/chevron-double-right.svg" alt="Logo" /> Outras redes sociais do desenvolvedor.</p>
            <div class="social-links mt-3">
              <a href="https://www.facebook.com/mateus.santos.984786" class="facebook"><img src="svg/facebook.svg" alt="Logo" /></a>
              <a href="https://www.instagram.com/teeusantos20/" class="instagram"><img src="svg/instagram.svg" alt="Logo" /></a>
              <a href="https://www.linkedin.com/in/mateus-santos-095a53210/" class="linkedin"><img src="svg/linkedin.svg" alt="Logo" /></a>
            </div>
          </div>

        </div>
      </div>
    </div>
  </footer><!-- End Footer -->
  @endsection