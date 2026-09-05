<?php

namespace App\Actions\Notas;

use App\Actions\Estoque\RegistrarEntrada;
use App\Models\MovimentacaoEstoque;
use App\Models\Nota;
use App\Models\Produto;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancelarNota
{
    public function __construct(
        private RegistrarEntrada $registrarEntrada
    ) {
    }

    public function execute(Nota $nota): Nota
    {
        return DB::transaction(function () use ($nota) {
            $nota = Nota::query()
                ->lockForUpdate()
                ->with('itens.itemable')
                ->findOrFail($nota->id);

            if ($nota->status !== 'Finalizado') {
                throw new InvalidArgumentException(
                    'Somente notas finalizadas podem ser canceladas.'
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

                $saida = MovimentacaoEstoque::query()
                    ->where('tipo', 'saida')
                    ->where('origem_type', $item->getMorphClass())
                    ->where('origem_id', $item->id)
                    ->first();

                if (!$saida) {
                    throw new InvalidArgumentException(
                        "Não foi encontrada a baixa de estoque do item #{$item->id}."
                    );
                }

                $reversaoExistente = MovimentacaoEstoque::query()
                    ->where('tipo', 'entrada')
                    ->where('origem_type', $item->getMorphClass())
                    ->where('origem_id', $item->id)
                    ->where('observacoes', 'like', 'Reversão do cancelamento%')
                    ->exists();

                if ($reversaoExistente) {
                    throw new InvalidArgumentException(
                        "O item #{$item->id} já possui uma reversão de estoque."
                    );
                }

                $this->registrarEntrada->execute(
                    produto: $item->itemable,
                    quantidade: (float) $saida->quantidade,
                    valorUnitario: $saida->valor_unitario !== null
                        ? (float) $saida->valor_unitario
                        : null,
                    origem: $item,
                    observacoes: "Reversão do cancelamento da Nota #{$nota->id}, item #{$item->id}."
                );
            }

            $nota->update([
                'status' => 'Cancelado',
            ]);

            return $nota->fresh();
        });
    }
}
