@extends('layouts.layout')

@vite(['resources/js/validateForm.js'])

@section('content')

<section class="container cadastro">

```
<h1>
    <i class="bi bi-gear"></i> CADASTRO DE MONTADORA
</h1>

<div class="campos">

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
        action="{{ route('montadoras.store') }}"
        method="POST"
    >

        @csrf

        <div class="row mb-3">

            <div class="col-md-6">

                <label
                    class="form-label"
                    for="nome"
                >
                    Montadora:*
                </label>

                <input
                    type="text"
                    class="form-control"
                    id="nome"
                    name="nome"
                    value="{{ old('nome') }}"
                    placeholder="Digite o nome da montadora"
                    maxlength="255"
                    required
                >

            </div>

        </div>

        <div class="col text-center mt-4">

            <button
                type="submit"
                class="btn btn-success"
            >
                <i class="bi bi-check-lg"></i>
                Cadastrar Montadora
            </button>

        </div>

    </form>

</div>
```

</section>

@endsection
