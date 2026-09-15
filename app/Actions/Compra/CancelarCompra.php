<?php

namespace App\Actions\Compra;

use App\Models\Compra;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancelarCompra
{
    public function execute(Compra $compra): Compra
    {
        return DB::transaction(function () use ($compra) {
            $compra = Compra::query()
                ->lockForUpdate()
                ->with('itens.movimentacoesEstoque')
                ->findOrFail($compra->id);

            if ($compra->estaCancelada()) {
                throw new InvalidArgumentException(
                    'A compra já está cancelada.'
                );
            }

            $estoqueLancado = $compra->itens->contains(
                function ($item) {
                    return $item->movimentacoesEstoque
                        ->contains('tipo', 'entrada');
                }
            );

            if ($estoqueLancado) {
                throw new InvalidArgumentException(
                    'Uma compra que já possui entrada no estoque não pode ser cancelada.'
                );
            }

            if (
                !$compra->estaPendente()
                && !$compra->estaEmConferencia()
                && !$compra->estaAprovada()
            ) {
                throw new InvalidArgumentException(
                    'O status atual da compra não permite cancelamento.'
                );
            }

            $compra->update([
                'status' => Compra::STATUS_CANCELADA,
            ]);

            return $compra->fresh();
        });
    }
}
