<?php

namespace App\Actions\ContasPagar;

use App\Models\ContaPagar;
use App\Models\PagamentoContaPagar;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EstornarPagamentoContaPagar
{
    public function execute(
        ContaPagar $conta,
        PagamentoContaPagar $pagamento,
        string $motivo
    ): void {
        DB::transaction(function () use ($conta, $pagamento, $motivo) {
            $conta = ContaPagar::query()
                ->lockForUpdate()
                ->findOrFail($conta->id);

            $pagamento = PagamentoContaPagar::query()
                ->where('id', $pagamento->id)
                ->where('conta_pagar_id', $conta->id)
                ->lockForUpdate()
                ->first();

            if (!$pagamento) {
                throw new InvalidArgumentException('Pagamento não encontrado para esta conta.');
            }

            if ($pagamento->estaEstornado()) {
                throw new InvalidArgumentException('Este pagamento já foi estornado.');
            }

            if ($conta->status === 'cancelada') {
                throw new InvalidArgumentException('Não é possível estornar pagamento de uma conta cancelada.');
            }

            $motivo = trim($motivo);

            if ($motivo === '') {
                throw new InvalidArgumentException('O motivo do estorno é obrigatório.');
            }

            $pagamento->update([
                'estornado_em' => now(),
                'motivo_estorno' => $motivo,
            ]);

            $valorPago = (float) $conta->pagamentosAtivos()->sum('valor');

            $conta->update([
                'status' => $valorPago <= 0
                    ? 'aberta'
                    : ($valorPago >= (float) $conta->valor
                        ? 'paga'
                        : 'parcialmente_paga'),
            ]);
        });
    }
}
