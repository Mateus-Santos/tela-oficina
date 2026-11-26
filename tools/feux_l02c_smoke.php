<?php
/** Ensaio do clone. Execute com docker exec -i --user 1337:1337, SOMENTE na FEUX. */
declare(strict_types=1);
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\Nota;
use App\Models\NotasItem;
use App\Models\OrdemServico;
use Illuminate\Support\Facades\DB;

$db = (string) DB::connection()->getDatabaseName();
if (DB::getDriverName() !== 'mysql' || !preg_match('/^feux_dados_dev_\d{8}_\d{6}_\d+$/', $db)) {
    throw new RuntimeException('Smoke bloqueado: banco fora do clone FEUX');
}
$before = [DB::table('ordem_servicos')->count(), DB::table('notas')->count(), DB::table('notas_itens')->count()];
if (DB::table('ordem_servicos')->whereNull('nota_id')->exists()) {
    throw new RuntimeException('O.S. sem FK antes do smoke');
}
$exemplo = DB::table('ordem_servicos as os')
    ->join('cliente_veiculo_cliente as cv', function ($join) {
        $join->on('cv.cliente_id','=','os.cliente_id')->on('cv.veiculo_cliente_id','=','os.veiculo_cliente_id');
    })
    ->select(['os.cliente_id','os.veiculo_cliente_id','os.setor_servico_id'])
    ->first();
if (! $exemplo) {
    throw new RuntimeException('Sem cliente-veiculo-setor valido para ensaio; bloqueado com segurança.');
}
DB::beginTransaction();
$falha = null;
try {
    $os = app(\App\Actions\OrdemServico\CriarOrdemServico::class)->execute([
        'cliente_id' => (int)$exemplo->cliente_id,
        'veiculo_cliente_id' => (int)$exemplo->veiculo_cliente_id,
        'setor_servico_id' => (int)$exemplo->setor_servico_id,
        'descricao' => 'FEUX L02C - TESTE COM ROLLBACK',
        'valor' => '1,23',
    ]);
    if (!$os->nota_id || !Nota::query()->whereKey($os->nota_id)->exists()) {
        throw new RuntimeException('CriarOrdemServico nao criou Nota');
    }
    if (NotasItem::query()->where('nota_id',$os->nota_id)->where('itemable_type', OrdemServico::class)
        ->where('itemable_id',$os->id)->count() !== 1) {
        throw new RuntimeException('O.S. criada sem item comercial unico');
    }
    echo "APROVADO=CRIAR_OS_PUBLICA_COM_NOTA_E_ITEM\n";

    // Duplicar comercialmente a mesma O.S. precisa falhar pelo indice unico.
    $duplicacaoBloqueada = false;
    try {
        NotasItem::create([
            'nota_id'=>$os->nota_id,'itemable_type'=>OrdemServico::class,
            'itemable_id'=>$os->id,'descricao'=>'duplicado',
            'quantidade'=>1,'valor_unitario'=>1.23,'desconto'=>0,'valor_total'=>1.23,
        ]);
    } catch (\Illuminate\Database\QueryException $e) {
        $duplicacaoBloqueada = true;
    }
    if (!$duplicacaoBloqueada) {
        throw new RuntimeException('Indice unico nao bloqueou duplicacao');
    }
    echo "APROVADO=INDICE_OS_UNICA\n";

    // FK NOT NULL impede insercao direta de O.S. orfa.
    $nullBloqueado = false;
    try {
        DB::table('ordem_servicos')->insert([
            'nota_id'=>null, 'cliente_id'=>$exemplo->cliente_id,
            'veiculo_cliente_id'=>$exemplo->veiculo_cliente_id,
            'setor_servico_id'=>$exemplo->setor_servico_id,
            'descricao'=>'bloqueio FK', 'status'=>'aberta', 'valor'=>1.23,
        ]);
    } catch (\Illuminate\Database\QueryException $e) {
        $nullBloqueado = true;
    }
    if (!$nullBloqueado) {
        throw new RuntimeException('NULL inesperadamente aceito');
    }
    echo "APROVADO=FK_OS_NOT_NULL\n";

    // O.S. rapida deve herdar nota_id da Nota, sem criar outra Nota.
    $nova = app(\App\Actions\Notas\CriarNota::class)->execute([
        'cliente_id' => (int)$exemplo->cliente_id,
        'veiculo_cliente_id' => (int)$exemplo->veiculo_cliente_id,
        'itens' => [[
            'itemable_type'=>OrdemServico::class,
            'itemable_id'=>-987654321,
            'os_nova_setor_servico_id'=>(int)$exemplo->setor_servico_id,
            'descricao'=>'FEUX L02C - RASCUNHO',
            'quantidade'=>1,'valor_unitario'=>2.34,'desconto'=>0,
        ]],
    ]);
    $item = $nova->itens()->where('itemable_type',OrdemServico::class)->firstOrFail();
    $novaOs = OrdemServico::findOrFail($item->itemable_id);
    if ((int)$novaOs->nota_id !== (int)$nova->id) {
        throw new RuntimeException('Rascunho gravou FK divergente');
    }
    echo "APROVADO=RASCUNHO_COM_FK_DA_NOTA\n";
} catch (Throwable $e) {
    $falha = $e;
} finally {
    DB::rollBack();
}
$after = [DB::table('ordem_servicos')->count(), DB::table('notas')->count(), DB::table('notas_itens')->count()];
if ($before !== $after) {
    throw new RuntimeException('Contagens alteradas apesar do rollback: antes='.json_encode($before).' depois='.json_encode($after));
}
echo "APROVADO=ROLLBACK_SEM_DADOS_PERSISTIDOS\n";
if ($falha) {
    throw $falha;
}
echo "L02C_SMOKE=APROVADO\n";
