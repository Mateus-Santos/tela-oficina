@extends('layouts.layout')

@vite(['resources/js/validateForm.js'])

@push('p04-inputmask')
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.6/dist/inputmask.min.js"></script>
@endpush

@section('content')

<section class="container cadastro">

    <h1>
        <i class="bi bi-car-front"></i>
        CADASTRO DE VEÍCULO
    </h1>

    <form
        action="{{ route('veiculosclientes.store') }}"
        method="POST"
    >

        @csrf

        @include('veiculosclientes._form')

        <div class="text-center mt-4">

            <button
                type="submit"
                class="btn btn-success"
            >
                <i class="bi bi-check-lg me-1"></i>
                Cadastrar veículo
            </button>

        </div>

    </form>

</section>

@endsection
