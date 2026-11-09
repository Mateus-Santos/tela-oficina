<?php

namespace App\Services\PortalCliente;

use App\Models\User;
use App\Models\VeiculosCliente;
use Illuminate\Database\Eloquent\Builder;

class ConsultarVeiculosCliente
{
    public function consulta(User $usuario): Builder
    {
        $clienteId = $usuario->cliente?->id;

        // Nenhum nome ou dado de outro responsável é carregado.
        $query = VeiculosCliente::query()
            ->select([
                'id',
                'placa',
                'ano',
                'cor',
                'veiculo_id',
            ])
            ->with([
                'veiculo:id,nome,montadora_id',
                'veiculo.montadora:id,nome',
            ])
            ->withCount('clientes');

        if ($clienteId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'clientes',
            fn ($clientes) =>
                $clientes->where('clientes.id', $clienteId)
        );
    }
}
