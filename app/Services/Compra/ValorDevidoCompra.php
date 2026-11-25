<?php

namespace App\Services\Compra;

use App\Models\Compra;
use App\Models\CompraDevolucaoItem;
use App\Support\Estoque\Quantidade;

/** O valor fiscal original da Compra nunca e sobrescrito. */
class ValorDevidoCompra
{
    public function abatimentoAplicadoCentavos(Compra $compra): int
    {
        $total = $compra->devolucoes()
            ->where('status', 'efetivada')
            ->where('modalidade', 'documental')
            ->where('situacao_financeira', 'aplicada')
            ->sum('valor_abatimento');
        return (int) round((float) $total * 100);
    }

    public function valorLiquidoCentavos(Compra $compra): int
    {
        return max(0, (int) round((float) $compra->valor_total * 100)
            - $this->abatimentoAplicadoCentavos($compra));
    }

    public function precisaConciliacao(Compra $compra): bool
    {
        return $compra->devolucoes()->where(function ($q): void {
            $q->where('modalidade', '!=', 'documental')
              ->orWhere('situacao_financeira', '!=', 'aplicada');
        })->exists();
    }

    /** Divergencias integralmente cobertas por recusa formal, sem estoque, podem ser vinculadas. */
    public function divergenciaCoberta(Compra $compra): bool
    {
        if (!$compra->possuiDivergenciaConferencia()) {
            return true;
        }
        $compra->loadMissing('itens');
        foreach ($compra->itens as $item) {
            if ($item->quantidade_conferida === null) {
                return false;
            }
            $nf = Quantidade::milessimos((string) $item->quantidade);
            $aceita = Quantidade::milessimos((string)
                ($item->quantidade_aceita ?? $item->quantidade_conferida));
            if ($aceita > $nf) {
                return false;
            }
            $recusas = CompraDevolucaoItem::query()
                ->where('compra_item_id', $item->id)
                ->where('modalidade', 'recusado')
                ->whereHas('devolucao', fn ($q) => $q
                    ->where('status', 'efetivada')
                    ->where('modalidade', 'documental')
                    ->where('situacao_financeira', 'aplicada'))
                ->sum('quantidade');
            if (Quantidade::milessimos((string) $recusas) < $nf - $aceita) {
                return false;
            }
        }
        return true;
    }
}
