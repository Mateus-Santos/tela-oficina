<?php

namespace App\Services\Compra;

use App\Models\Compra;
use App\Models\CompraDevolucaoItem;
use App\Models\CompraItem;
use App\Models\MovimentacaoEstoque;
use App\Support\Estoque\Quantidade;

/** Saldos em milesimos, sempre derivados das entradas e dos documentos efetivados. */
class SaldoDevolucaoCompra
{
    public function consultar(Compra $compra): array
    {
        $compra->loadMissing('itens');
        $saldos = [];

        foreach ($compra->itens as $item) {
            $nf = Quantidade::milessimos((string) $item->quantidade);
            $aceita = $item->quantidade_aceita !== null
                ? Quantidade::milessimos((string) $item->quantidade_aceita)
                : ($item->quantidade_conferida !== null
                    ? Quantidade::milessimos((string) $item->quantidade_conferida)
                    : 0);

            $movimentos = MovimentacaoEstoque::query()
                ->where('origem_type', $item->getMorphClass())
                ->where('origem_id', $item->id);
            $entrada = Quantidade::milessimos((string) (clone $movimentos)
                ->where('tipo', 'entrada')->sum('quantidade'));
            $estornoDireto = (clone $movimentos)->where('tipo', 'saida')->exists();

            $devolucoes = CompraDevolucaoItem::query()
                ->where('compra_item_id', $item->id)
                ->whereHas('devolucao', fn ($q) => $q->where('status', 'efetivada'));
            $baixa = Quantidade::milessimos((string) (clone $devolucoes)
                ->where('modalidade', 'estoque')->sum('quantidade'));
            $recusa = Quantidade::milessimos((string) (clone $devolucoes)
                ->where('modalidade', 'recusado')->sum('quantidade'));

            $saldos[$item->id] = [
                'entrada' => $entrada,
                'aceita' => $aceita,
                'estoque' => $estornoDireto ? 0 : max(0, min($entrada, $aceita) - $baixa),
                'recusado' => max(0, $nf - $aceita - $recusa),
            ];
        }

        return $saldos;
    }

    public function valorIncrementalCentavos(CompraItem $item, int $novaQtd): int
    {
        $qtdNf = Quantidade::milessimos((string) $item->quantidade);
        if ($qtdNf <= 0 || $novaQtd <= 0) {
            throw new \InvalidArgumentException('Quantidade de devolucao invalida.');
        }

        $anterior = Quantidade::milessimos((string) CompraDevolucaoItem::query()
            ->where('compra_item_id', $item->id)
            ->whereHas('devolucao', fn ($q) => $q
                ->where('status', 'efetivada')->where('modalidade', 'documental'))
            ->sum('quantidade'));

        if ($anterior + $novaQtd > $qtdNf) {
            throw new \InvalidArgumentException('Devolucao documental excede a quantidade da NF original.');
        }

        // Arredondamento cumulativo: partes somadas nunca ultrapassam o valor do item.
        $total = (int) round((float) $item->valor_total * 100);
        return (int) round($total * ($anterior + $novaQtd) / $qtdNf)
             - (int) round($total * $anterior / $qtdNf);
    }
}
