<?php

namespace App\Actions\Financeiro;

use App\Models\ContaReceber;
use App\Models\Nota;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AtualizarContaReceber
{
    public function execute(ContaReceber $contaReceber, array $dados): ContaReceber
    {
        return DB::transaction(function () use ($contaReceber, $dados) {
            if ($contaReceber->recebimentos()->exists()) {
                throw ValidationException::withMessages([
                    'contaReceber' => 'Contas que possuem recebimentos não podem ser alteradas.',
                ]);
            }

            $nota = null;

            if (!empty($dados['nota_id'])) {
                $nota = Nota::findOrFail($dados['nota_id']);

                if (!empty($dados['cliente_id']) && (int) $dados['cliente_id'] !== (int) $nota->cliente_id) {
                    throw ValidationException::withMessages([
                        'cliente_id' => 'O cliente informado não corresponde ao cliente da nota.',
                    ]);
                }

                $dados['cliente_id'] = $nota->cliente_id;
            }

            if (empty($dados['cliente_id'])) {
                throw ValidationException::withMessages([
                    'cliente_id' => 'É necessário informar um cliente ou uma nota vinculada.',
                ]);
            }

            $dados['desconto'] = $dados['desconto'] ?? 0;
            $dados['juros'] = $dados['juros'] ?? 0;
            $dados['multa'] = $dados['multa'] ?? 0;

            $valorDevido = (float) $dados['valor_original']
                - (float) $dados['desconto']
                + (float) $dados['juros']
                + (float) $dados['multa'];

            if ($valorDevido <= 0) {
                throw ValidationException::withMessages([
                    'valor_original' => 'O valor final da conta deve ser maior que zero.',
                ]);
            }

            $dados['status'] = 'aberta';
            $dados['data_quitacao'] = null;

            $contaReceber->update($dados);

            return $contaReceber->fresh();
        });
    }
}
