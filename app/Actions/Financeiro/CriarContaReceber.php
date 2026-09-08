<?php

namespace App\Actions\Financeiro;

use App\Models\ContaReceber;
use App\Models\Nota;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CriarContaReceber
{
    public function execute(array $dados): ContaReceber
    {
        return DB::transaction(function () use ($dados) {
            $nota = null;

            if (!empty($dados['nota_id'])) {
                $nota = Nota::query()
                    ->lockForUpdate()
                    ->findOrFail($dados['nota_id']);

                $contaExistente = ContaReceber::query()
                    ->where('nota_id', $nota->id)
                    ->exists();

                if ($contaExistente) {
                    throw ValidationException::withMessages([
                        'nota_id' => "A Nota #{$nota->id} já possui uma conta a receber.",
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

            return ContaReceber::create($dados);
        });
    }
}
