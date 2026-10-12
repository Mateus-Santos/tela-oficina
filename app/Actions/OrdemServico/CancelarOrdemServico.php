<?php

namespace App\Actions\OrdemServico;

use App\Models\OrdemServico;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancelarOrdemServico
{
    public function execute(
        OrdemServico $ordemServico
    ): OrdemServico {
        return DB::transaction(function () use (
            $ordemServico
        ) {
            $ordemServico = OrdemServico::query()
                ->lockForUpdate()
                ->findOrFail($ordemServico->id);

            if ($ordemServico->status !== 'aberta') {
                throw new InvalidArgumentException(
                    'Somente ordens de serviço abertas podem ser canceladas.'
                );
            }

            $ordemServico->update([
                'status' => 'cancelada',
                'data_fechamento' => now(),
            ]);

            return $ordemServico->fresh([
                'etapa',
            ]);
        });
    }
}
