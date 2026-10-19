@extends('layouts.layout')

@section('content')
    <div class="container cadastro">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>DETALHES DO USUÁRIO</h1>

            <div>
                <a
                    href="{{ route('users.index') }}"
                    class="btn btn-secondary"
                >
                    Voltar
                </a>

                <a
                    href="{{ route('users.edit', $user) }}"
                    class="btn btn-info"
                >
                    <i class="bi bi-pencil-square"></i>
                    Editar
                </a>
            </div>
        </div>

        <div class="campos">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label
                        class="form-label"
                        for="nome"
                    >
                        Nome Completo
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="nome"
                        value="{{ $user->pessoa?->nome }}"
                        disabled
                    >
                </div>

                <div class="col-md-6">
                    <label
                        class="form-label"
                        for="email"
                    >
                        E-mail
                    </label>

                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        value="{{ $user->email }}"
                        disabled
                    >
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label
                        class="form-label"
                        for="perfil"
                    >
                        Perfil
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="perfil"
                        value="{{ ucfirst($user->perfil) }}"
                        disabled
                    >
                </div>

                <div class="col-md-3">
                    <label
                        class="form-label"
                        for="rg"
                    >
                        RG
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="rg"
                        value="{{ $user->pessoa?->rg }}"
                        disabled
                    >
                </div>

                <div class="col-md-3">
                    <label
                        class="form-label"
                        for="cpf"
                    >
                        CPF
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="cpf"
                        value="{{ $user->pessoa?->cpf }}"
                        disabled
                    >
                </div>

                <div class="col-md-3">
                    <label
                        class="form-label"
                        for="data_nascimento"
                    >
                        Data de Nascimento
                    </label>

                    <input
                        type="date"
                        class="form-control"
                        id="data_nascimento"
                        value="{{ $user->pessoa?->data_nascimento }}"
                        disabled
                    >
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label
                        class="form-label"
                        for="telefone_1"
                    >
                        Telefone Principal
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="telefone_1"
                        value="{{ $user->pessoa?->telefone_1 }}"
                        disabled
                    >
                </div>

                <div class="col-md-6">
                    <label
                        class="form-label"
                        for="telefone_2"
                    >
                        Telefone Secundário
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="telefone_2"
                        value="{{ $user->pessoa?->telefone_2 }}"
                        disabled
                    >
                </div>
            </div>
        </div>

        <hr class="my-4">

        @if ($enderecos->isEmpty())
            <div
                class="alert alert-warning d-flex justify-content-between align-items-center"
            >
                <span>
                    Usuário não possui endereços cadastrados.
                </span>

                <a
                    class="btn btn-success"
                    href="/endereco/create/{{ $user->id }}"
                >
                    Cadastrar Novo Endereço
                </a>
            </div>
        @else
            <div
                class="d-flex justify-content-between align-items-center mb-3"
            >
                <h3>ENDEREÇOS CADASTRADOS</h3>

                <a
                    class="btn btn-success"
                    href="/endereco/create/{{ $user->id }}"
                >
                    <i class="bi bi-plus-circle"></i>
                    Cadastrar Novo Endereço
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>CEP</th>
                            <th>Cidade</th>
                            <th>Bairro</th>
                            <th>Estado</th>
                            <th>Rua</th>
                            <th>Número</th>
                            <th>Ponto Referência</th>
                            <th>Editar</th>
                            <th>Excluir</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($enderecos as $endereco)
                            <tr>
                                <td>
                                    {{ $endereco->id }}
                                </td>

                                <td>{{ $endereco->cep }}</td>
                                <td>{{ $endereco->cidade }}</td>
                                <td>{{ $endereco->bairro }}</td>
                                <td>{{ $endereco->estado }}</td>
                                <td>{{ $endereco->rua }}</td>
                                <td>{{ $endereco->numero }}</td>

                                <td>
                                    {{ $endereco->ponto_referencia ?? '-' }}
                                </td>

                                <td>
                                    <a
                                        href="{{ route(
                                            'enderecos.edit',
                                            $endereco
                                        ) }}"
                                        class="btn btn-info btn-sm"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                        Editar
                                    </a>
                                </td>

                                <td>
                                    <form
                                        action="{{ route(
                                            'enderecos.destroy',
                                            $endereco
                                        ) }}"
                                        method="POST"
                                        onsubmit="return confirm('Tem certeza que deseja excluir este endereço?');"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-danger btn-sm"
                                        >
                                            <i class="bi bi-trash3"></i>
                                            Deletar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
