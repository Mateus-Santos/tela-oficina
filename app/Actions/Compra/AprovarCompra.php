<?php

namespace App\Actions\Compra;

use App\Models\Compra;
use App\Support\Estoque\Quantidade;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AprovarCompra
{
    public function execute(Compra $compra): Compra
    {
        return DB::transaction(function () use ($compra): Compra {
            $compra = Compra::query()->lockForUpdate()->with('itens')->findOrFail($compra->id);
            if (!$compra->estaEmConferencia()) {
                throw new InvalidArgumentException('Somente compras em conferência podem ser aprovadas.');
            }
            if ($compra->itens->isEmpty()) {
                throw new InvalidArgumentException('Não é possível aprovar uma compra sem itens.');
            }
            foreach ($compra->itens as $item) {
                if ($item->quantidade_conferida === null) {
                    throw new InvalidArgumentException("O item '{$item->descricao}' ainda não foi conferido.");
                }
                $recebida = Quantidade::milessimos((string) $item->quantidade_conferida);
                $avariada = Quantidade::milessimos((string) ($item->quantidade_avariada ?? '0'));
                $aceita = Quantidade::milessimos((string) ($item->quantidade_aceita ?? $item->quantidade_conferida));
                if ($avariada > $recebida || $aceita > $recebida - $avariada
                    || $aceita > Quantidade::milessimos((string) $item->quantidade)) {
                    throw new InvalidArgumentException("Quantidades inconsistentes no item '{$item->descricao}'.");
                }
                if (($recebida !== Quantidade::milessimos((string) $item->quantidade)
                    || $avariada > 0 || $aceita !== Quantidade::milessimos((string) $item->quantidade))
                    && mb_strlen(trim((string) $item->observacao_divergencia)) < 5) {
                    throw new InvalidArgumentException("Justifique a divergência no item '{$item->descricao}'.");
                }
            }
            $compra->update(['status' => Compra::STATUS_APROVADA]);
            return $compra->fresh();
        });
    }
}
