<?php

namespace App\Actions\FinanceiroSocios;

use App\Models\RetiradaSocio;
use App\Models\Socio;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EstornarRetiradaSocio
{
    public function execute(Socio $socio, RetiradaSocio $retirada, string $motivo): void
    {
        DB::transaction(function () use ($socio, $retirada, $motivo) {
            $retirada = RetiradaSocio::query()
                ->whereKey($retirada->id)
                ->where('socio_id', $socio->id)
                ->lockForUpdate()->firstOrFail();

            if ($retirada->pagamento_conta_pagar_socio_id !== null) {
                throw new InvalidArgumentException('Estorne o pagamento da divida para reverter esta retirada.');
            }
            if ($retirada->estornado_em !== null) {
                throw new InvalidArgumentException('Retirada ja estornada.');
            }
            $retirada->update(['estornado_em' => now(), 'motivo_estorno' => $motivo]);
        });
    }
}
