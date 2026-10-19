@extends('layouts.layout')

@section('content')
    <div class="container cadastro">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>LISTAR USUÁRIOS</h1>

            <a
                href="{{ route('users.create') }}"
                class="btn btn-success"
            >
                <i class="bi bi-plus-circle"></i>
                Cadastrar Usuário
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome Completo</th>
                        <th>Perfil</th>
                        <th>Data de Nascimento</th>
                        <th>E-mail</th>
                        <th>CPF</th>
                        <th>Telefone Principal</th>
                        <th>Ativado</th>
                        <th>Detalhes</th>
                        <th>Editar</th>
                        <th>Excluir</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                {{ $user->id }}
                            </td>

                            <td>
                                {{ $user->name ?? '-' }}
                            </td>

                            <td>
                                {{ ucfirst($user->perfil) }}
                            </td>

                            <td>
                                {{ $user->pessoa?->data_nascimento ?? '-' }}
                            </td>

                            <td>
                                {{ $user->email }}
                            </td>

                            <td>
                                {{ $user->pessoa?->cpf ?? '-' }}
                            </td>

                            <td>
                                {{ $user->pessoa?->telefone_1 ?? '-' }}
                            </td>

                            <td>
                                @livewire(
                                    'toggle-switch',
                                    [
                                        'model' => $user,
                                        'field' => 'status',
                                    ],
                                    key('user-status-'.$user->id)
                                )
                            </td>

                            <td>
                                <a
                                    href="{{ route('users.show', $user) }}"
                                    class="btn btn-success btn-sm"
                                >
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>

                            <td>
                                <a
                                    href="{{ route('users.edit', $user) }}"
                                    class="btn btn-info btn-sm"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            </td>

                            <td>
                                @if (! auth()->user()->is($user))
                                    <form
                                        action="{{ route('users.destroy', $user) }}"
                                        method="POST"
                                        onsubmit="return confirm('Tem certeza que deseja excluir este usuário?');"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-danger btn-sm"
                                        >
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                @else
                                    <span
                                        class="text-muted"
                                        title="Você não pode excluir sua própria conta."
                                    >
                                        —
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="11"
                                class="text-center"
                            >
                                Nenhum usuário cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
