<?php

namespace App\Actions\Compra;

use App\Actions\Estoque\RegistrarSaida;
use App\Models\Compra;
use App\Models\MovimentacaoEstoque;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EstornarCompra
{
    public function __construct(
        private RegistrarSaida $registrarSaida
    ) {
    }

    public function execute(Compra $compra): Compra
    {
        return DB::transaction(function () use ($compra) {
            $compra = Compra::query()
                ->lockForUpdate()
                ->with('itens.produto')
                ->findOrFail($compra->id);

            if ($compra->estaCancelada()) {
                throw new InvalidArgumentException('A compra já está cancelada.');
            }

            if (!$compra->estaAprovada()) {
                throw new InvalidArgumentException('Somente compras aprovadas podem ser estornadas.');
            }

            if ($compra->itens->isEmpty()) {
                throw new InvalidArgumentException('A compra não possui itens para estorno.');
            }

            $entradasEncontradas = 0;

            foreach ($compra->itens as $item) {
                $entrada = MovimentacaoEstoque::query()
                    ->where('tipo', 'entrada')
                    ->where('origem_type', $item->getMorphClass())
                    ->where('origem_id', $item->id)
                    ->first();

                if (!$entrada) {
                    continue;
                }

                $entradasEncontradas++;

                $estornoExistente = MovimentacaoEstoque::query()
                    ->where('tipo', 'saida')
                    ->where('origem_type', $item->getMorphClass())
                    ->where('origem_id', $item->id)
                    ->where('observacoes', 'like', 'Estorno da entrada da Compra%')
                    ->exists();

                if ($estornoExistente) {
                    throw new InvalidArgumentException(
                        "O item '{$item->descricao}' já possui um estorno de estoque."
                    );
                }

                if (!$item->produto) {
                    throw new InvalidArgumentException(
                        "O produto do item '{$item->descricao}' não foi encontrado."
                    );
                }

                $this->registrarSaida->execute(
                    produto: $item->produto,
                    quantidade: (float) $entrada->quantidade,
                    valorUnitario: $entrada->valor_unitario !== null
                        ? (float) $entrada->valor_unitario
                        : null,
                    origem: $item,
                    observacoes: "Estorno da entrada da Compra #{$compra->id}, item #{$item->id}."
                );
            }

            if ($entradasEncontradas === 0) {
                throw new InvalidArgumentException(
                    'Não existem entradas de estoque registradas para esta compra.'
                );
            }

            $compra->update([
                'status' => Compra::STATUS_CANCELADA,
            ]);

            return $compra->fresh();
        });
    }
}
