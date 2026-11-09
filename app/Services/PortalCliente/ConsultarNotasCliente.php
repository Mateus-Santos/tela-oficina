<?php

namespace App\Services\PortalCliente;

use App\Models\Nota;
use App\Models\User;
use App\Models\VeiculosCliente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ConsultarNotasCliente
{
    public function consulta(
        User $usuario,
        ?int $veiculoId = null
    ): Builder {
        $clienteId = $usuario->cliente?->id;

        $query = Nota::query()
            ->select([
                'id',
                'cliente_id',
                'veiculo_cliente_id',
                'tipo',
                'status',
                'total',
                'created_at',
            ]);

        if ($clienteId === null) {
            return $query->whereRaw('1 = 0');
        }

        $query
            ->where('cliente_id', $clienteId)
            ->with([
                'veiculosCliente' => function ($veiculo) use ($clienteId) {
                    $veiculo
                        ->select('id', 'placa', 'veiculo_id')
                        ->whereHas(
                            'clientes',
                            fn ($clientes) =>
                                $clientes->where('clientes.id', $clienteId)
                        )
                        ->with('veiculo:id,nome');
                },
            ]);

        if ($veiculoId !== null) {
            $query
                ->where('veiculo_cliente_id', $veiculoId)
                ->whereHas(
                    'veiculosCliente.clientes',
                    fn ($clientes) =>
                        $clientes->where('clientes.id', $clienteId)
                );
        }

        return $query;
    }

    public function veiculosDoCliente(User $usuario): Collection
    {
        $clienteId = $usuario->cliente?->id;

        if ($clienteId === null) {
            return collect();
        }

        return VeiculosCliente::query()
            ->select(['id', 'placa', 'veiculo_id'])
            ->whereHas(
                'clientes',
                fn ($clientes) =>
                    $clientes->where('clientes.id', $clienteId)
            )
            ->with('veiculo:id,nome')
            ->orderBy('placa')
            ->get();
    }
}
