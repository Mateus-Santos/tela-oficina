@extends('layouts.layout')

@section('content')
    <section class="container cadastro">
        <h1>CADASTRO DE COLABORADOR</h1>

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
            action="{{ route('colaboradores.store') }}"
            method="POST"
            class="row g-3"
        >
            @csrf

            <div class="campos">
                <div class="row">
                    <div class="col-md-4">
                        <label
                            class="form-label"
                            for="id_user"
                        >
                            Usuário:*
                        </label>

                        <select
                            id="id_user"
                            class="form-control"
                            name="id_user"
                            required
                        >
                            <option value="">
                                Escolher...
                            </option>

                            @foreach ($users as $user)
                                <option
                                    value="{{ $user->id }}"
                                    @selected(
                                        old('id_user') == $user->id
                                    )
                                >
                                    {{ $user->name }}
                                    — {{ $user->email }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label
                            class="form-label"
                            for="chave_pix"
                        >
                            Chave Pix:*
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="chave_pix"
                            name="chave_pix"
                            value="{{ old('chave_pix') }}"
                            maxlength="100"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label
                            class="form-label"
                            for="conta_banco"
                        >
                            Conta bancária:*
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="conta_banco"
                            name="conta_banco"
                            value="{{ old('conta_banco') }}"
                            maxlength="30"
                            required
                        >
                    </div>
                </div>

                <div class="col text-center mt-4">
                    <button
                        type="submit"
                        class="btn btn-success"
                    >
                        Cadastrar
                    </button>
                </div>
            </div>
        </form>
    </section>
@endsection
