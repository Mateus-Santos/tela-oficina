<?php

namespace App\Actions\VeiculosClientes;

use App\Models\VeiculosCliente;

class AtualizarVeiculoCliente
{
    public function execute(
        VeiculosCliente $veiculoCliente,
        array $dados,
        $user
    ): VeiculosCliente {
        if ($user->permitions == 1) {
            $clienteId = $dados['id_cliente'];
        } else {
            $clienteId = $user->pessoa?->cliente?->id;

            if (!$clienteId) {
                abort(403, 'Usuário não possui um cliente vinculado.');
            }

            if ($veiculoCliente->cliente_id !== $clienteId) {
                abort(403, 'Ação não autorizada.');
            }
        }

        $veiculoCliente->update([
            'placa' => $dados['placa'],
            'ano' => $dados['ano'],
            'cor' => $dados['cor'] ?? null,
            'veiculo_id' => $dados['veiculo_id'],
            'cliente_id' => $clienteId,
        ]);

        return $veiculoCliente->refresh();
    }
}
