<?php

namespace App\Actions\Compra;

use App\Models\Compra;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class IniciarConferenciaCompra
{
    public function execute(Compra $compra): Compra
    {
        return DB::transaction(function () use ($compra) {
            $compra = Compra::query()
                ->lockForUpdate()
                ->with('itens')
                ->findOrFail($compra->id);

            if (!$compra->estaPendente()) {
                throw new InvalidArgumentException(
                    'Somente compras pendentes podem iniciar a conferência.'
                );
            }

            if ($compra->itens->isEmpty()) {
                throw new InvalidArgumentException(
                    'Não é possível iniciar a conferência de uma compra sem itens.'
                );
            }

            $compra->update([
                'status' => Compra::STATUS_CONFERINDO,
            ]);

            return $compra->fresh();
        });
    }
}
