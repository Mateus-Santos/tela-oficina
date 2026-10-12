<?php

namespace App\Actions\OrdemServico;

use App\Models\Etapa;
use App\Models\OrdemServico;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinalizarOrdemServico
{
    public function __construct(
        private AlterarEtapaOrdemServico $alterarEtapa
    ) {}

    public function execute(
        OrdemServico $ordemServico,
        ?int $userId = null
    ): OrdemServico {
        return DB::transaction(function () use (
            $ordemServico,
            $userId
        ) {
            $ordemServico = OrdemServico::query()
                ->lockForUpdate()
                ->with('etapa')
                ->findOrFail($ordemServico->id);

            if ($ordemServico->status !== 'aberta') {
                throw new InvalidArgumentException(
                    'Somente ordens de serviço abertas podem ser finalizadas.'
                );
            }

            $etapasFinais = Etapa::query()
                ->paraOrdemServico()
                ->ativas()
                ->finais()
                ->get();

            if ($etapasFinais->count() !== 1) {
                throw new InvalidArgumentException(
                    'A Ordem de Serviço precisa possuir exatamente uma etapa final ativa configurada.'
                );
            }

            $etapaFinal = $etapasFinais->first();

            $ordemServico = $this->alterarEtapa->execute(
                $ordemServico,
                $etapaFinal,
                $userId,
                'Movimentação automática pela finalização da Ordem de Serviço.'
            );

            $ordemServico->update([
                'status' => 'finalizada',

                'data_fechamento' => $ordemServico->data_fechamento
                    ?? now(),
            ]);

            return $ordemServico->fresh([
                'etapa',
                'historicoEtapas',
            ]);
        });
    }
}
