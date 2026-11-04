<?php

namespace App\Actions\FinanceiroSocios;

use App\Models\ContaPagarSocio;
use App\Models\PagamentoContaPagarSocio;
use App\Models\ParcelaContaPagarSocio;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistrarPagamentoSocio
{
    public function execute(ContaPagarSocio $conta, array $dados): PagamentoContaPagarSocio
    {
        return DB::transaction(function () use ($conta, $dados) {
            $conta = ContaPagarSocio::query()->lockForUpdate()->findOrFail($conta->id);

            if ($conta->status === 'cancelada') {
                throw new InvalidArgumentException('A conta esta cancelada.');
            }

            $parcela = ParcelaContaPagarSocio::query()
                ->whereKey($dados['parcela_id'])
                ->where('conta_pagar_socio_id', $conta->id)
                ->lockForUpdate()
                ->first();

            if (!$parcela) {
                throw new InvalidArgumentException('A parcela nao pertence a esta conta.');
            }

            $valor = DinheiroSocios::centavos($dados['valor']);
            $pago = DinheiroSocios::centavos((string) $parcela->pagamentosAtivos()->sum('valor'));
            $saldo = DinheiroSocios::centavos($parcela->valor) - $pago;

            if ($valor <= 0 || $valor > $saldo) {
                throw new InvalidArgumentException('Pagamento acima do saldo ou com valor invalido.');
            }

            $origem = $dados['origem'];
            if (!in_array($origem, ['pessoal', 'empresa'], true)) {
                throw new InvalidArgumentException('Origem de pagamento invalida.');
            }

            $pagamento = $conta->pagamentos()->create([
                'parcela_conta_pagar_socio_id' => $parcela->id,
                'valor' => DinheiroSocios::decimal($valor),
                'data_pagamento' => $dados['data_pagamento'],
                'origem' => $origem,
                'forma_pagamento_id' => $dados['forma_pagamento_id'] ?? null,
                'observacoes' => $dados['observacoes'] ?? null,
            ]);

            if ($origem === 'empresa') {
                $pagamento->retirada()->create([
                    'socio_id' => $conta->socio_id,
                    'tipo' => 'pagamento_divida',
                    'valor' => DinheiroSocios::decimal($valor),
                    'data_retirada' => $dados['data_pagamento'],
                    'observacoes' => 'Retirada vinculada ao pagamento pessoal #' . $pagamento->id,
                ]);
            }

            $totalPago = DinheiroSocios::centavos((string) $conta->pagamentosAtivos()->sum('valor'));
            $totalConta = DinheiroSocios::centavos($conta->valor);

            $conta->update([
                'status' => $totalPago >= $totalConta
                    ? 'paga' : ($totalPago > 0 ? 'parcialmente_paga' : 'aberta'),
            ]);

            return $pagamento->load('retirada');
        });
    }
}
