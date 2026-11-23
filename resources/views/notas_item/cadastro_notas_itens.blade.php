@extends('layouts.layout')

@section('content')

<div class="container cadastro feux-notas-lote02 feux-notas-formulario feux-moderno">

    <header class="feux-notas-cabecalho">
        <div class="feux-notas-cabecalho__texto">
            <p class="feux-notas-eyebrow mb-1">Gestão de notas</p>
            <h1 class="feux-notas-cabecalho__titulo">Nova nota</h1>
            <p class="feux-notas-cabecalho__descricao">Defina o cliente, inclua os itens e confira os valores antes de salvar.</p>
        </div>
        <a class="btn btn-outline-dark" href="{{ route('notas.index') }}">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Voltar às notas
        </a>
    </header>
    <nav class="feux-notas-passos" aria-label="Navegação do formulário de notas">
        <a href="#feux-notas-identificacao"><span>01</span> Identificação</a>
        <a href="#feux-notas-resumo"><span>02</span> Resumo</a>
        <a href="#feux-notas-itens"><span>03</span> Itens <small id="feux-notas-contador" aria-live="polite"></small></a>
        <a href="#feux-notas-descontos"><span>04</span> Descontos</a>
        <a href="#feux-notas-salvar"><span>05</span> Salvar</a>
    </nav>

    <form data-feux-form="true"
        action="{{ route('notasitem.store') }}"
        method="POST"
        id="form-os-itens"
    >

        @csrf

        @include('notas_item._form')

    </form>

</div>

{{-- Modais ficam fora do .cadastro --}}
@include('notas_item.modal._modal_adicionar_item')
@include('notas_item.modal._modal_descontos')

@endsection


@section('scripts')

    @vite([
        'resources/js/notaitem/form.js',
        'resources/js/notaitem/itens.js',
        'resources/js/notaitem/descontos.js',
        'resources/js/notaitem/feux-notas-lote02.js',
        'resources/js/notaitem/feux-notas-lote04.js',
        'resources/js/notaitem/feux-notas-lote05.js'
    ])

@endsection
