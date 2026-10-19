<?php

namespace App\Http\Controllers;

use App\Http\Requests\Usuario\StoreUserRequest;
use App\Http\Requests\Usuario\UpdatePerfilRequest;
use App\Http\Requests\Usuario\UpdateUserRequest;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->with('pessoa')
            ->orderBy('id')
            ->get();

        return view(
            'user.listaruser',
            compact('users')
        );
    }

    public function create()
    {
        return view(
            'user.cadastrouser'
        );
    }

    public function store(
        StoreUserRequest $request
    ) {
        $validated = $request->validated();

        DB::transaction(
            function () use ($validated) {
                $pessoa = Pessoa::create([
                    'nome' => $validated['nome'],

                    'cpf' => $validated['cpf']
                        ?? null,

                    'rg' => $validated['rg']
                        ?? null,

                    'data_nascimento' => $validated[
                            'data_nascimento'
                        ] ?? null,

                    'telefone_1' => $validated[
                            'telefone_1'
                        ] ?? null,

                    'telefone_2' => $validated[
                            'telefone_2'
                        ] ?? null,
                ]);

                User::create([
                    'pessoa_id' => $pessoa->id,

                    'email' => $validated[
                        'email'
                    ],

                    'perfil' => User::PERFIL_CLIENTE,

                    'password' => Hash::make(
                        Str::random(64)
                    ),
                ]);
            }
        );

        return redirect()
            ->route('users.index')
            ->with(
                'success',
                'Usuário cadastrado com sucesso.'
            );
    }

    public function show(User $user)
    {
        $user->load([
            'pessoa.enderecos',
        ]);

        $enderecos =
            $user->pessoa?->enderecos
            ?? collect();

        return view(
            'user.showuser',
            compact(
                'user',
                'enderecos'
            )
        );
    }

    public function edit(User $user)
    {
        $user->load('pessoa');

        return view(
            'user.editaruser',
            compact('user')
        );
    }

    public function update(
        UpdateUserRequest $request,
        User $user
    ) {
        $validated =
            $request->validated();

        DB::transaction(
            function () use (
                $user,
                $validated
            ) {
                $user->update([
                    'email' => $validated['email'],
                ]);

                $user->pessoa->update([
                    'nome' => $validated['nome'],

                    'cpf' => $validated['cpf']
                        ?? null,

                    'rg' => $validated['rg']
                        ?? null,

                    'data_nascimento' => $validated[
                            'data_nascimento'
                        ] ?? null,

                    'telefone_1' => $validated[
                            'telefone_1'
                        ] ?? null,

                    'telefone_2' => $validated[
                            'telefone_2'
                        ] ?? null,
                ]);
            }
        );

        return redirect()
            ->route('users.index')
            ->with(
                'success',
                'Usuário atualizado com sucesso.'
            );
    }

    public function destroy(
        Request $request,
        User $user
    ) {
        abort_if(
            $request->user()->is($user),
            403,
            'Você não pode excluir sua própria conta.'
        );

        DB::transaction(
            function () use ($user) {
                /*
                 * Colaborador depende diretamente
                 * da conta de usuário.
                 */
                $user->colaborador?->delete();

                /*
                 * Remove apenas a conta de acesso.
                 * Pessoa e Cliente permanecem.
                 */
                $user->delete();
            }
        );

        return redirect()
            ->route('users.index')
            ->with(
                'success',
                'Usuário excluído com sucesso.'
            );
    }

    public function perfil(
        Request $request
    ) {
        $user = $request
            ->user()
            ->load('pessoa');

        return view(
            'user.perfil',
            compact('user')
        );
    }

    public function atualizarPerfil(
        UpdatePerfilRequest $request
    ) {
        $user = $request->user();

        $validated =
            $request->validated();

        DB::transaction(
            function () use (
                $user,
                $validated
            ) {
                $user->update([
                    'email' => $validated['email'],
                ]);

                $user->pessoa->update([
                    'nome' => $validated['nome'],

                    'cpf' => $validated['cpf']
                        ?? null,

                    'rg' => $validated['rg']
                        ?? null,

                    'data_nascimento' => $validated[
                            'data_nascimento'
                        ] ?? null,

                    'telefone_1' => $validated[
                            'telefone_1'
                        ] ?? null,

                    'telefone_2' => $validated[
                            'telefone_2'
                        ] ?? null,
                ]);
            }
        );

        return redirect()
            ->route('perfil')
            ->with(
                'success',
                'Perfil atualizado com sucesso.'
            );
    }
}
