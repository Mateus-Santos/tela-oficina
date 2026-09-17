@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <h1 class="mb-4">
        GERENCIAR ITENS DA NOTA / O.S.
    </h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        action="{{ route('notasitem.update', $nota->id) }}"
        method="POST"
        id="form-os-itens"
    >
        @csrf
        @method('PUT')

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
