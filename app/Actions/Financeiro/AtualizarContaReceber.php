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
            $contaReceber = ContaReceber::query()
                ->lockForUpdate()
                ->findOrFail($contaReceber->id);

            if (in_array($contaReceber->status, ['quitada', 'cancelada'], true)) {
                throw ValidationException::withMessages([
                    'contaReceber' => 'Contas quitadas ou canceladas não podem ser alteradas.',
                ]);
            }

            if ($contaReceber->recebimentos()->exists()) {
                throw ValidationException::withMessages([
                    'contaReceber' => 'Contas que possuem recebimentos não podem ser alteradas.',
                ]);
            }

            $nota = null;

            if (!empty($dados['nota_id'])) {
                $nota = Nota::query()
                    ->lockForUpdate()
                    ->findOrFail($dados['nota_id']);

                $outraConta = ContaReceber::query()
                    ->where('nota_id', $nota->id)
                    ->where('id', '!=', $contaReceber->id)
                    ->exists();

                if ($outraConta) {
                    throw ValidationException::withMessages([
                        'nota_id' => "A Nota #{$nota->id} já possui outra conta a receber.",
                    ]);
                }

                if (
                    !empty($dados['cliente_id']) &&
                    (int) $dados['cliente_id'] !== (int) $nota->cliente_id
                ) {
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

            $valorOriginal = (float) ($dados['valor_original'] ?? 0);
            $desconto = (float) ($dados['desconto'] ?? 0);
            $juros = (float) ($dados['juros'] ?? 0);
            $multa = (float) ($dados['multa'] ?? 0);

            if ($valorOriginal <= 0) {
                throw ValidationException::withMessages([
                    'valor_original' => 'O valor original deve ser maior que zero.',
                ]);
            }

            if ($desconto < 0) {
                throw ValidationException::withMessages([
                    'desconto' => 'O desconto não pode ser negativo.',
                ]);
            }

            if ($juros < 0) {
                throw ValidationException::withMessages([
                    'juros' => 'Os juros não podem ser negativos.',
                ]);
            }

            if ($multa < 0) {
                throw ValidationException::withMessages([
                    'multa' => 'A multa não pode ser negativa.',
                ]);
            }

            $valorDevido =
                $valorOriginal
                - $desconto
                + $juros
                + $multa;

            if ($valorDevido <= 0) {
                throw ValidationException::withMessages([
                    'valor_original' => 'O valor final da conta deve ser maior que zero.',
                ]);
            }

            $dados['desconto'] = $desconto;
            $dados['juros'] = $juros;
            $dados['multa'] = $multa;
            $dados['status'] = 'aberta';
            $dados['data_quitacao'] = null;

            $contaReceber->update($dados);

            return $contaReceber->fresh();
        });
    }
}
