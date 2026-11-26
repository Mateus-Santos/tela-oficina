<?php

namespace App\Console\Commands;

use App\Models\OrdemServico;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Leitura estrita: inventário para migração de OS órfãs antes da remoção do legado. */
final class AuditarVinculosOsNotas extends Command
{
    protected $signature = 'notas:auditar-vinculos-os';
    protected $description = 'Audita O.S. sem Nota, O.S. duplicada e vínculos divergentes; não altera dados.';

    public function handle(): int
    {
        $tipo = OrdemServico::class;
        $base = DB::table('ordem_servicos as os');
        $total = (clone $base)->count();
        $semNota = (clone $base)->whereNotExists(function ($q) use ($tipo) {
            $q->selectRaw('1')->from('notas_itens as ni')
                ->where('ni.itemable_type', $tipo)
                ->whereColumn('ni.itemable_id', 'os.id');
        })->count();
        $duplicadas = DB::table('notas_itens')
            ->where('itemable_type', $tipo)
            ->groupBy('itemable_id')->havingRaw('COUNT(DISTINCT nota_id) > 1')
            ->get(['itemable_id'])->count();
        $incompatibilidades = DB::table('notas_itens as ni')
            ->join('ordem_servicos as os', 'os.id', '=', 'ni.itemable_id')
            ->join('notas as n', 'n.id', '=', 'ni.nota_id')
            ->where('ni.itemable_type', $tipo)
            ->where(function ($q) {
                $q->whereColumn('os.cliente_id', '<>', 'n.cliente_id')
                    ->orWhereColumn('os.veiculo_cliente_id', '<>', 'n.veiculo_cliente_id');
            })->count();
        $this->table(['Critério', 'Quantidade'], [
            ['O.S. cadastradas', $total],
            ['O.S. sem Nota', $semNota],
            ['O.S. ligadas a múltiplas Notas', $duplicadas],
            ['Vínculos cliente/veículo divergentes', $incompatibilidades],
        ]);
        $this->line('AUDITORIA_SOMENTE_LEITURA=SIM');
        return self::SUCCESS;
    }
}
