<?php

namespace App\Actions\ContasPagar;

use App\Models\ContaPagar;
use App\Models\FormaPagamento;
use App\Models\PagamentoContaPagar;
use App\Models\ParcelaContaPagar;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistrarPagamentoContaPagar
{
    public function execute(
        ContaPagar $conta,
        array $dados
    ): PagamentoContaPagar {
        return DB::transaction(function () use ($conta, $dados) {
            $conta = ContaPagar::query()
                ->lockForUpdate()
                ->findOrFail($conta->id);

            if ($conta->status === 'cancelada') {
                throw new InvalidArgumentException(
                    'Não é possível registrar pagamento em uma conta cancelada.'
                );
            }

            $parcelaId = $dados['parcela_conta_pagar_id'] ?? null;

            if (!$parcelaId) {
                throw new InvalidArgumentException(
                    'A parcela do pagamento deve ser informada.'
                );
            }

            $parcela = ParcelaContaPagar::query()
                ->whereKey($parcelaId)
                ->where('conta_pagar_id', $conta->id)
                ->lockForUpdate()
                ->first();

            if (!$parcela) {
                throw new InvalidArgumentException(
                    'A parcela selecionada não pertence a esta conta a pagar.'
                );
            }

            $formaPagamento = FormaPagamento::query()
                ->whereKey($dados['forma_pagamento_id'])
                ->where('ativo', true)
                ->first();

            if (!$formaPagamento) {
                throw new InvalidArgumentException(
                    'A forma de pagamento selecionada não está disponível.'
                );
            }

            $valor = round((float) $dados['valor'], 2);

            if ($valor <= 0) {
                throw new InvalidArgumentException(
                    'O valor do pagamento deve ser maior que zero.'
                );
            }

            $valorPagoParcela = (float) $parcela
                ->pagamentosAtivos()
                ->sum('valor');

            $saldoParcela = round(
                (float) $parcela->valor - $valorPagoParcela,
                2
            );

            if ($saldoParcela <= 0) {
                throw new InvalidArgumentException(
                    'Esta parcela já está totalmente paga.'
                );
            }

            if ($valor > $saldoParcela) {
                throw new InvalidArgumentException(
                    'O valor do pagamento não pode ser maior que o saldo da parcela de R$ ' .
                    number_format($saldoParcela, 2, ',', '.')
                );
            }

            $pagamento = $conta->pagamentos()->create([
                'parcela_conta_pagar_id' => $parcela->id,
                'valor' => $valor,
                'data_pagamento' => $dados['data_pagamento'],
                'forma_pagamento_id' => $formaPagamento->id,
                'forma_pagamento' => $formaPagamento->nome,
                'observacoes' => $dados['observacoes'] ?? null,
            ]);

            $valorPagoConta = (float) $conta
                ->pagamentosAtivos()
                ->sum('valor');

            $valorConta = (float) $conta->valor;

            if ($valorPagoConta >= $valorConta) {
                $status = 'paga';
            } elseif ($valorPagoConta > 0) {
                $status = 'parcialmente_paga';
            } else {
                $status = 'aberta';
            }

            $conta->update([
                'status' => $status,
            ]);

            return $pagamento->fresh();
        });
    }
}
