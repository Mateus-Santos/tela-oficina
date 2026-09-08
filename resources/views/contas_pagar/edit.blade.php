@extends('layouts.layout')

@section('content')
<div class="container cadastro">
    <x-list-header
        title="EDITAR CONTA A PAGAR"
        icon="bi-pencil-square"
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
        action="{{ route('contas-pagar.update', $conta) }}"
    >
        @csrf
        @method('PUT')

        @include('contas_pagar._form')
    </form>
</div>
@endsection
