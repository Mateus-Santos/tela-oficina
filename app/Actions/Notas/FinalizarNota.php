<?php

namespace App\Actions\Notas;

use App\Actions\Estoque\RegistrarSaida;
use App\Models\Nota;
use App\Models\Produto;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinalizarNota
{
    public function __construct(
        private RegistrarSaida $registrarSaida
    ) {
    }

    public function execute(Nota $nota): Nota
    {
        return DB::transaction(function () use ($nota) {
            $nota = Nota::query()
                ->lockForUpdate()
                ->with('itens.itemable')
                ->findOrFail($nota->id);

            if ($nota->status !== 'Aberto') {
                throw new InvalidArgumentException(
                    'Somente notas com status Aberto podem ser finalizadas.'
                );
            }

            if ($nota->itens->isEmpty()) {
                throw new InvalidArgumentException(
                    'Não é possível finalizar uma nota sem itens.'
                );
            }

            foreach ($nota->itens as $item) {
                if (!$item->itemable) {
                    throw new InvalidArgumentException(
                        "O item #{$item->id} possui um produto ou serviço inválido."
                    );
                }

                if (!$item->itemable instanceof Produto) {
                    continue;
                }

                $movimentacaoExistente = DB::table('movimentacao_estoques')
                    ->where('origem_type', $item->getMorphClass())
                    ->where('origem_id', $item->id)
                    ->where('tipo', 'saida')
                    ->exists();

                if ($movimentacaoExistente) {
                    throw new InvalidArgumentException(
                        "O item #{$item->id} já possui uma baixa de estoque registrada."
                    );
                }

                $this->registrarSaida->execute(
                    produto: $item->itemable,
                    quantidade: (float) $item->quantidade,
                    valorUnitario: (float) $item->valor_unitario,
                    origem: $item,
                    observacoes: "Baixa de estoque da Nota #{$nota->id}, item #{$item->id}."
                );
            }

            $nota->update([
                'status' => 'Finalizado',
            ]);

            return $nota->fresh();
        });
    }
}
