@extends('layouts.layout')

@section('content')
    <div class="container cadastro">
        <div
            class="d-flex justify-content-between align-items-center mb-3"
        >
            <h1>LISTAR COLABORADORES</h1>

            <a
                href="{{ route('colaboradores.create') }}"
                class="btn btn-success"
            >
                Cadastrar colaborador
            </a>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Nome</th>
                    <th scope="col">Chave Pix</th>
                    <th scope="col">Conta Banco</th>
                    <th scope="col">E-mail</th>
                    <th scope="col">Editar</th>
                    <th scope="col">Excluir</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($colaboradores as $colaborador)
                    <tr>
                        <td>
                            {{ $colaborador->id }}
                        </td>

                        <td>
                            {{ $colaborador->user->name }}
                        </td>

                        <td>
                            {{ $colaborador->chave_pix }}
                        </td>

                        <td>
                            {{ $colaborador->conta_banco }}
                        </td>

                        <td>
                            {{ $colaborador->user->email }}
                        </td>

                        <td>
                            <a
                                href="{{ route(
                                    'colaboradores.edit',
                                    $colaborador->id
                                ) }}"
                                class="btn btn-info"
                            >
                                <i class="bi bi-pencil-square"></i>
                                Editar
                            </a>
                        </td>

                        <td>
                            <form
                                action="{{ route(
                                    'colaboradores.destroy',
                                    $colaborador->id
                                ) }}"
                                method="POST"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="btn btn-danger delete-btn"
                                >
                                    <i class="bi bi-trash3"></i>
                                    Deletar
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td
                            colspan="7"
                            class="text-center"
                        >
                            Nenhum colaborador cadastrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
