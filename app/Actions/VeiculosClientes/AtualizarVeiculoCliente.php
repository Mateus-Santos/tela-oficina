<?php

namespace App\Actions\VeiculosClientes;

use App\Models\VeiculosCliente;
use Illuminate\Support\Facades\DB;

class AtualizarVeiculoCliente
{
    public function execute(
        VeiculosCliente $veiculoCliente,
        array $dados,
        $user
    ): VeiculosCliente {
        return DB::transaction(
            function () use (
                $veiculoCliente,
                $dados,
                $user
            ) {
                if ($user->isAdmin()) {
                    $clientesIds = collect(
                        $dados['clientes'] ?? []
                    )
                        ->map(
                            fn ($id) => (int) $id
                        )
                        ->unique()
                        ->values()
                        ->all();
                } elseif ($user->isCliente()) {
                    $clienteId = $user
                        ->cliente
                        ?->id;

                    if (! $clienteId) {
                        abort(
                            403,
                            'Usuário não possui um cliente vinculado.'
                        );
                    }

                    $possuiAcesso = $veiculoCliente
                        ->clientes()
                        ->where(
                            'clientes.id',
                            $clienteId
                        )
                        ->exists();

                    if (! $possuiAcesso) {
                        abort(
                            403,
                            'Ação não autorizada.'
                        );
                    }

                    /*
                     * Cliente não pode alterar os
                     * responsáveis pelo veículo.
                     */
                    $clientesIds = $veiculoCliente
                        ->clientes()
                        ->pluck('clientes.id')
                        ->map(
                            fn ($id) => (int) $id
                        )
                        ->all();
                } else {
                    abort(
                        403,
                        'Perfil de usuário sem permissão para alterar veículos.'
                    );
                }

                if ($clientesIds === []) {
                    abort(
                        422,
                        'Selecione pelo menos um cliente.'
                    );
                }

                $veiculoCliente->update([
                    'placa' => $dados['placa'],
                    'ano' => $dados['ano'],
                    'cor' => $dados['cor'] ?? null,
                    'veiculo_id' => $dados['veiculo_id'],

                    /*
                     * Compatibilidade temporária.
                     */
                    'cliente_id' => $clientesIds[0],
                ]);

                $veiculoCliente
                    ->clientes()
                    ->sync($clientesIds);

                return $veiculoCliente
                    ->refresh()
                    ->load([
                        'veiculo.montadora',
                        'clientes.pessoa',
                    ]);
            }
        );
    }
}
