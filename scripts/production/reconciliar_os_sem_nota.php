<?php
// Operacao pontual, auditavel, exclusiva para MySQL de producao.
// MODO=audit: somente SELECT; MODO=apply: cria 1 Nota e 1 NotasItem para
// cada O.S. elegivel, preservando status, cliente, veiculo e valor da O.S.
// Nao gera cobrancas nem altera estoque. Sem cancelamento automatico.
declare(strict_types=1);
chdir('/var/www/html');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\OrdemServico;
use App\Models\Etapa;
use App\Actions\Notas\CriarNota;

function bloqueio(string $mensagem): never {
    fwrite(STDERR, 'BLOQUEADO_ORFAS='.$mensagem."\n");
    exit(45);
}
$modo = getenv('SOS_ORFAS_MODO') ?: 'audit';
if (!in_array($modo, ['audit', 'apply'], true)) bloqueio('MODO_INVALIDO');
if (!app()->environment('production') || DB::getDriverName() !== 'mysql'
    || (DB::selectOne('SELECT DATABASE() AS nome')->nome ?? '') !== 'jaime_automotivo') {
    bloqueio('BANCO_OU_AMBIENTE_INESPERADO');
}
if (Schema::hasColumn('ordem_servicos', 'nota_id')
    || Schema::hasColumn('notas_itens', 'feux_os_unica')
    || DB::table('migrations')->where('migration',
        '2026_11_25_160000_feux_l02c_os_nota_obrigatoria')->exists()) {
    bloqueio('L02C_JA_APLICADA_OU_SCHEMA_PARCIAL');
}
$tipo = OrdemServico::class;
$orfas = static fn () => DB::table('ordem_servicos as os')
    ->whereNotExists(static function ($q) use ($tipo) {
        $q->selectRaw('1')->from('notas_itens as ni')
          ->where('ni.itemable_type', $tipo)->whereColumn('ni.itemable_id', 'os.id');
    });
$errosIntegridade = [
    'OS_MULTIPLOS_ITENS' => DB::table('notas_itens')
        ->where('itemable_type', $tipo)->select('itemable_id')
        ->groupBy('itemable_id')->havingRaw('COUNT(*) > 1')->exists(),
    'ITEM_OS_INEXISTENTE' => DB::table('notas_itens as ni')
        ->where('ni.itemable_type', $tipo)
        ->whereNotExists(static fn ($q) => $q->selectRaw('1')->from('ordem_servicos as os')
            ->whereColumn('os.id', 'ni.itemable_id'))->exists(),
    'ITEM_NOTA_INEXISTENTE' => DB::table('notas_itens as ni')
        ->where('ni.itemable_type', $tipo)
        ->whereNotExists(static fn ($q) => $q->selectRaw('1')->from('notas as n')
            ->whereColumn('n.id', 'ni.nota_id'))->exists(),
    'CLIENTE_VEICULO_DIVERGENTE' => DB::table('notas_itens as ni')
        ->join('ordem_servicos as os', 'os.id', '=', 'ni.itemable_id')
        ->join('notas as n', 'n.id', '=', 'ni.nota_id')
        ->where('ni.itemable_type', $tipo)
        ->where(static fn ($q) => $q
            ->whereRaw('NOT (os.cliente_id <=> n.cliente_id)')
            ->orWhereRaw('NOT (os.veiculo_cliente_id <=> n.veiculo_cliente_id)'))
        ->exists(),
];
foreach ($errosIntegridade as $nome => $erro) {
    if ($erro) bloqueio('OUTRA_INCONSISTENCIA_'.$nome);
}
$linhas = $orfas()->orderBy('os.id')->get([
    'os.id', 'os.status', 'os.cliente_id', 'os.veiculo_cliente_id',
    'os.valor', 'os.descricao', 'os.data_fechamento',
]);
$ids = $linhas->pluck('id')->map(static fn ($id) => (int) $id)->all();
echo 'ORFAS_TOTAL='.$linhas->count()."\n";
echo 'ORFAS_IDS='.implode(',', $ids)."\n";
if ($linhas->count() === 0) {
    echo "REGULARIZACAO_DESNECESSARIA=SIM\n";
    exit(0);
}
if ($linhas->count() !== 2) bloqueio('ESPERADAS_EXATAMENTE_2_ORFAS');
if (Etapa::query()->paraNota()->ativas()->iniciais()->count() !== 1) {
    bloqueio('ETAPA_INICIAL_NOTAS_NAO_UNICA');
}
$problemas = [];
foreach ($linhas as $os) {
    $erros = [];
    if ($os->status !== 'aberta' || $os->data_fechamento !== null) $erros[] = 'STATUS_OU_FECHAMENTO';
    if (!$os->cliente_id || !$os->veiculo_cliente_id) $erros[] = 'CLIENTE_VEICULO_AUSENTE';
    if (!is_numeric($os->valor) || (float) $os->valor <= 0) $erros[] = 'VALOR_NAO_POSITIVO';
    if ($os->cliente_id && $os->veiculo_cliente_id &&
        !DB::table('cliente_veiculo_cliente')
            ->where('cliente_id', $os->cliente_id)
            ->where('veiculo_cliente_id', $os->veiculo_cliente_id)->exists()) {
        $erros[] = 'SEM_VINCULO_CLIENTE_VEICULO';
    }
    echo 'OS_ID='.$os->id.' STATUS='.$os->status.' ELEGIVEL='.($erros ? 'NAO' : 'SIM')."\n";
    if ($erros) $problemas[] = $os->id.':'.implode('+', $erros);
}
if ($problemas) bloqueio('REVISAO_HUMANA_OBRIGATORIA:'.implode(',', $problemas));
if ($modo === 'audit') {
    echo "AUDITORIA_ORFAS=APROVADA_SEM_ESCRITA\n";
    exit(0);
}
if (getenv('SOS_ORFAS_CONFIRMAR') !== 'REGULARIZAR_2_OS'
    || getenv('SOS_ORFAS_IDS_ESPERADOS') !== implode(',', $ids)) {
    bloqueio('CONFIRMACAO_OU_LISTA_DE_IDS_DIVERGENTE');
}

// Revalida sob lock e grava atomicamente. Usamos Query Builder so para
// NotasItem, porque o modelo novo exige os.nota_id antes de a L02C cria-la.
// CriarNota oficial preserva historico/etapa inicial da Nota.
$criadas = DB::transaction(static function () use ($ids, $tipo, $orfas): array {
    $bloqueadas = DB::table('ordem_servicos')->whereIn('id', $ids)
        ->orderBy('id')->lockForUpdate()->get();
    if ($bloqueadas->count() !== 2) throw new RuntimeException('O.S. desapareceu durante reparo');
    $atuais = $orfas()->orderBy('os.id')->pluck('os.id')->map(static fn ($v) => (int) $v)->all();
    if ($atuais !== $ids) throw new RuntimeException('Lista de O.S. mudou; abortando');
    $result = [];
    foreach ($bloqueadas as $os) {
        if ($os->status !== 'aberta' || $os->data_fechamento !== null
            || !$os->cliente_id || !$os->veiculo_cliente_id
            || !is_numeric($os->valor) || (float) $os->valor <= 0) {
            throw new RuntimeException('O.S. mudou ou deixou de ser elegivel');
        }
        if (!DB::table('cliente_veiculo_cliente')
            ->where('cliente_id', $os->cliente_id)
            ->where('veiculo_cliente_id', $os->veiculo_cliente_id)->exists()) {
            throw new RuntimeException('Vinculo cliente/veiculo mudou');
        }
        if (DB::table('notas_itens')->where('itemable_type', $tipo)
            ->where('itemable_id', $os->id)->exists()) {
            throw new RuntimeException('O.S. ja vinculada; abortando');
        }
        $nota = app(CriarNota::class)->execute([
            'cliente_id' => (int) $os->cliente_id,
            'veiculo_cliente_id' => (int) $os->veiculo_cliente_id,
            'itens' => [],
        ]);
        if ($nota->status !== 'Aberto'
            || (int) $nota->cliente_id !== (int) $os->cliente_id
            || (int) $nota->veiculo_cliente_id !== (int) $os->veiculo_cliente_id) {
            throw new RuntimeException('Nota canonica diferente da O.S.');
        }
        $valor = number_format((float) $os->valor, 2, '.', '');
        DB::table('notas_itens')->insert([
            'nota_id' => $nota->id,
            'itemable_type' => $tipo,
            'itemable_id' => $os->id,
            'descricao' => mb_substr(trim((string) $os->descricao) ?: ('Servico da O.S. #'.$os->id), 0, 220),
            'quantidade' => 1,
            'valor_unitario' => $valor,
            'desconto' => '0.00',
            'valor_total' => $valor,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $nota->update([
            'subtotal' => $valor,
            'desconto' => '0.00',
            'total' => $valor,
            'observacoes' => 'Regularizacao historica automatizada de O.S. sem Nota; GitHub Actions; sem cancelamento, sem movimentar financeiro ou estoque.',
        ]);
        if (DB::table('notas_itens')->where('nota_id', $nota->id)
            ->where('itemable_type', $tipo)->where('itemable_id', $os->id)->count() !== 1
            || (int) round((float) $nota->fresh()->total * 100) !==
                (int) round((float) $os->valor * 100)) {
            throw new RuntimeException('Totais ou vinculos nao conferem');
        }
        $result[] = ['os' => (int) $os->id, 'nota' => (int) $nota->id];
    }
    if ($orfas()->exists()) throw new RuntimeException('O.S. orfa remanescente');
    return $result;
}, 3);
foreach ($criadas as $par) {
    echo 'VINCULO_CRIADO=OS_'.$par['os'].'_NOTA_'.$par['nota']."\n";
}
echo "REGULARIZACAO=CONCLUIDA_SEM_CANCELAR_OS\n";
