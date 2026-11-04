<?php

namespace App\Actions\FinanceiroSocios;

use App\Models\ContaPagarSocio;
use App\Models\PagamentoContaPagarSocio;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EstornarPagamentoSocio
{
    public function execute(ContaPagarSocio $conta, PagamentoContaPagarSocio $pagamento, string $motivo): void
    {
        DB::transaction(function () use ($conta, $pagamento, $motivo) {
            $conta = ContaPagarSocio::query()->lockForUpdate()->findOrFail($conta->id);
            $pagamento = PagamentoContaPagarSocio::query()
                ->whereKey($pagamento->id)
                ->where('conta_pagar_socio_id', $conta->id)
                ->lockForUpdate()->firstOrFail();

            if ($pagamento->estornado_em) {
                throw new InvalidArgumentException('Este pagamento ja foi estornado.');
            }

            $pagamento->update(['estornado_em' => now(), 'motivo_estorno' => $motivo]);
            $retirada = $pagamento->retirada()->lockForUpdate()->first();
            if ($pagamento->origem === 'empresa' && !$retirada) {
                throw new InvalidArgumentException('Retirada vinculada nao encontrada; estorno bloqueado.');
            }
            if ($retirada) {
                if ($retirada->estornado_em) {
                    throw new InvalidArgumentException('Retirada vinculada ja estornada; revisar integridade.');
                }
                $retirada->update(['estornado_em' => now(), 'motivo_estorno' => $motivo]);
            }

            $pago = DinheiroSocios::centavos((string) $conta->pagamentosAtivos()->sum('valor'));
            $total = DinheiroSocios::centavos($conta->valor);
            $conta->update(['status' => $pago >= $total
                ? 'paga' : ($pago > 0 ? 'parcialmente_paga' : 'aberta')]);
        });
    }
}
