@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <x-list-header
        title="CADASTRAR COMPRA"
        icon="bi-cart-plus"
    />

    <form
        method="POST"
        action="{{ route('compras.store') }}"
        enctype="multipart/form-data"
    >
        @csrf
        @include('compra._form')
    </form>
</div>

@php
    $compraProdutos = $produtos->map(function ($produto) {
        return [
            'id' => $produto->id,
            'nome' => $produto->nome,
            'codigo_fabricante' => $produto->codigo_fabricante,
            'preco_uni' => $produto->preco_uni,
        ];
    })->values()->all();
@endphp

<script>
    window.compraProdutos = {{ Illuminate\Support\Js::from($compraProdutos) }};
</script>

@vite('resources/js/compra.js')
@endsection
