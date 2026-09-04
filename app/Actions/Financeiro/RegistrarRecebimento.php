<?php

namespace App\Actions\Financeiro;

use App\Models\ContaReceber;
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

            /*
             * Validações de status
             */

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

            /*
             * Valor total devido:
             *
             * Valor original
             * - desconto
             * + juros
             * + multa
             */

            $valorDevidoCentavos =
                $valorOriginalCentavos
                - $descontoCentavos
                + $jurosCentavos
                + $multaCentavos;

            /*
             * Soma dos recebimentos já registrados.
             */

            $valorRecebidoCentavos = (int) round(
                (float) $conta->recebimentos()->sum('valor') * 100
            );

            /*
             * Valor que está sendo recebido agora.
             */

            $valorCentavos = (int) round(
                (float) $dados['valor'] * 100
            );

            /*
             * Saldo atual da conta.
             */

            $saldoCentavos =
                $valorDevidoCentavos
                - $valorRecebidoCentavos;

            /*
             * Impede recebimento acima do saldo.
             */

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

            /*
             * Registra o recebimento.
             */

            $recebimento = Recebimento::create([
                'conta_receber_id' => $conta->id,
                'forma_pagamento_id' => $dados['forma_pagamento_id'],
                'valor' => $dados['valor'],
                'data_pagamento' => $dados['data_pagamento'],
                'usuario_id' => auth()->id(),
                'observacoes' => $dados['observacoes'] ?? null,
            ]);

            /*
             * Calcula o novo total recebido.
             */

            $novoTotalRecebidoCentavos =
                $valorRecebidoCentavos
                + $valorCentavos;

            /*
             * Atualiza o status da conta.
             *
             * Se o total recebido atingir ou ultrapassar
             * o valor devido, a conta será considerada quitada.
             */

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
