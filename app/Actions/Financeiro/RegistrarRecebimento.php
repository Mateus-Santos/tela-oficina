<?php

namespace App\Actions\Financeiro;

use App\Models\ContaReceber;
use App\Models\FormaPagamento;
use App\Models\Recebimento;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrarRecebimento
{
    public function execute(array $dados): Recebimento
    {
        return DB::transaction(function () use ($dados) {
            $conta = ContaReceber::query()
                ->lockForUpdate()
                ->findOrFail($dados['conta_receber_id']);

            if ($conta->status === 'cancelada') {
                throw ValidationException::withMessages([
                    'conta_receber_id' => 'Não é possível receber uma conta cancelada.',
                ]);
            }

            if ($conta->status === 'quitada') {
                throw ValidationException::withMessages([
                    'conta_receber_id' => 'Esta conta já está quitada.',
                ]);
            }

            $valorCentavos = (int) round(
                (float) ($dados['valor'] ?? 0) * 100
            );

            if ($valorCentavos <= 0) {
                throw ValidationException::withMessages([
                    'valor' => 'O valor do recebimento deve ser maior que zero.',
                ]);
            }

            $formaPagamento = FormaPagamento::query()
                ->whereKey($dados['forma_pagamento_id'])
                ->where('ativo', true)
                ->exists();

            if (!$formaPagamento) {
                throw ValidationException::withMessages([
                    'forma_pagamento_id' => 'A forma de pagamento informada não existe ou está inativa.',
                ]);
            }

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
                throw ValidationException::withMessages([
                    'conta_receber_id' => 'A conta possui um valor devido inválido.',
                ]);
            }

            $valorRecebidoCentavos = (int) round(
                (float) $conta->recebimentos()->sum('valor') * 100
            );

            $saldoCentavos =
                $valorDevidoCentavos
                - $valorRecebidoCentavos;

            if ($saldoCentavos <= 0) {
                throw ValidationException::withMessages([
                    'conta_receber_id' => 'Esta conta não possui saldo disponível para recebimento.',
                ]);
            }

            if ($valorCentavos > $saldoCentavos) {
                throw ValidationException::withMessages([
                    'valor' => sprintf(
                        'O valor informado é superior ao saldo da conta. Saldo disponível: R$ %s.',
                        number_format(
                            $saldoCentavos / 100,
                            2,
                            ',',
                            '.'
                        )
                    ),
                ]);
            }

            $recebimento = Recebimento::create([
                'conta_receber_id' => $conta->id,
                'forma_pagamento_id' => $dados['forma_pagamento_id'],
                'valor' => $dados['valor'],
                'data_pagamento' => $dados['data_pagamento'],
                'usuario_id' => auth()->id(),
                'observacoes' => $dados['observacoes'] ?? null,
            ]);

            $novoTotalRecebidoCentavos =
                $valorRecebidoCentavos
                + $valorCentavos;

            if ($novoTotalRecebidoCentavos >= $valorDevidoCentavos) {
                $conta->update([
                    'status' => 'quitada',
                    'data_quitacao' => $dados['data_pagamento'],
                ]);
            } else {
                $conta->update([
                    'status' => 'parcial',
                    'data_quitacao' => null,
                ]);
            }

            return $recebimento;
        });
    }
}
