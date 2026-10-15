<?php

namespace App\Actions\VeiculosClientes;

use App\Models\VeiculosCliente;
use Illuminate\Support\Facades\DB;

class CriarVeiculoCliente
{
    public function execute(
        array $dados,
        $user
    ): VeiculosCliente {
        return DB::transaction(
            function () use ($dados, $user) {
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

                    $clientesIds = [
                        (int) $clienteId,
                    ];
                } else {
                    abort(
                        403,
                        'Perfil de usuário sem permissão para cadastrar veículos.'
                    );
                }

                if ($clientesIds === []) {
                    abort(
                        422,
                        'Selecione pelo menos um cliente.'
                    );
                }

                $veiculoCliente = VeiculosCliente::create([
                    'placa' => $dados['placa'],
                    'ano' => $dados['ano'],
                    'cor' => $dados['cor'] ?? null,
                    'veiculo_id' => $dados['veiculo_id'],

                    /*
                     * Compatibilidade temporária.
                     *
                     * A relação oficial com clientes é
                     * cliente_veiculo_cliente.
                     */
                    'cliente_id' => $clientesIds[0],
                ]);

                $veiculoCliente
                    ->clientes()
                    ->sync($clientesIds);

                return $veiculoCliente->load([
                    'veiculo.montadora',
                    'clientes.pessoa',
                ]);
            }
        );
    }
}
