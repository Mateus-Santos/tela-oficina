<?php

namespace App\Actions\ContasPagar;

use App\Models\ContaPagar;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AtualizarContaPagar
{
    public function execute(ContaPagar $conta, array $dados): ContaPagar
    {
        return DB::transaction(function () use ($conta, $dados) {
            $conta = ContaPagar::query()
                ->lockForUpdate()
                ->findOrFail($conta->id);

            if ($conta->status === 'cancelada') {
                throw new InvalidArgumentException(
                    'Não é possível alterar uma conta cancelada.'
                );
            }

            $valorPago = (float) $conta->pagamentosAtivos()->sum('valor');

            if (array_key_exists('valor', $dados)) {
                $novoValor = round((float) $dados['valor'], 2);

                if ($novoValor <= 0) {
                    throw new InvalidArgumentException(
                        'O valor da conta deve ser maior que zero.'
                    );
                }

                if ($valorPago > 0 && $novoValor <= $valorPago) {
                    throw new InvalidArgumentException(
                        'O valor da conta deve ser maior que o total já pago de R$ ' .
                        number_format($valorPago, 2, ',', '.')
                    );
                }

                $dados['valor'] = $novoValor;
            }

            unset($dados['status']);

            $conta->update($dados);

            $valorAtual = (float) $conta->valor;

            if ($valorPago <= 0) {
                $status = 'aberta';
            } elseif ($valorPago >= $valorAtual) {
                $status = 'paga';
            } else {
                $status = 'parcialmente_paga';
            }

            $conta->update([
                'status' => $status,
            ]);

            return $conta->refresh();
        });
    }
}
