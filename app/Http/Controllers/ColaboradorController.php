<?php

namespace App\Http\Controllers;

use App\Http\Requests\Colaborador\StoreColaboradorRequest;
use App\Http\Requests\Colaborador\UpdateColaboradorRequest;
use App\Models\Colaborador;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ColaboradorController extends Controller
{
    public function index()
    {
        $colaboradores = Colaborador::query()
            ->with('user.pessoa')
            ->orderBy('id')
            ->get();

        return view(
            'colaborador.listarcolaborador',
            compact('colaboradores')
        );
    }

    public function create()
    {
        $users = User::query()
            ->with('pessoa')
            ->where(
                'perfil',
                '!=',
                User::PERFIL_ADMIN
            )
            ->whereDoesntHave('colaborador')
            ->orderBy('email')
            ->get();

        return view(
            'colaborador.cadastrocolaborador',
            compact('users')
        );
    }

    public function store(
        StoreColaboradorRequest $request
    ) {
        $validated = $request->validated();

        DB::transaction(
            function () use ($validated) {
                $user = User::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $validated['id_user']
                    );

                $colaborador = Colaborador::create([
                    'id_user' => $user->id,
                    'chave_pix' => $validated[
                        'chave_pix'
                    ],
                    'conta_banco' => $validated[
                        'conta_banco'
                    ],
                ]);

                $user->update([
                    'perfil' => User::PERFIL_COLABORADOR,
                ]);

                return $colaborador;
            }
        );

        return redirect()
            ->route('colaboradores.index');
    }

    public function edit(string $colaborador)
    {
        $colaborador = Colaborador::query()
            ->with('user.pessoa')
            ->findOrFail($colaborador);

        $users = User::query()
            ->with('pessoa')
            ->where(
                'perfil',
                '!=',
                User::PERFIL_ADMIN
            )
            ->where(
                function ($query) use ($colaborador) {
                    $query
                        ->whereDoesntHave('colaborador')
                        ->orWhere(
                            'id',
                            $colaborador->id_user
                        );
                }
            )
            ->orderBy('email')
            ->get();

        return view(
            'colaborador.editarcolaborador',
            compact(
                'colaborador',
                'users'
            )
        );
    }

    public function update(
        UpdateColaboradorRequest $request,
        string $colaborador
    ) {
        $validated = $request->validated();

        DB::transaction(
            function () use (
                $validated,
                $colaborador
            ) {
                $colaborador = Colaborador::query()
                    ->lockForUpdate()
                    ->findOrFail($colaborador);

                $usuarioAnteriorId =
                    $colaborador->id_user;

                $novoUsuario = User::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $validated['id_user']
                    );

                $colaborador->update([
                    'id_user' => $novoUsuario->id,
                    'chave_pix' => $validated[
                        'chave_pix'
                    ],
                    'conta_banco' => $validated[
                        'conta_banco'
                    ],
                ]);

                $novoUsuario->update([
                    'perfil' => User::PERFIL_COLABORADOR,
                ]);

                if (
                    $usuarioAnteriorId
                    !== $novoUsuario->id
                ) {
                    User::query()
                        ->where(
                            'id',
                            $usuarioAnteriorId
                        )
                        ->update([
                            'perfil' => User::PERFIL_CLIENTE,
                        ]);
                }
            }
        );

        return redirect()
            ->route('colaboradores.index');
    }

    public function destroy(string $colaborador)
    {
        DB::transaction(
            function () use ($colaborador) {
                $colaborador = Colaborador::query()
                    ->lockForUpdate()
                    ->findOrFail($colaborador);

                $user = User::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $colaborador->id_user
                    );

                $colaborador->delete();

                $user->update([
                    'perfil' => User::PERFIL_CLIENTE,
                ]);
            }
        );

        return redirect()
            ->route('colaboradores.index');
    }
}
