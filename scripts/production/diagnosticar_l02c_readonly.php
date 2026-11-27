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

// REVISAO_DIRIGIDA_OS_3_61 - consultas SELECT, nenhuma escrita.
foreach ($lista as $os) {
    if (!in_array((int) $os->id, [3, 61], true)) continue;
    $veiculoId = (int) $os->veiculo_cliente_id;
    $clientes = DB::table('cliente_veiculo_cliente')
        ->where('veiculo_cliente_id', $veiculoId)
        ->distinct()->orderBy('cliente_id')->pluck('cliente_id')
        ->map(static fn ($v) => (int) $v)->all();
    $legado = DB::table('veiculos_clientes')
        ->where('id', $veiculoId)->value('cliente_id');
    printf(
        "REVISAO_OS=%d CLIENTES_DO_VEICULO=%s QTD_CLIENTES=%d CLIENTE_LEGADO=%s\n",
        $os->id, implode(',', $clientes), count($clientes),
        $legado === null ? 'NULO' : (string) $legado
    );

    $notas = DB::table('notas')
        ->where('cliente_id', $os->cliente_id)
        ->where('veiculo_cliente_id', $veiculoId)
        ->orderBy('id')->limit(10)
        ->get(['id', 'status', 'subtotal', 'desconto', 'total']);
    foreach ($notas as $nota) {
        $itens = DB::table('notas_itens')
            ->where('nota_id', $nota->id)->orderBy('id')
            ->get(['id', 'itemable_type', 'itemable_id', 'valor_total']);
        $soma = $itens->sum(static fn ($item) => (float) $item->valor_total);
        printf(
            "REVISAO_OS=%d NOTA_CANDIDATA_ID=%d STATUS_NOTA=%s SUBTOTAL=%s DESCONTO=%s TOTAL=%s QTD_ITENS=%d SOMA_ITENS=%s\n",
            $os->id, $nota->id,
            preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $nota->status),
            (string) $nota->subtotal, (string) $nota->desconto,
            (string) $nota->total, $itens->count(),
            number_format($soma, 2, '.', '')
        );
        foreach ($itens as $item) {
            printf(
                "REVISAO_OS=%d NOTA_ID=%d ITEM_ID=%d TIPO=%s REFERENCIA=%d TOTAL_ITEM=%s\n",
                $os->id, $nota->id, $item->id,
                preg_replace('/[^a-zA-Z0-9_\\\\]/', '', (string) $item->itemable_type),
                (int) $item->itemable_id, (string) $item->valor_total
            );
        }
    }
    // Inspeciona tipos antigos com o mesmo ID sem deduzir que sao O.S.
    $itensId = DB::table('notas_itens')
        ->where('itemable_id', (int) $os->id)
        ->orderBy('id')->limit(20)
        ->get(['id', 'nota_id', 'itemable_type']);
    foreach ($itensId as $item) {
        printf(
            "REVISAO_OS=%d ITEM_ID_IGUAL=%d NOTA_ID=%d TIPO=%s\n",
            $os->id, $item->id, $item->nota_id,
            preg_replace('/[^a-zA-Z0-9_\\\\]/', '', (string) $item->itemable_type)
        );
    }
}

echo "DADOS_NAO_MODIFICADOS=SIM\n";
