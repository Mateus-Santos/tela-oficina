<?php

namespace App\Actions\Clientes;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AtualizarCliente
{
    public function execute(
        Cliente $cliente,
        ?User $usuario,
        array $dados
    ): void {
        DB::transaction(function () use ($cliente, $usuario, $dados): void {
            $cliente->pessoa->update([
                'nome' => $dados['nome'],
                'cpf' => $dados['cpf'] ?? null,
                'rg' => $dados['rg'] ?? null,
                'data_nascimento' => $dados['data_nascimento'] ?? null,
                'telefone_1' => $dados['telefone_1'] ?? null,
                'telefone_2' => $dados['telefone_2'] ?? null,
            ]);

            $cliente->update([
                'pontos' => $dados['pontos'] ?? 0,
            ]);

            if ($usuario) {
                $usuario->update([
                    'email' => $dados['email'],
                ]);
            }
        });
    }
}
