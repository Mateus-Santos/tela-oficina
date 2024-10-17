@extends('layout')

@section('content')

<main id="main">

<section id="erro404" class="erro404">
  <div class="container">
    <div class="section-title">
      <h2>Página não encontrada!</h2>
    </div>  
        <p>As vezes essas coisas acontecem, mas não se preocupe. Clique no botão abaixo para retornar a página inicial.</p>
        <a class="btn btn-warning" href="/home">Página Inicial</a>
    </div>
  </div>
</section>

@endsection