<?php

namespace App\Actions\ContasPagar;

use App\Models\ContaPagar;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancelarContaPagar
{
    public function execute(ContaPagar $conta, string $motivo): ContaPagar
    {
        return DB::transaction(function () use ($conta, $motivo) {
            $conta = ContaPagar::query()
                ->lockForUpdate()
                ->findOrFail($conta->id);

            if ($conta->status === 'cancelada') {
                throw new InvalidArgumentException('Esta conta já está cancelada.');
            }

            $motivo = trim($motivo);

            if ($motivo === '') {
                throw new InvalidArgumentException('O motivo do cancelamento é obrigatório.');
            }

            $valorPago = (float) $conta->pagamentosAtivos()->sum('valor');

            if ($valorPago > 0) {
                throw new InvalidArgumentException(
                    'Não é possível cancelar uma conta que possui pagamentos ativos. Estorne os pagamentos antes de cancelar.'
                );
            }

            $conta->update([
                'status' => 'cancelada',
                'observacoes' => $conta->observacoes
                    ? $conta->observacoes . "\nCancelamento: " . $motivo
                    : "Cancelamento: " . $motivo,
            ]);

            return $conta->refresh();
        });
    }
}
