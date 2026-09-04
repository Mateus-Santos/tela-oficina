@extends('layouts.layout')

@section('content')

<div class="container cadastro">

    <x-list-header
        title="CADASTRAR FORNECEDOR"
        icon="bi-building-add"
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

    <form
        method="POST"
        action="{{ route('fornecedores.store') }}"
    >
        @csrf

        @include('fornecedor._form')
    </form>

</div>

@endsection
