<?php

namespace App\Actions\Estoque;

use App\Models\MovimentacaoEstoque;
use App\Models\Produto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistrarAjuste
{
    public function execute(
        Produto $produto,
        float $novoSaldo,
        ?float $valorUnitario = null,
        ?Model $origem = null,
        ?string $observacoes = null
    ): MovimentacaoEstoque {
        if ($novoSaldo < 0) {
            throw new InvalidArgumentException(
                'O estoque ajustado não pode ser negativo.'
            );
        }

        return DB::transaction(function () use (
            $produto,
            $novoSaldo,
            $valorUnitario,
            $origem,
            $observacoes
        ) {
            $produto = Produto::query()
                ->lockForUpdate()
                ->findOrFail($produto->id);

            $saldoAnterior = (float) $produto->quantidade;
            $diferenca = $novoSaldo - $saldoAnterior;

            if ($diferenca == 0.0) {
                throw new InvalidArgumentException(
                    'O novo estoque é igual ao estoque atual. Nenhum ajuste é necessário.'
                );
            }

            $produto->update([
                'quantidade' => $novoSaldo,
            ]);

            return MovimentacaoEstoque::create([
                'produto_id' => $produto->id,
                'tipo' => 'ajuste',
                'quantidade' => abs($diferenca),
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $novoSaldo,
                'valor_unitario' => $valorUnitario,
                'origem_type' => $origem ? $origem->getMorphClass() : null,
                'origem_id' => $origem ? $origem->getKey() : null,
                'usuario_id' => auth()->id(),
                'observacoes' => $observacoes,
            ]);
        });
    }
}
