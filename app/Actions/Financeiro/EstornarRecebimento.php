<?php

namespace App\Actions\Financeiro;

use App\Models\ContaReceber;
use App\Models\Recebimento;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EstornarRecebimento
{
    public function execute(
        ContaReceber $conta,
        Recebimento $recebimento,
        string $motivo
    ): void {
        DB::transaction(function () use (
            $conta,
            $recebimento,
            $motivo
        ) {
            $conta = ContaReceber::query()
                ->lockForUpdate()
                ->findOrFail($conta->id);

            $recebimento = Recebimento::query()
                ->where('id', $recebimento->id)
                ->where('conta_receber_id', $conta->id)
                ->lockForUpdate()
                ->first();

            if (!$recebimento) {
                throw new InvalidArgumentException(
                    'Recebimento não encontrado para esta conta.'
                );
            }

            if ($recebimento->estaEstornado()) {
                throw new InvalidArgumentException(
                    'Este recebimento já foi estornado.'
                );
            }

            if ($conta->status === 'cancelada') {
                throw new InvalidArgumentException(
                    'Não é possível estornar recebimento de uma conta cancelada.'
                );
            }

            $motivo = trim($motivo);

            if ($motivo === '') {
                throw new InvalidArgumentException(
                    'O motivo do estorno é obrigatório.'
                );
            }

            $recebimento->update([
                'estornado_em' => now(),
                'motivo_estorno' => $motivo,
            ]);

            $valorOriginalCentavos = (int) round(
                (float) $conta->valor_original * 100
            );

            $descontoCentavos = (int) round(
                (float) $conta->desconto * 100
            );

            $jurosCentavos = (int) round(
                (float) $conta->juros * 100
            );

            $multaCentavos = (int) round(
                (float) $conta->multa * 100
            );

            $valorDevidoCentavos =
                $valorOriginalCentavos
                - $descontoCentavos
                + $jurosCentavos
                + $multaCentavos;

            if ($valorDevidoCentavos <= 0) {
                throw new InvalidArgumentException(
                    'A conta possui um valor devido inválido.'
                );
            }

            $valorRecebidoCentavos = (int) round(
                (float) $conta->recebimentos()
                    ->whereNull('estornado_em')
                    ->sum('valor') * 100
            );

            if ($valorRecebidoCentavos <= 0) {
                $status = 'aberta';
                $dataQuitacao = null;
            } elseif ($valorRecebidoCentavos >= $valorDevidoCentavos) {
                $status = 'quitada';

                $dataQuitacao = $conta->recebimentos()
                    ->whereNull('estornado_em')
                    ->orderByDesc('data_pagamento')
                    ->value('data_pagamento');
            } else {
                $status = 'parcial';
                $dataQuitacao = null;
            }

            $conta->update([
                'status' => $status,
                'data_quitacao' => $dataQuitacao,
            ]);
        });
    }
}
