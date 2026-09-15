<?php

namespace App\Actions\Compra;

use App\Models\Compra;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ConferirCompra
{
    public function execute(Compra $compra, array $dados): Compra
    {
        return DB::transaction(function () use ($compra, $dados) {
            $compra = Compra::query()
                ->lockForUpdate()
                ->with('itens')
                ->findOrFail($compra->id);

            if (!$compra->estaEmConferencia()) {
                throw new InvalidArgumentException(
                    'Somente compras em conferência podem ter suas quantidades conferidas.'
                );
            }

            if ($compra->itens->isEmpty()) {
                throw new InvalidArgumentException(
                    'A compra não possui itens para conferência.'
                );
            }

            $itensRecebidos = $dados['itens'] ?? [];

            foreach ($compra->itens as $item) {
                if (!array_key_exists($item->id, $itensRecebidos)) {
                    throw new InvalidArgumentException(
                        "A quantidade recebida do item '{$item->descricao}' não foi informada."
                    );
                }

                $quantidadeConferida = $itensRecebidos[$item->id]['quantidade_conferida'] ?? null;

                if ($quantidadeConferida === null || (float) $quantidadeConferida <= 0) {
                    throw new InvalidArgumentException(
                        "A quantidade recebida do item '{$item->descricao}' deve ser maior que zero."
                    );
                }

                $item->update([
                    'quantidade_conferida' => (float) $quantidadeConferida,
                ]);
            }

            return $compra->fresh('itens');
        });
    }
}
