<?php

namespace App\Actions\Clientes;

use App\Models\Cliente;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CriarClienteCompleto
{
    public function execute(array $dados, bool $criarUsuario = false): ?string
    {
        return DB::transaction(function () use ($dados, $criarUsuario): ?string {
            $pessoa = Pessoa::create([
                'nome' => $dados['nome'],
                'cpf' => $dados['cpf'] ?? null,
                'rg' => $dados['rg'] ?? null,
                'data_nascimento' => $dados['data_nascimento'] ?? null,
                'telefone_1' => $dados['telefone_1'] ?? null,
                'telefone_2' => $dados['telefone_2'] ?? null,
            ]);

            Cliente::create([
                'pessoa_id' => $pessoa->id,
                'pontos' => $dados['pontos'] ?? 0,
            ]);

            if (! $criarUsuario || empty($dados['email'])) {
                return null;
            }

            // Fluxo atual preservado ate a revisao de autenticacao.
            $senha = Str::random(8);

            User::create([
                'email' => $dados['email'],
                'password' => Hash::make($senha),
                'pessoa_id' => $pessoa->id,
                'perfil' => User::PERFIL_CLIENTE,
            ]);

            return $senha;
        });
    }
}
