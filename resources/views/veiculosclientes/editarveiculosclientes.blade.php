@extends('layouts.layout')

@vite(['resources/js/validateForm.js'])

@push('p04-inputmask')
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.6/dist/inputmask.min.js"></script>
@endpush

@section('content')

<section class="container cadastro">

    <h1>
        <i class="bi bi-pencil-square"></i>
        EDITAR VEÍCULO
    </h1>

    <form
        action="{{ route(
            'veiculosclientes.update',
            $veiculoscliente->id
        ) }}"
        method="POST"
    >

        @csrf
        @method('PATCH')

        @include('veiculosclientes._form')

        <div class="text-center mt-4">

            <button
                type="submit"
                class="btn btn-info"
            >
                <i class="bi bi-pencil-square me-1"></i>
                Salvar alterações
            </button>

            <a href="{{ route('veiculosclientes.index') }}" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                Voltar aos veículos
            </a>

        </div>

    </form>

</section>

@endsection
