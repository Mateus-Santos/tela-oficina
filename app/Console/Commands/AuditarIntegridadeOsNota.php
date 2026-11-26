<?php

namespace App\Console\Commands;

use App\Models\OrdemServico;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AuditarIntegridadeOsNota extends Command
{
    protected $signature = 'notas:auditar-integridade-os';
    protected $description = 'Confere FK canonica e linha comercial unica de cada O.S. (somente leitura).';

    public function handle(): int
    {
        if (!Schema::hasColumn('ordem_servicos', 'nota_id')) {
            $this->error('MIGRATION_L02C_NAO_APLICADA');
            return self::FAILURE;
        }
        $tipo = OrdemServico::class;
        $os = DB::table('ordem_servicos')->count();
        $semNota = DB::table('ordem_servicos')->whereNull('nota_id')->count();
        $semItem = DB::table('ordem_servicos as os')
            ->whereNotExists(function ($q) use ($tipo) {
                $q->selectRaw('1')->from('notas_itens as ni')
                    ->where('ni.itemable_type', $tipo)
                    ->whereColumn('ni.itemable_id', 'os.id')
                    ->whereColumn('ni.nota_id', 'os.nota_id');
            })->count();
        $divergentes = DB::table('notas_itens as ni')
            ->join('ordem_servicos as os', 'ni.itemable_id', '=', 'os.id')
            ->where('ni.itemable_type', $tipo)
            ->whereColumn('ni.nota_id', '!=', 'os.nota_id')->count();
        $repetidas = DB::table('notas_itens')->select('itemable_id')
            ->where('itemable_type', $tipo)->groupBy('itemable_id')
            ->havingRaw('COUNT(*) > 1')->get()->count();
        $this->table(['Verificacao L02C', 'Quantidade'], [
            ['O.S. cadastradas', $os],
            ['O.S. sem FK de Nota', $semNota],
            ['O.S. sem item comercial correspondente', $semItem],
            ['Itens associados a Nota divergente', $divergentes],
            ['O.S. com item duplicado', $repetidas],
        ]);
        if ($semNota || $semItem || $divergentes || $repetidas) {
            $this->error('INTEGRIDADE_OS_NOTA=REPROVADA');
            return self::FAILURE;
        }
        $this->info('INTEGRIDADE_OS_NOTA=APROVADA');
        return self::SUCCESS;
    }
}
