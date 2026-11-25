<?php

namespace App\Actions\Etapas;

use App\Models\Etapa;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Usar dentro da transacao que bloqueou a etapa de origem (FOR UPDATE).
 * Reposiciona TODOS os registros vinculados, sem alterar seus status ou valores.
 */
class RealocarVinculosEtapa
{
    public function execute(
        Etapa $origem,
        bool $moverNotas,
        bool $moverOrdensServico,
        ?int $usuarioId
    ): array {
        $resultado = ['notas' => 0, 'os' => 0];

        if ($moverNotas) {
            $destino = $this->inicial('aplica_nota', $origem);
            $resultado['notas'] = $this->mover(
                'notas',
                'nota_etapa_historicos',
                'nota_id',
                $origem,
                $destino,
                $usuarioId
            );
        }

        if ($moverOrdensServico) {
            $destino = $this->inicial('aplica_ordem_servico', $origem);
            $resultado['os'] = $this->mover(
                'ordem_servicos',
                'ordem_servico_etapa_historicos',
                'ordem_servico_id',
                $origem,
                $destino,
                $usuarioId
            );
        }

        return $resultado;
    }

    private function inicial(string $aplicabilidade, Etapa $origem): Etapa
    {
        $etapas = Etapa::query()
            ->where('tipo', 'inicial')
            ->where('ativo', true)
            ->where($aplicabilidade, true)
            ->lockForUpdate()
            ->get();

        if ($etapas->count() !== 1 || $etapas->first()->id === $origem->id) {
            throw ValidationException::withMessages([
                'etapa' => 'Realocacao bloqueada: deve existir exatamente uma etapa inicial ativa para cada fluxo.',
            ]);
        }

        return $etapas->first();
    }

    private function mover(
        string $tabela,
        string $historico,
        string $colunaId,
        Etapa $origem,
        Etapa $destino,
        ?int $usuarioId
    ): int {
        $total = 0;

        do {
            $ids = DB::table($tabela)
                ->where('etapa_id', $origem->id)
                ->orderBy('id')
                ->limit(200)
                ->lockForUpdate()
                ->pluck('id')
                ->all();

            if ($ids === []) {
                break;
            }

            $agora = now();
            $alterados = DB::table($tabela)
                ->whereIn('id', $ids)
                ->where('etapa_id', $origem->id)
                ->update(['etapa_id' => $destino->id, 'updated_at' => $agora]);

            if ($alterados !== count($ids)) {
                throw new RuntimeException('Realocacao interrompida: quantidade divergente.');
            }

            $linhas = [];
            foreach ($ids as $id) {
                $linhas[] = [
                    $colunaId => $id,
                    'etapa_origem_id' => $origem->id,
                    'etapa_destino_id' => $destino->id,
                    'etapa_origem_nome' => $origem->nome,
                    'etapa_destino_nome' => $destino->nome,
                    'user_id' => $usuarioId,
                    'motivo' => 'Realocacao administrativa: etapa de origem desativada ou retirada do fluxo.',
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ];
            }

            DB::table($historico)->insert($linhas);
            $total += count($ids);
        } while (true);

        if (DB::table($tabela)->where('etapa_id', $origem->id)->exists()) {
            throw new RuntimeException('Realocacao incompleta: registros restantes na etapa.');
        }

        return $total;
    }
}
