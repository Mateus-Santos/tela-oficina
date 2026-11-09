<?php

namespace App\Services\PortalCliente;

use App\Models\OrdemServico;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ConsultarOrdensServicoCliente
{
    public function consulta(
        User $usuario,
        ?int $veiculoId = null,
        ?string $status = null
    ): Builder {
        $clienteId = $usuario->cliente?->id;

        $query = OrdemServico::query()
            ->select([
                'id',
                'cliente_id',
                'veiculo_cliente_id',
                'setor_servico_id',
                'etapa_id',
                'status',
                'valor',
                'data_abertura',
                'data_fechamento',
            ]);

        if ($clienteId === null) {
            return $query->whereRaw('1 = 0');
        }

        // O titular da OS determina o acesso.
        // O vínculo ao veículo nunca amplia essa autorização.
        $query
            ->where('cliente_id', $clienteId)
            ->with([
                'etapa:id,nome',
                'setorServico:id,setor',
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

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        return $query;
    }
}
