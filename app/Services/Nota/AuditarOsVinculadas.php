<?php

namespace App\Services\Nota;

use App\Actions\OrdemServico\FinalizarOrdemServico;
use App\Models\Nota;
use App\Models\NotasItem;
use App\Models\OrdemServico;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Audita o legado sem escrita. A correção exige seleção explícita de UMA nota.
 * Nunca reabre/cancela OS, notas, parcelas, recebimentos ou estoque.
 */
class AuditarOsVinculadas
{
    public function __construct(private FinalizarOrdemServico $finalizarOs)
    {
    }

    /** @return array<int, array<string, int|string|null>> */
    public function auditar(?int $notaId = null, int $limite = 2000): array
    {
        $itens = NotasItem::query()
            ->where('itemable_type', OrdemServico::class)
            ->when($notaId !== null, fn ($q) => $q->where('nota_id', $notaId))
            ->orderBy('id')->limit($limite)->get();

        if ($itens->isEmpty()) {
            return [];
        }

        $ids = $itens->pluck('itemable_id')->unique()->all();
        $duplicadas = DB::table('notas_itens')
            ->where('itemable_type', OrdemServico::class)
            ->whereIn('itemable_id', $ids)
            ->groupBy('itemable_id')->havingRaw('COUNT(*) > 1')
            ->pluck('itemable_id')->map(fn ($id) => (int) $id)->all();
        $duplicadas = array_fill_keys($duplicadas, true);
        $notas = Nota::query()->whereIn('id', $itens->pluck('nota_id')->all())->get()->keyBy('id');
        $ordens = OrdemServico::query()->with('etapa')->whereIn('id', $ids)->get()->keyBy('id');

        $resultado = [];
        foreach ($itens as $item) {
            $nota = $notas->get($item->nota_id);
            $os = $ordens->get($item->itemable_id);
            $situacao = $this->classificar($nota, $os, isset($duplicadas[(int) $item->itemable_id]));
            $resultado[] = [
                'item_id' => (int) $item->id,
                'nota_id' => (int) $item->nota_id,
                'os_id' => (int) $item->itemable_id,
                'status_nota' => $nota?->status,
                'status_os' => $os?->status,
                'situacao' => $situacao,
            ];
        }

        return $resultado;
    }

    /**
     * Corrige exclusivamente OS ABERTAS de uma nota já finalizada, de forma
     * transacional, preservando a Action oficial e seu histórico de etapas.
     */
    public function corrigirNota(int $notaId): int
    {
        return DB::transaction(function () use ($notaId): int {
            $nota = Nota::query()->lockForUpdate()->findOrFail($notaId);
            if (!in_array($nota->status, ['Finalizado', 'Concluido'], true)) {
                throw new InvalidArgumentException('A correção exige Nota finalizada.');
            }

            $itens = NotasItem::query()
                ->where('nota_id', $notaId)
                ->where('itemable_type', OrdemServico::class)
                ->orderBy('itemable_id')->lockForUpdate()->get();
            if ($itens->isEmpty()) {
                throw new InvalidArgumentException('Nota sem OS vinculada.');
            }

            $ids = $itens->pluck('itemable_id')->map(fn ($id) => (int) $id)->all();
            $repetidas = DB::table('notas_itens')
                ->where('itemable_type', OrdemServico::class)
                ->whereIn('itemable_id', $ids)
                ->groupBy('itemable_id')->havingRaw('COUNT(*) > 1')->exists();
            if ($repetidas) {
                throw new InvalidArgumentException('OS com vinculo duplicado. Revisao manual obrigatoria.');
            }

            $ordens = OrdemServico::query()->with('etapa')
                ->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($ordens->count() !== count($ids)) {
                throw new InvalidArgumentException('Existe OS ausente no vinculo.');
            }

            // Validar todo o conjunto antes da primeira alteração.
            foreach ($ids as $id) {
                $os = $ordens->get($id);
                if ($this->vinculoInconsistente($nota, $os)) {
                    throw new InvalidArgumentException("OS #{$id} com cliente ou veiculo divergente.");
                }
                if (!in_array($os->status, ['aberta', 'finalizada'], true)) {
                    throw new InvalidArgumentException("OS #{$id} em estado bloqueado.");
                }
                if ($os->status === 'finalizada' && $os->etapa?->tipo !== 'final') {
                    throw new InvalidArgumentException("OS #{$id} finalizada com etapa divergente. Revisao manual.");
                }
            }

            $corrigidas = 0;
            foreach ($ids as $id) {
                $os = $ordens->get($id);
                if ($os->status !== 'aberta') {
                    continue;
                }
                $this->finalizarOs->execute($os);
                $corrigidas++;
            }

            return $corrigidas;
        }, 3);
    }

    private function classificar(?Nota $nota, ?OrdemServico $os, bool $duplicada): string
    {
        if ($duplicada) return 'vinculo_duplicado';
        if (!$nota) return 'nota_ausente';
        if (!$os) return 'os_ausente';
        if ($this->vinculoInconsistente($nota, $os)) return 'cliente_ou_veiculo_divergente';
        if (in_array($nota->status, ['Finalizado', 'Concluido'], true)) {
            if ($os->status === 'aberta') return 'corrigivel_com_confirmacao';
            if ($os->status === 'finalizada' && $os->etapa?->tipo === 'final') return 'regular';
            return 'revisao_manual';
        }
        if ($nota->status === 'Aberto' && $os->status === 'aberta') return 'regular';
        return 'revisao_manual';
    }

    private function vinculoInconsistente(Nota $nota, OrdemServico $os): bool
    {
        return ($nota->cliente_id !== null && $os->cliente_id !== null
                && (int) $nota->cliente_id !== (int) $os->cliente_id)
            || ($nota->veiculo_cliente_id !== null && $os->veiculo_cliente_id !== null
                && (int) $nota->veiculo_cliente_id !== (int) $os->veiculo_cliente_id);
    }
}
