@extends('layouts.layout')

@section('content')

<div class="container cadastro feux-compra feux-moderno">

    <x-list-header
        title="CADASTRAR COMPRA"
        icon="bi-cart-plus"
    />

    <form id="compra-form"
        method="POST"
        action="{{ route('compras.store') }}"
        enctype="multipart/form-data"
    >
        @csrf

        @include('compra._form')
    </form>

</div>

@vite('resources/js/compra.js')

@endsection
