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
        // D01A e apenas operacional. Seus registros continuam compondo o
        // historico e os saldos fisicos, mas NAO reduzem o valor financeiro
        // nem impedem o vinculo pelo valor fiscal original da Compra.
        // Uma devolucao D04 pendente continua bloqueando novas vinculacoes.
        if ($compra->devolucoes()
            ->where('modalidade', 'documental')
            ->where('situacao_financeira', '!=', 'aplicada')
            ->exists()) {
            return true;
        }

        // Um abatimento D03B anterior nao deve ser sobrescrito silenciosamente
        // por uma nova vinculacao calculada somente pelas devolucoes D04.
        return \App\Models\CompraConciliacaoEfetivacao::query()
            ->where('compra_id', $compra->id)
            ->where('tratamento', 'abatimento')
            ->exists();
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
            // Excedente recebido (> NF) nao e recusa de mercadoria da NF:
            // nao liberar a geracao de conta sem regularizacao explicita.
            $recebida = Quantidade::milessimos((string) $item->quantidade_conferida);
            if ($recebida > $nf || $aceita > $nf) {
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
