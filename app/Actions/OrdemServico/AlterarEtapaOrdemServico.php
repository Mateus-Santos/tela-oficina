<?php

namespace App\Actions\OrdemServico;

use App\Models\Etapa;
use App\Models\OrdemServico;
use App\Models\OrdemServicoEtapaHistorico;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AlterarEtapaOrdemServico
{
    public function execute(
        OrdemServico $ordemServico,
        Etapa $etapa,
        ?int $userId = null,
        ?string $motivo = null
    ): OrdemServico {
        return DB::transaction(
            function () use (
                $ordemServico,
                $etapa,
                $userId,
                $motivo
            ) {
                $ordemServico =
                    OrdemServico::query()
                        ->lockForUpdate()
                        ->with('etapa')
                        ->findOrFail(
                            $ordemServico->id
                        );

                $etapa =
                    Etapa::query()
                        ->findOrFail(
                            $etapa->id
                        );

                /*
                 * =====================================================
                 * STATUS DA O.S.
                 * =====================================================
                 *
                 * A etapa representa o andamento operacional.
                 *
                 * O.S. finalizada ou cancelada não pode continuar
                 * circulando entre etapas.
                 */
                if (
                    $ordemServico->status
                    !== 'aberta'
                ) {
                    throw new InvalidArgumentException(
                        'Somente ordens de serviço abertas podem mudar de etapa.'
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
                if (
                    ! $etapa
                        ->aplica_ordem_servico
                ) {
                    throw new InvalidArgumentException(
                        'A etapa selecionada não pode ser utilizada em Ordens de Serviço.'
                    );
                }

                /*
                 * =====================================================
                 * MESMA ETAPA
                 * =====================================================
                 */
                if (
                    (int) $ordemServico->etapa_id
                    === (int) $etapa->id
                ) {
                    return $ordemServico;
                }

                $etapaOrigem =
                    $ordemServico->etapa;

                /*
                 * =====================================================
                 * ALTERAÇÃO
                 * =====================================================
                 */
                $ordemServico->update([
                    'etapa_id' => $etapa->id,
                ]);

                /*
                 * =====================================================
                 * HISTÓRICO
                 * =====================================================
                 */
                OrdemServicoEtapaHistorico::create([
                    'ordem_servico_id' => $ordemServico->id,

                    'etapa_origem_id' => $etapaOrigem?->id,

                    'etapa_destino_id' => $etapa->id,

                    'etapa_origem_nome' => $etapaOrigem?->nome,

                    'etapa_destino_nome' => $etapa->nome,

                    'user_id' => $userId,

                    'motivo' => $motivo,
                ]);

                return $ordemServico->fresh([
                    'etapa',
                    'historicoEtapas',
                ]);
            }
        );
    }
}
