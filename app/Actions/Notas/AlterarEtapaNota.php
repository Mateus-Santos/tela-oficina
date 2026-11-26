<?php

namespace App\Actions\Notas;

use App\Models\Etapa;
use App\Models\Nota;
use App\Models\NotaEtapaHistorico;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AlterarEtapaNota
{
    public function execute(
        Nota $nota,
        Etapa $etapa,
        ?int $userId = null,
        ?string $motivo = null,
        bool $finalizando = false
    ): Nota {
        return DB::transaction(
            function () use (
                $nota,
                $etapa,
                $userId,
                $motivo,
                $finalizando
            ) {
                $etapa = Etapa::query()
                    ->lockForUpdate()
                    ->findOrFail($etapa->id);

                $nota = Nota::query()
                    ->lockForUpdate()
                    ->with('etapa')
                    ->findOrFail($nota->id);

                /*
                 * =====================================================
                 * STATUS DA NOTA
                 * =====================================================
                 *
                 * Etapa representa somente o andamento operacional.
                 *
                 * Uma Nota finalizada ou cancelada está encerrada
                 * sistemicamente e não pode mais trocar de etapa.
                 */
                if (
                    $nota->status
                    !== 'Aberto'
                ) {
                    throw new InvalidArgumentException(
                        'Somente notas com status Aberto podem mudar de etapa.'
                    );
                }

                /*
                 * =====================================================
                 * ETAPA ATIVA
                 * =====================================================
                 */
                if (! $etapa->ativo) {
                    throw new InvalidArgumentException(
                        'A etapa selecionada está inativa.'
                    );
                }

                /*
                 * =====================================================
                 * COMPATIBILIDADE
                 * =====================================================
                 */
                if (! $etapa->aplica_nota) {
                    throw new InvalidArgumentException(
                        'A etapa selecionada não pode ser utilizada em Notas.'
                    );
                }

                /* L03N: etapa final só pode ser alcançada pela Action
                 * FinalizarNota, que valida estoque, O.S., parcelas e conta. */
                if ($etapa->tipo === 'final' && ! $finalizando) {
                    throw new InvalidArgumentException(
                        'A última etapa exige finalização real. Utilize Finalizar Nota na Central.'
                    );
                }

                /*
                 * =====================================================
                 * MESMA ETAPA
                 * =====================================================
                 *
                 * Não atualiza o banco e não cria histórico duplicado.
                 */
                if (
                    (int) $nota->etapa_id
                    === (int) $etapa->id
                ) {
                    return $nota;
                }

                $etapaOrigem =
                    $nota->etapa;

                /*
                 * =====================================================
                 * ALTERAÇÃO
                 * =====================================================
                 */
                $nota->update([
                    'etapa_id' => $etapa->id,
                ]);

                /*
                 * =====================================================
                 * HISTÓRICO
                 * =====================================================
                 */
                NotaEtapaHistorico::create([
                    'nota_id' => $nota->id,

                    'etapa_origem_id' => $etapaOrigem?->id,

                    'etapa_destino_id' => $etapa->id,

                    'etapa_origem_nome' => $etapaOrigem?->nome,

                    'etapa_destino_nome' => $etapa->nome,

                    'user_id' => $userId,

                    'motivo' => $motivo,
                ]);

                return $nota->fresh([
                    'etapa',
                    'historicoEtapas',
                ]);
            }
        );
    }
}
