<?php

namespace App\Actions\Estoque;

use App\Models\MovimentacaoEstoque;
use App\Models\Produto;
use App\Support\Estoque\Quantidade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistrarSaida
{
    public function execute(
        Produto $produto,
        int|float|string $quantidade,
        ?float $valorUnitario = null,
        ?Model $origem = null,
        ?string $observacoes = null
    ): MovimentacaoEstoque {
        $quantidadeMilesimos = Quantidade::milessimos($quantidade);

        if ($quantidadeMilesimos <= 0) {
            throw new InvalidArgumentException('A quantidade da saída deve ser maior que zero.');
        }

        return DB::transaction(function () use (
            $produto,
            $quantidadeMilesimos,
            $valorUnitario,
            $origem,
            $observacoes
        ) {
            $produto = Produto::query()
                ->lockForUpdate()
                ->findOrFail($produto->id);

            $saldoAnterior = Quantidade::milessimos((string) $produto->quantidade);

            if ($quantidadeMilesimos > $saldoAnterior) {
                throw new InvalidArgumentException(
                    "Estoque insuficiente. Estoque atual: ".Quantidade::formatar($saldoAnterior)."."
                );
            }

            $saldoPosterior = $saldoAnterior - $quantidadeMilesimos;

            $produto->update([
                'quantidade' => Quantidade::formatar($saldoPosterior),
            ]);

            return MovimentacaoEstoque::create([
                'produto_id' => $produto->id,
                'tipo' => 'saida',
                'quantidade' => Quantidade::formatar($quantidadeMilesimos),
                'saldo_anterior' => Quantidade::formatar($saldoAnterior),
                'saldo_posterior' => Quantidade::formatar($saldoPosterior),
                'valor_unitario' => $valorUnitario,
                'origem_type' => $origem ? $origem->getMorphClass() : null,
                'origem_id' => $origem ? $origem->getKey() : null,
                'usuario_id' => auth()->id(),
                'observacoes' => $observacoes,
            ]);
        });
    }
}
