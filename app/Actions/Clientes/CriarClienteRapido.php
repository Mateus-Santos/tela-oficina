<?php

namespace App\Actions\Clientes;

use App\Models\Cliente;
use App\Models\Pessoa;
use Illuminate\Support\Facades\DB;

class CriarClienteRapido
{
    public function execute(
        array $dados
    ): Cliente {
        return DB::transaction(
            function () use ($dados) {
                $telefone =
                    preg_replace(
                        '/\D/',
                        '',
                        $dados['telefone_1']
                        ?? ''
                    );

                $pessoa =
                    Pessoa::create([
                        'nome' =>
                            trim(
                                $dados['nome']
                            ),

                        'telefone_1' =>
                            $telefone,
                    ]);

                $cliente =
                    Cliente::create([
                        'pessoa_id' =>
                            $pessoa->id,

                        'pontos' =>
                            0,
                    ]);

                return $cliente->load(
                    'pessoa'
                );
            }
        );
    }
}
