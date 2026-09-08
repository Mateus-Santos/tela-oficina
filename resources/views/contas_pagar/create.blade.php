@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <x-list-header
        title="CADASTRAR CONTA A PAGAR"
        icon="bi-wallet2"
    />

    @if ($errors->any())
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Corrija os erros abaixo:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('contas-pagar.store') }}"
    >
        @csrf

        @include('contas_pagar._form')
    </form>
</div>
@endsection
