<?php

namespace App\Http\Controllers;

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
        $users = User::with('pessoa')->get();

        return view(
            'user.listaruser',
            compact('users')
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'unique:users,email',
            ],

            'data_nascimento' => [
                'required',
                'date',
            ],

            'telefone_1' => [
                'required',
                'string',
            ],
        ]);

        DB::transaction(
            function () use ($request) {
                $pessoa = Pessoa::create([
                    'nome' => $request->input('nome'),

                    'data_nascimento' => $request->input(
                        'data_nascimento'
                    ),

                    'telefone_1' => preg_replace(
                        '/\D/',
                        '',
                        $request->input(
                            'telefone_1'
                        )
                    ),

                    'telefone_2' => preg_replace(
                        '/\D/',
                        '',
                        $request->input(
                            'telefone_2'
                        ) ?? ''
                    ) ?: null,

                    'cpf' => preg_replace(
                        '/\D/',
                        '',
                        $request->input(
                            'cpf'
                        ) ?? ''
                    ) ?: null,

                    'rg' => $request->input('rg'),
                ]);

                User::create([
                    'pessoa_id' => $pessoa->id,

                    'email' => $request->input(
                        'email'
                    ),

                    'perfil' => User::PERFIL_CLIENTE,

                    'password' => Hash::make(Str::random(64)),
                ]);
            }
        );

        return redirect()
            ->route('users.index');
    }

    public function show(string $id)
    {
        $user = User::with([
            'pessoa.enderecos',
        ])->findOrFail($id);

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

    public function update(
        Request $request,
        string $id
    ) {
        $user = User::findOrFail($id);

        $validatedUser =
            $request->validate([
                'email' => [
                    'required',
                    'email',
                    'unique:users,email,'.$id,
                ],
            ]);

        $validatedPessoa =
            $request->validate([
                'nome' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'cpf' => [
                    'nullable',
                    'string',
                    'unique:pessoas,cpf,'
                        .$user->pessoa_id,
                ],

                'rg' => [
                    'nullable',
                    'string',
                ],

                'telefone_1' => [
                    'required',
                    'string',
                ],

                'telefone_2' => [
                    'nullable',
                    'string',
                ],
            ]);

        DB::transaction(
            function () use (
                $user,
                $validatedUser,
                $validatedPessoa
            ) {
                $user->update(
                    $validatedUser
                );

                $user->pessoa->update([
                    'nome' => $validatedPessoa['nome'],

                    'cpf' => preg_replace(
                        '/\D/',
                        '',
                        $validatedPessoa['cpf']
                            ?? ''
                    ) ?: null,

                    'rg' => $validatedPessoa['rg']
                        ?? null,

                    'telefone_1' => preg_replace(
                        '/\D/',
                        '',
                        $validatedPessoa[
                            'telefone_1'
                        ]
                    ),

                    'telefone_2' => preg_replace(
                        '/\D/',
                        '',
                        $validatedPessoa[
                            'telefone_2'
                        ] ?? ''
                    ) ?: null,
                ]);
            }
        );

        return redirect()->route(
            $user->isCliente()
                ? 'perfil'
                : 'users.index'
        );
    }
}
