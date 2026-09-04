<?php

namespace App\Actions\Estoque;

use App\Models\MovimentacaoEstoque;
use App\Models\Produto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistrarSaida
{
    public function execute(
        Produto $produto,
        float $quantidade,
        ?float $valorUnitario = null,
        ?Model $origem = null,
        ?string $observacoes = null
    ): MovimentacaoEstoque {
        if ($quantidade <= 0) {
            throw new InvalidArgumentException('A quantidade da saída deve ser maior que zero.');
        }

        return DB::transaction(function () use (
            $produto,
            $quantidade,
            $valorUnitario,
            $origem,
            $observacoes
        ) {
            $produto = Produto::query()
                ->lockForUpdate()
                ->findOrFail($produto->id);

            $saldoAnterior = (float) $produto->quantidade;

            if ($quantidade > $saldoAnterior) {
                throw new InvalidArgumentException(
                    "Estoque insuficiente. Estoque atual: {$saldoAnterior}."
                );
            }

            $saldoPosterior = $saldoAnterior - $quantidade;

            $produto->update([
                'quantidade' => $saldoPosterior,
            ]);

            return MovimentacaoEstoque::create([
                'produto_id' => $produto->id,
                'tipo' => 'saida',
                'quantidade' => $quantidade,
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'valor_unitario' => $valorUnitario,
                'origem_type' => $origem ? $origem->getMorphClass() : null,
                'origem_id' => $origem ? $origem->getKey() : null,
                'usuario_id' => auth()->id(),
                'observacoes' => $observacoes,
            ]);
        });
    }
}
