@extends('layout')

@section('content')

<main id="main">

<section id="erro404" class="erro404">
<div class="container">
        <h1>403 - Acesso Negado</h1>
        <p>Você precisa estar logado para acessar esta página.</p>
        <a class="btn btn-warning" href="{{ route('login') }}">Ir para a página de login</a>
    </div>
</section>

@endsection