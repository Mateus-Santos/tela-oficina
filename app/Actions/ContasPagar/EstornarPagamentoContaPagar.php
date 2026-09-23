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
                ->whereKey($pagamento->id)
                ->where('conta_pagar_id', $conta->id)
                ->lockForUpdate()
                ->first();

            if (!$pagamento) {
                throw new InvalidArgumentException(
                    'Pagamento não encontrado para esta conta.'
                );
            }

            if ($pagamento->estaEstornado()) {
                throw new InvalidArgumentException(
                    'Este pagamento já foi estornado.'
                );
            }

            if ($conta->status === 'cancelada') {
                throw new InvalidArgumentException(
                    'Não é possível estornar pagamento de uma conta cancelada.'
                );
            }

            if (!$pagamento->parcela_conta_pagar_id) {
                throw new InvalidArgumentException(
                    'Este pagamento não possui uma parcela vinculada.'
                );
            }

            $parcelaPertence = $conta->parcelas()
                ->whereKey($pagamento->parcela_conta_pagar_id)
                ->exists();

            if (!$parcelaPertence) {
                throw new InvalidArgumentException(
                    'A parcela vinculada ao pagamento não pertence a esta conta a pagar.'
                );
            }

            $motivo = trim($motivo);

            if ($motivo === '') {
                throw new InvalidArgumentException(
                    'O motivo do estorno é obrigatório.'
                );
            }

            $pagamento->update([
                'estornado_em' => now(),
                'motivo_estorno' => $motivo,
            ]);

            $valorPago = (float) $conta
                ->pagamentosAtivos()
                ->sum('valor');

            $valorConta = (float) $conta->valor;

            if ($valorPago >= $valorConta) {
                $status = 'paga';
            } elseif ($valorPago > 0) {
                $status = 'parcialmente_paga';
            } else {
                $status = 'aberta';
            }

            $conta->update([
                'status' => $status,
            ]);
        });
    }
}
