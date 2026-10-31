<?php

namespace App\Actions\Estoque;

use App\Models\MovimentacaoEstoque;
use App\Models\Produto;
use App\Support\Estoque\Quantidade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistrarAjuste
{
    public function execute(
        Produto $produto,
        int|float|string $novoSaldo,
        ?float $valorUnitario = null,
        ?Model $origem = null,
        ?string $observacoes = null
    ): MovimentacaoEstoque {
        $novoSaldoMilesimos = Quantidade::milessimos($novoSaldo);

        return DB::transaction(function () use (
            $produto,
            $novoSaldoMilesimos,
            $valorUnitario,
            $origem,
            $observacoes
        ) {
            $produto = Produto::query()
                ->lockForUpdate()
                ->findOrFail($produto->id);

            $saldoAnterior = Quantidade::milessimos((string) $produto->quantidade);
            $diferenca = $novoSaldoMilesimos - $saldoAnterior;

            if ($diferenca === 0) {
                throw new InvalidArgumentException(
                    'O novo estoque é igual ao estoque atual. Nenhum ajuste é necessário.'
                );
            }

            $produto->update([
                'quantidade' => Quantidade::formatar($novoSaldoMilesimos),
            ]);

            return MovimentacaoEstoque::create([
                'produto_id' => $produto->id,
                'tipo' => 'ajuste',
                'quantidade' => Quantidade::formatar(abs($diferenca)),
                'saldo_anterior' => Quantidade::formatar($saldoAnterior),
                'saldo_posterior' => Quantidade::formatar($novoSaldoMilesimos),
                'valor_unitario' => $valorUnitario,
                'origem_type' => $origem ? $origem->getMorphClass() : null,
                'origem_id' => $origem ? $origem->getKey() : null,
                'usuario_id' => auth()->id(),
                'observacoes' => $observacoes,
            ]);
        });
    }
}
