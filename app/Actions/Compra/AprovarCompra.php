<?php

namespace App\Actions\Compra;

use App\Models\Compra;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AprovarCompra
{
    public function execute(Compra $compra): Compra
    {
        return DB::transaction(function () use ($compra) {
            $compra = Compra::query()
                ->lockForUpdate()
                ->with('itens')
                ->findOrFail($compra->id);

            if (!$compra->estaEmConferencia()) {
                throw new InvalidArgumentException(
                    'Somente compras em conferência podem ser aprovadas.'
                );
            }

            if ($compra->itens->isEmpty()) {
                throw new InvalidArgumentException(
                    'Não é possível aprovar uma compra sem itens.'
                );
            }

            foreach ($compra->itens as $item) {
                if ($item->quantidade_conferida === null) {
                    throw new InvalidArgumentException(
                        "O item '{$item->descricao}' ainda não foi conferido."
                    );
                }

                if ((float) $item->quantidade_conferida <= 0) {
                    throw new InvalidArgumentException(
                        "A quantidade conferida do item '{$item->descricao}' deve ser maior que zero."
                    );
                }
            }

            $compra->update([
                'status' => Compra::STATUS_APROVADA,
            ]);

            return $compra->fresh();
        });
    }
}
