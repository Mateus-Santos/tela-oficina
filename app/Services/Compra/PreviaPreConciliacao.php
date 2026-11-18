<?php

namespace App\Services\Compra;

use App\Models\Compra;
use App\Models\ContaPagar;
use App\Support\Estoque\Quantidade;

/**
 * Somente leitura: fotografia operacional e financeira para elaborar proposta.
 * Pagamentos de contas agrupadas pertencem a conta inteira, nao a uma compra.
 */
class PreviaPreConciliacao
{
    public function montar(Compra $compra): array
    {
        $compra->load(['itens', 'devolucoes.itens', 'contasPagar', 'contaPagar']);
        $devolvidos = [];
        foreach ($compra->devolucoes as $devolucao) {
            if ($devolucao->status !== 'efetivada') {
                continue;
            }
            foreach ($devolucao->itens as $linha) {
                $id = (int) $linha->compra_item_id;
                $devolvidos[$id] = ($devolvidos[$id] ?? 0)
                    + Quantidade::milessimos((string) $linha->quantidade);
            }
        }

        $itens = [];
        foreach ($compra->itens as $item) {
            $nf = Quantidade::milessimos((string) $item->quantidade);
            $recebida = $item->quantidade_conferida !== null
                ? Quantidade::milessimos((string) $item->quantidade_conferida) : null;
            $aceita = $item->quantidade_aceita !== null
                ? Quantidade::milessimos((string) $item->quantidade_aceita)
                : $recebida;
            $devolvida = $devolvidos[$item->id] ?? 0;
            $itens[] = [
                'descricao' => $item->descricao,
                'quantidade_nf' => Quantidade::formatar($nf),
                'quantidade_recebida' => $recebida === null ? null : Quantidade::formatar($recebida),
                'quantidade_aceita' => $aceita === null ? null : Quantidade::formatar($aceita),
                'quantidade_devolvida' => Quantidade::formatar($devolvida),
            ];
        }

        $contas = $compra->contasPagar;
        if ($compra->contaPagar !== null && !$contas->contains('id', $compra->contaPagar->id)) {
            $contas->push($compra->contaPagar);
        }
        $contas = $contas->unique('id')->values();
        $resumoContas = [];
        foreach ($contas as $conta) {
            /** @var ContaPagar $conta */
            $pago = (float) $conta->pagamentosAtivos()->sum('valor');
            $resumoContas[] = [
                'id' => $conta->id,
                'status' => $conta->status,
                'valor' => (string) $conta->valor,
                'pago_total_conta' => number_format($pago, 2, '.', ''),
                'agrupada' => $conta->compras()->count() > 1,
            ];
        }

        return [
            'itens' => $itens,
            'contas' => $resumoContas,
            'possui_divergencia' => $compra->possuiDivergenciaConferencia(),
            'possui_devolucao' => $compra->devolucoes->isNotEmpty(),
            'valor_compra' => (string) $compra->valor_total,
        ];
    }
}
