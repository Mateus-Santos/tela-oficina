<?php
// Diagnostico somente SELECT no banco real, executado no aplicativo antigo.
declare(strict_types=1);
chdir('/var/www/html');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

if (!app()->environment('production')) {
    throw new RuntimeException('BLOQUEADO: nao e producao');
}
if (DB::getDriverName() !== 'mysql'
    || (DB::selectOne('SELECT DATABASE() AS nome')->nome ?? null) !== 'jaime_automotivo') {
    throw new RuntimeException('BLOQUEADO: banco inesperado');
}
$tipo = 'App\\Models\\OrdemServico';
$base = DB::table('ordem_servicos as os')->whereNotExists(function ($query) use ($tipo) {
    $query->selectRaw('1')->from('notas_itens as ni')
        ->where('ni.itemable_type', $tipo)
        ->whereColumn('ni.itemable_id', 'os.id');
});
$total = (clone $base)->count();
echo "BANCO=jaime_automotivo\nDIAGNOSTICO_L02C=APENAS_SELECT\nOS_SEM_NOTA_TOTAL={$total}\n";
if ($total > 20) {
    echo "BLOQUEADO=MAIS_DE_20_ORFAS\n";
    exit(2);
}
$lista = (clone $base)->orderBy('os.id')->get([
    'os.id','os.status','os.cliente_id','os.veiculo_cliente_id',
    'os.setor_servico_id','os.valor','os.data_fechamento',
]);
foreach ($lista as $os) {
    $cliente = (int) ($os->cliente_id ?? 0);
    $veiculo = (int) ($os->veiculo_cliente_id ?? 0);
    $parValido = $cliente > 0 && $veiculo > 0 && DB::table('cliente_veiculo_cliente')
        ->where('cliente_id', $cliente)
        ->where('veiculo_cliente_id', $veiculo)->exists();
    $notas = DB::table('notas')->where('cliente_id', $cliente)->where('veiculo_cliente_id', $veiculo);
    $qtdNotas = (clone $notas)->count();
    $abertas = (clone $notas)->where('status', 'Aberto')->count();
    $finalizadas = (clone $notas)->whereIn('status', ['Finalizado', 'Concluido'])->count();
    printf("OS_ID=%d STATUS=%s CLIENTE_ID=%d VEICULO_ID=%d SETOR_ID=%d FECHADA=%s VALOR_VALIDO=%s PAR_CLIENTE_VEICULO=%s NOTAS_MESMO_PAR=%d NOTAS_ABERTAS=%d NOTAS_FINALIZADAS=%d\n",
        $os->id, preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $os->status),
        $cliente, $veiculo, (int) ($os->setor_servico_id ?? 0),
        $os->data_fechamento === null ? 'NAO' : 'SIM',
        is_numeric($os->valor) && (float) $os->valor >= 0 ? 'SIM' : 'NAO',
        $parValido ? 'SIM' : 'NAO', $qtdNotas, $abertas, $finalizadas);
}
echo "DADOS_NAO_MODIFICADOS=SIM\n";
