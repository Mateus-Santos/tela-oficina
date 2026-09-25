<?php

namespace App\Actions\Financeiro;

use App\Models\ContaReceber;
use App\Models\FormaPagamento;
use App\Models\ParcelaContaReceber;
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

            $parcela = ParcelaContaReceber::query()
                ->whereKey($dados['parcela_conta_receber_id'])
                ->lockForUpdate()
                ->first();

            if (!$parcela) {
                throw ValidationException::withMessages([
                    'parcela_conta_receber_id' => 'A parcela informada não existe.',
                ]);
            }

            if ((int) $parcela->conta_receber_id !== (int) $conta->id) {
                throw ValidationException::withMessages([
                    'parcela_conta_receber_id' => 'A parcela informada não pertence a esta conta a receber.',
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

            $valorParcelaCentavos = (int) round(
                (float) $parcela->valor * 100
            );

            $valorRecebidoParcelaCentavos = (int) round(
                (float) $parcela->recebimentos()
                    ->whereNull('estornado_em')
                    ->sum('valor') * 100
            );

            $saldoParcelaCentavos =
                $valorParcelaCentavos
                - $valorRecebidoParcelaCentavos;

            if ($saldoParcelaCentavos <= 0) {
                throw ValidationException::withMessages([
                    'parcela_conta_receber_id' => 'Esta parcela já está quitada.',
                ]);
            }

            if ($valorCentavos > $saldoParcelaCentavos) {
                throw ValidationException::withMessages([
                    'valor' => sprintf(
                        'O valor informado é superior ao saldo da parcela. Saldo disponível: R$ %s.',
                        number_format(
                            $saldoParcelaCentavos / 100,
                            2,
                            ',',
                            '.'
                        )
                    ),
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

            $valorRecebidoContaCentavos = (int) round(
                (float) $conta->recebimentos()
                    ->whereNull('estornado_em')
                    ->sum('valor') * 100
            );

            $saldoContaCentavos =
                $valorDevidoCentavos
                - $valorRecebidoContaCentavos;

            if ($saldoContaCentavos <= 0) {
                throw ValidationException::withMessages([
                    'conta_receber_id' => 'Esta conta não possui saldo disponível para recebimento.',
                ]);
            }

            if ($valorCentavos > $saldoContaCentavos) {
                throw ValidationException::withMessages([
                    'valor' => sprintf(
                        'O valor informado é superior ao saldo da conta. Saldo disponível: R$ %s.',
                        number_format(
                            $saldoContaCentavos / 100,
                            2,
                            ',',
                            '.'
                        )
                    ),
                ]);
            }

            $recebimento = Recebimento::create([
                'conta_receber_id' => $conta->id,
                'parcela_conta_receber_id' => $parcela->id,
                'forma_pagamento_id' => $dados['forma_pagamento_id'],
                'valor' => $dados['valor'],
                'data_pagamento' => $dados['data_pagamento'],
                'usuario_id' => auth()->id(),
                'observacoes' => $dados['observacoes'] ?? null,
            ]);

            $novoTotalRecebidoCentavos =
                $valorRecebidoContaCentavos
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
