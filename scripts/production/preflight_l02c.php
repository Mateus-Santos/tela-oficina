<?php
// Preflight de release no APP ANTIGO, ANTES da troca de imagens.
// Executado pelo workflow via: docker exec -i sosmecanica-prod-app-1 php < script
// Apenas SELECT. Bloqueia dados incompativeis com L02C e NFe.

declare(strict_types=1);
chdir('/var/www/html');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (!app()->environment('production')) {
    throw new RuntimeException('Preflight bloqueado: nao e producao');
}
if (DB::getDriverName() !== 'mysql'
    || (DB::selectOne('SELECT DATABASE() AS nome')->nome ?? '') !== 'jaime_automotivo') {
    throw new RuntimeException('Preflight bloqueado: banco diferente de jaime_automotivo');
}

$tipo = 'App\\Models\\OrdemServico';
$contagens = [];
$contagens['os_sem_nota'] = DB::table('ordem_servicos as os')
    ->whereNotExists(function ($q) use ($tipo) {
        $q->selectRaw('1')->from('notas_itens as ni')
            ->where('ni.itemable_type', $tipo)
            ->whereColumn('ni.itemable_id', 'os.id');
    })->count();
$contagens['os_multiplos_itens'] = DB::table('notas_itens')
    ->where('itemable_type', $tipo)->select('itemable_id')
    ->groupBy('itemable_id')->havingRaw('COUNT(*) > 1')->get()->count();
$contagens['item_os_inexistente'] = DB::table('notas_itens as ni')
    ->where('ni.itemable_type', $tipo)->whereNotExists(function ($q) {
        $q->selectRaw('1')->from('ordem_servicos as os')
            ->whereColumn('os.id', 'ni.itemable_id');
    })->count();
$contagens['item_nota_inexistente'] = DB::table('notas_itens as ni')
    ->where('ni.itemable_type', $tipo)->whereNotExists(function ($q) {
        $q->selectRaw('1')->from('notas as n')->whereColumn('n.id', 'ni.nota_id');
    })->count();
$contagens['cliente_veiculo_divergente'] = DB::table('notas_itens as ni')
    ->join('ordem_servicos as os', 'os.id', '=', 'ni.itemable_id')
    ->join('notas as n', 'n.id', '=', 'ni.nota_id')
    ->where('ni.itemable_type', $tipo)
    ->where(function ($q) {
        $q->whereRaw('NOT (os.cliente_id <=> n.cliente_id)')
            ->orWhereRaw('NOT (os.veiculo_cliente_id <=> n.veiculo_cliente_id)');
    })->count();

$aplicada = DB::table('migrations')
    ->where('migration', '2026_11_25_160000_feux_l02c_os_nota_obrigatoria')->exists();
$osNota = Schema::hasColumn('ordem_servicos', 'nota_id');
$indice = Schema::hasColumn('notas_itens', 'feux_os_unica');
$contagens['l02c_schema_parcial'] = ($aplicada ? (!$osNota || !$indice) : ($osNota || $indice)) ? 1 : 0;

$nfeAplicada = DB::table('migrations')
    ->where('migration', '2026_11_21_210000_arquivar_chave_nf_de_compra_cancelada')->exists();
$nfeColuna = Schema::hasColumn('compras', 'chave_nf_cancelada');
$contagens['nfe_schema_parcial'] = ($nfeAplicada !== $nfeColuna) ? 1 : 0;

$contagens['nfe_chave_duplicada'] = DB::query()
    ->fromSub(DB::table('compras')->whereNotNull('chave_nf')
        ->select('chave_nf')->groupBy('chave_nf')->havingRaw('COUNT(*) > 1'), 'duplicadas')
    ->count();

foreach ($contagens as $nome => $valor) {
    printf("PREFLIGHT_%s=%d\n", strtoupper($nome), $valor);
}
if (array_sum($contagens) !== 0) {
    fwrite(STDERR, "BLOQUEADO=L02C_OU_NFE_INCOMPATIVEL; SEM_CORRECAO_AUTOMATICA\n");
    exit(49);
}
echo "PREFLIGHT_PRODUCAO_READONLY=APROVADO; NENHUMA_ESCRITA\n";
