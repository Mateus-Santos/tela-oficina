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

@vite('resources/js/compra.js')

@endsection
