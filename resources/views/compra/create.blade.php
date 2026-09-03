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
    >
        @csrf

        @include('compra._form')

    </form>

</div>

<script>
    window.compraProdutos = {{ \Illuminate\Support\Js::from(
        $produtos->map(fn ($produto) => [
            'id' => $produto->id,
            'nome' => $produto->nome,
            'codigo_fabricante' => $produto->codigo_fabricante,
            'preco_uni' => $produto->preco_uni,
        ])->values()
    ) }};
</script>

@vite('resources/js/compra.js')

@endsection
