<?php

namespace App\Console\Commands;

use App\Models\Etapa;
use App\Models\Nota;
use App\Models\NotaEtapaHistorico;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Auditoria de Notas já finalizadas fora da última etapa. Padrão: somente leitura. */
final class AuditarEtapasFinalizadas extends Command
{
    protected $signature = 'notas:auditar-etapas-finalizadas {--aplicar : Alinhar somente etapa/histórico de Notas já finalizadas}';
    protected $description = 'Verifica finalizadas fora da etapa final; correção opcional sem movimentação financeira';

    public function handle(): int
    {
        $etapas = Etapa::query()->paraNota()->ativas()->finais()->get();
        if ($etapas->count() !== 1) {
            $this->error('BLOQUEADO: configure exatamente uma etapa final ativa para Notas.');
            return self::FAILURE;
        }
        $final = $etapas->first();
        $pendentes = static fn () => Nota::query()
            ->whereIn('status', ['Finalizado','Concluido'])
            ->where(function ($q) use ($final) {
                $q->whereNull('etapa_id')->orWhere('etapa_id', '!=', $final->id);
            });
        $total = $pendentes()->count();
        $this->line('ETAPA_FINAL_ID='.$final->id);
        $this->line('FINALIZADAS_FORA_DA_ETAPA_FINAL='.$total);
        if (! $this->option('aplicar')) {
            $this->line('SOMENTE_LEITURA=SIM; PARA_CORRIGIR NO CLONE USE --aplicar');
            return self::SUCCESS;
        }
        DB::transaction(function () use ($pendentes, $final) {
            foreach ($pendentes()->lockForUpdate()->with('etapa')->orderBy('id')->get() as $nota) {
                $origem = $nota->etapa;
                $nota->update(['etapa_id' => $final->id]);
                NotaEtapaHistorico::create([
                    'nota_id' => $nota->id,
                    'etapa_origem_id' => $origem?->id,
                    'etapa_destino_id' => $final->id,
                    'etapa_origem_nome' => $origem?->nome,
                    'etapa_destino_nome' => $final->nome,
                    'user_id' => null,
                    'motivo' => 'Regularização de etapa final de Nota já finalizada (sem efeitos financeiros).',
                ]);
            }
        });
        $this->line('CORRIGIDAS='.$total);
        $restantes = $pendentes()->count();
        $this->line('RESTANTES='.$restantes);
        return $restantes === 0 ? self::SUCCESS : self::FAILURE;
    }
}
