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

            $valorDevido =
                (float) $conta->valor_original
                - (float) $conta->desconto
                + (float) $conta->juros
                + (float) $conta->multa;

            $valorRecebido = (float) $conta->recebimentos()
                ->sum('valor');

            $saldo = $valorDevido - $valorRecebido;

            if ((float) $dados['valor'] > $saldo) {
                throw ValidationException::withMessages([
                    'valor' => sprintf(
                        'O valor informado é superior ao saldo da conta. Saldo disponível: R$ %s.',
                        number_format($saldo, 2, ',', '.')
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

            $novoTotalRecebido = $valorRecebido + (float) $dados['valor'];

            if ($novoTotalRecebido >= $valorDevido) {
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
