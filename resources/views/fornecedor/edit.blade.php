@extends('layouts.layout')

@section('content')

<div class="container cadastro feux-moderno">

    <x-list-header
        title="EDITAR FORNECEDOR"
        icon="bi-pencil-square"
    />

    @if ($errors->any())
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Verifique os dados informados:</strong>

            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form data-feux-form="true"
        method="POST"
        action="{{ route('fornecedores.update', $fornecedor) }}"
    >
        @csrf
        @method('PUT')

        @include('fornecedor._form')
    </form>

</div>

@endsection
