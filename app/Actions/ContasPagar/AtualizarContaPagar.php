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
                ->with('parcelas')
                ->findOrFail($conta->id);

            if ($conta->status === 'cancelada') {
                throw new InvalidArgumentException(
                    'Não é possível alterar uma conta cancelada.'
                );
            }

            $valorPago = (float) $conta->pagamentosAtivos()->sum('valor');

            if (
                $conta->status === 'paga'
                || $valorPago >= (float) $conta->valor
            ) {
                throw new InvalidArgumentException(
                    'Não é possível alterar uma conta que já foi totalmente paga.'
                );
            }

            $quantidadeParcelas = $conta->parcelas->count();

            if ($quantidadeParcelas > 1) {
                if (
                    array_key_exists('valor', $dados)
                    && round((float) $dados['valor'], 2) !== round((float) $conta->valor, 2)
                ) {
                    throw new InvalidArgumentException(
                        'O valor de uma conta com múltiplas parcelas deve ser alterado pelo gerenciamento das parcelas.'
                    );
                }

                if (
                    array_key_exists('data_vencimento', $dados)
                    && $dados['data_vencimento'] !== $conta->data_vencimento?->format('Y-m-d')
                ) {
                    throw new InvalidArgumentException(
                        'O vencimento de uma conta com múltiplas parcelas deve ser alterado pelo gerenciamento das parcelas.'
                    );
                }
            }

            if (array_key_exists('valor', $dados)) {
                $novoValor = round((float) $dados['valor'], 2);

                if ($novoValor <= 0) {
                    throw new InvalidArgumentException(
                        'O valor da conta deve ser maior que zero.'
                    );
                }

                if ($valorPago > 0 && $novoValor < $valorPago) {
                    throw new InvalidArgumentException(
                        'O valor da conta não pode ser menor que o total já pago de R$ ' .
                        number_format($valorPago, 2, ',', '.')
                    );
                }

                $dados['valor'] = $novoValor;
            }

            unset($dados['status']);

            $conta->update($dados);

            if ($quantidadeParcelas === 1) {
                $parcela = $conta->parcelas->first();

                $parcela->update([
                    'valor' => $conta->valor,
                    'data_vencimento' => $conta->data_vencimento,
                ]);
            }

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

            return $conta->fresh('parcelas');
        });
    }
}
