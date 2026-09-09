@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <h1 class="mb-4">
        GERENCIAR ITENS DA NOTA / O.S.
    </h1>

    <form
        action="{{ route('notasitem.store') }}"
        method="POST"
        id="form-os-itens"
    >

        @csrf

        @include('notas_item._form')

    </form>

</div>

{{-- Modais ficam fora do .cadastro --}}
@include('notas_item._modal_adicionar_item')
@include('notas_item._modal_descontos')

@endsection


@section('scripts')

    @vite([
        'resources/js/notaitem/form.js',
        'resources/js/notaitem/itens.js',
        'resources/js/notaitem/descontos.js'
    ])

@endsection
