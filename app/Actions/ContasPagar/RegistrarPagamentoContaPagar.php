<?php

namespace App\Actions\ContasPagar;

use App\Models\ContaPagar;
use App\Models\FormaPagamento;
use App\Models\PagamentoContaPagar;
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

            $valorPago = (float) $conta->pagamentosAtivos()->sum('valor');

            $saldo = round(
                (float) $conta->valor - $valorPago,
                2
            );

            if ($saldo <= 0) {
                throw new InvalidArgumentException(
                    'Esta conta já está totalmente paga.'
                );
            }

            if ($valor > $saldo) {
                throw new InvalidArgumentException(
                    'O valor do pagamento não pode ser maior que o saldo de R$ ' .
                    number_format($saldo, 2, ',', '.')
                );
            }

            $pagamento = $conta->pagamentos()->create([
                'valor' => $valor,
                'data_pagamento' => $dados['data_pagamento'],
                'forma_pagamento_id' => $formaPagamento->id,
                'forma_pagamento' => $formaPagamento->nome,
                'observacoes' => $dados['observacoes'] ?? null,
            ]);

            $novoValorPago = round(
                $valorPago + $valor,
                2
            );

            $conta->update([
                'status' => $novoValorPago >= (float) $conta->valor
                    ? 'paga'
                    : 'parcialmente_paga',
            ]);

            return $pagamento;
        });
    }
}
