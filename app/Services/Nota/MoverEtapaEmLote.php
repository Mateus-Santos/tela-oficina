<?php

namespace App\Services\Nota;

use App\Actions\Notas\AlterarEtapaNota;
use App\Models\Etapa;
use App\Models\Nota;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Movimentação operacional em lote. NUNCA finaliza Notas e NUNCA mexe no financeiro.
 * O destino final exige FinalizarNota individual (estoque, O.S., parcelas e conta).
 */
final class MoverEtapaEmLote
{
    private const LIMITE = 100;

    public function __construct(private AlterarEtapaNota $alterarEtapaNota) {}

    private function etapas(int $origemId, int $destinoId): array
    {
        if ($origemId === $destinoId) {
            throw new InvalidArgumentException('Selecione duas etapas diferentes.');
        }
        $origem = Etapa::query()->paraNota()->ativas()->find($origemId);
        $destino = Etapa::query()->paraNota()->ativas()->find($destinoId);
        if (! $origem || ! $destino) {
            throw new InvalidArgumentException('Etapa de origem ou destino inativa ou incompatível com Notas.');
        }
        if ($origem->tipo === 'final' || $destino->tipo === 'final') {
            throw new InvalidArgumentException('A etapa final não aceita movimentação em lote. Finalize as Notas individualmente.');
        }
        return [$origem, $destino];
    }

    private function notas(int $origemId, bool $bloquear = false)
    {
        $query = Nota::query()->where('status', 'Aberto')
            ->where('etapa_id', $origemId)
            ->orderBy('id')
            ->limit(self::LIMITE + 1);
        if ($bloquear) $query->lockForUpdate();
        return $query->get(['id', 'updated_at']);
    }

    private function assinatura($notas): string
    {
        return hash('sha256', $notas->map(static fn (Nota $nota) =>
            $nota->id.'@'.($nota->updated_at?->format('Y-m-d H:i:s.u') ?? '')
        )->implode('|'));
    }

    public function previsualizar(int $origemId, int $destinoId): array
    {
        [$origem, $destino] = $this->etapas($origemId, $destinoId);
        $contagem = Nota::query()->where('status', 'Aberto')->where('etapa_id', $origemId)->count();
        $notas = $contagem <= self::LIMITE ? $this->notas($origemId) : collect();
        return [
            'origem' => $origem->nome,
            'destino' => $destino->nome,
            'contagem' => $contagem,
            'limite' => self::LIMITE,
            'excede_limite' => $contagem > self::LIMITE,
            'assinatura' => $contagem <= self::LIMITE ? $this->assinatura($notas) : null,
        ];
    }

    public function executar(int $origemId, int $destinoId, string $assinatura, ?int $userId): array
    {
        return DB::transaction(function () use ($origemId, $destinoId, $assinatura, $userId) {
            [, $destino] = $this->etapas($origemId, $destinoId);
            $notas = $this->notas($origemId, true);
            if ($notas->count() === 0) {
                throw new InvalidArgumentException('Nenhuma Nota aberta nesta etapa. Atualize a prévia.');
            }
            if ($notas->count() > self::LIMITE) {
                throw new InvalidArgumentException('Mais de 100 Notas na etapa. Divida a operação em lotes menores.');
            }
            if (! hash_equals($this->assinatura($notas), $assinatura)) {
                throw new InvalidArgumentException('O conjunto de Notas mudou desde a prévia. Confira novamente antes de movimentar.');
            }
            foreach ($notas as $nota) {
                // Action oficial registra o histórico e recusa Nota não-aberta.
                $this->alterarEtapaNota->execute($nota, $destino, $userId,
                    'Movimentação operacional em lote pela Central Vue.');
            }
            return ['movidas' => $notas->count(), 'destino_etapa_id' => $destinoId];
        }, 3);
    }
}
