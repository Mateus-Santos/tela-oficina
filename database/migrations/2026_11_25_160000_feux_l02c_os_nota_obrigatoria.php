<?php

use App\Models\OrdemServico;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Apenas no clone FEUX, depois de publicar os novos produtores. */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('L02C requer MySQL para indice condicional de O.S.');
        }
        $tipo = OrdemServico::class;
        $vinculos = DB::table('notas_itens')
            ->select('itemable_id')
            ->where('itemable_type', $tipo)
            ->groupBy('itemable_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
        if ($vinculos) {
            throw new RuntimeException('L02C bloqueada: O.S. vinculada a multiplas Notas.');
        }
        $orfos = DB::table('ordem_servicos as os')
            ->whereNotExists(function ($q) use ($tipo) {
                $q->selectRaw('1')->from('notas_itens as ni')
                    ->where('ni.itemable_type', $tipo)
                    ->whereColumn('ni.itemable_id', 'os.id');
            })->exists();
        if ($orfos) {
            throw new RuntimeException('L02C bloqueada: existem O.S. sem Nota.');
        }
        $inexistentes = DB::table('notas_itens as ni')
            ->where('ni.itemable_type', $tipo)
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')->from('ordem_servicos as os')
                    ->whereColumn('os.id', 'ni.itemable_id');
            })->exists();
        if ($inexistentes) {
            throw new RuntimeException('L02C bloqueada: item O.S. sem registro correspondente.');
        }
        $divergentes = DB::table('notas_itens as ni')
            ->join('ordem_servicos as os', 'os.id', '=', 'ni.itemable_id')
            ->join('notas as n', 'n.id', '=', 'ni.nota_id')
            ->where('ni.itemable_type', $tipo)
            ->where(function ($q) {
                $q->whereColumn('os.cliente_id', '!=', 'n.cliente_id')
                    ->orWhereColumn('os.veiculo_cliente_id', '!=', 'n.veiculo_cliente_id');
            })->exists();
        if ($divergentes) {
            throw new RuntimeException('L02C bloqueada: cliente ou veiculo divergente.');
        }

        Schema::table('ordem_servicos', function (Blueprint $table): void {
            $table->foreignId('nota_id')->nullable()->after('cliente_id')
                ->constrained(table: 'notas', indexName: 'feux_os_nota_fk')->restrictOnDelete();
        });

        DB::table('ordem_servicos as os')->join('notas_itens as ni', function ($join) use ($tipo) {
            $join->on('ni.itemable_id', '=', 'os.id')->where('ni.itemable_type', '=', $tipo);
        })->update(['os.nota_id' => DB::raw('ni.nota_id')]);

        if (DB::table('ordem_servicos')->whereNull('nota_id')->exists()) {
            throw new RuntimeException('L02C: backfill incompleto; bloqueado antes de NOT NULL.');
        }
        DB::statement('ALTER TABLE `ordem_servicos` MODIFY `nota_id` BIGINT UNSIGNED NOT NULL');

        // Produtos podem compor varias Notas; apenas O.S. e globalmente unica.
        $sqlTipo = DB::getPdo()->quote($tipo);
        DB::statement('ALTER TABLE `notas_itens` ADD COLUMN `feux_os_unica` BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `itemable_type` = '.$sqlTipo.' THEN `itemable_id` ELSE NULL END) STORED');
        DB::statement('CREATE UNIQUE INDEX `feux_os_item_unico` ON `notas_itens` (`feux_os_unica`)');
    }

    public function down(): void
    {
        if (Schema::hasColumn('notas_itens', 'feux_os_unica')) {
            DB::statement('DROP INDEX `feux_os_item_unico` ON `notas_itens`');
            DB::statement('ALTER TABLE `notas_itens` DROP COLUMN `feux_os_unica`');
        }
        if (Schema::hasColumn('ordem_servicos', 'nota_id')) {
            Schema::table('ordem_servicos', function (Blueprint $table): void {
                $table->dropForeign('feux_os_nota_fk');
                $table->dropColumn('nota_id');
            });
        }
    }
};
