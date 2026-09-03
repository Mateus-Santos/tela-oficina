@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="EDITAR COMPRA"
        icon="bi-pencil-square"
    />

    <form
        method="POST"
        action="{{ route('compras.update', $compra) }}"
    >
        @csrf
        @method('PUT')

        @include('compra._form')

    </form>

</div>

<script>
    window.compraProdutos = @json(
        $produtos->map(fn ($produto) => [
            'id' => $produto->id,
            'nome' => $produto->nome,
            'codigo_fabricante' => $produto->codigo_fabricante,
            'preco_uni' => $produto->preco_uni,
        ])->values()
    );
</script>

@vite('resources/js/compra.js')

@endsection
