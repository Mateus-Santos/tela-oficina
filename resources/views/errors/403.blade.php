@extends('layouts.layout')

@section('content')

<main id="main">

<section id="erro403" class="erro403">
<div class="container">
        <h1>403 - Acesso Negado</h1>
        <p>Você precisa estar logado para acessar esta página.</p>
        <a class="btn btn-warning" href="{{ route('login') }}">Ir para a página de login</a>
    </div>
</section>

@endsection