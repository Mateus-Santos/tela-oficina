<?php

namespace App\Actions\FinanceiroSocios;

use App\Models\ContaReceberSocio;
use App\Models\RecebimentoContaReceberSocio;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EstornarRecebimentoSocio
{
    public function execute(ContaReceberSocio $conta, RecebimentoContaReceberSocio $recebimento, string $motivo): void
    {
        DB::transaction(function () use ($conta, $recebimento, $motivo) {
            $conta = ContaReceberSocio::query()->lockForUpdate()->findOrFail($conta->id);
            $recebimento = RecebimentoContaReceberSocio::query()
                ->whereKey($recebimento->id)
                ->where('conta_receber_socio_id', $conta->id)
                ->lockForUpdate()->firstOrFail();

            if ($recebimento->estornado_em !== null) {
                throw new InvalidArgumentException('Este recebimento já foi estornado.');
            }
            if (mb_strlen(trim($motivo)) < 5) {
                throw new InvalidArgumentException('Informe um motivo de estorno válido.');
            }

            $recebimento->update(['estornado_em' => now(), 'motivo_estorno' => trim($motivo)]);
            $recebido = DinheiroSocios::centavos((string) $conta->recebimentosAtivos()->sum('valor'));
            $total = DinheiroSocios::centavos($conta->valor);
            $status = $recebido >= $total ? 'quitada' : ($recebido > 0 ? 'parcial' : 'aberta');
            $conta->update([
                'status' => $status,
                'data_quitacao' => $status === 'quitada'
                    ? $conta->recebimentosAtivos()->orderByDesc('data_recebimento')->value('data_recebimento')
                    : null,
            ]);
        });
    }
}
