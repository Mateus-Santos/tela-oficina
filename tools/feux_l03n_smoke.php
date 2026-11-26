<?php
/** L03N: somente MySQL FEUX; movimentos de ensaio sempre com rollback. */
declare(strict_types=1);
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Actions\Notas\AlterarEtapaNota;
use App\Actions\Notas\CriarNota;
use App\Models\Etapa;
use App\Models\Nota;
use App\Services\Nota\MoverEtapaEmLote;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

$db = (string) DB::connection()->getDatabaseName();
if (DB::getDriverName() !== 'mysql' || ! preg_match('/^feux_dados_dev_\d{8}_\d{6}_\d+$/', $db)) {
    throw new RuntimeException('Bloqueado: MySQL nao e o clone FEUX.');
}
$etapasFinais = Etapa::query()->paraNota()->ativas()->finais()->get();
if ($etapasFinais->count() !== 1) throw new RuntimeException('Etapa final ativa deve ser unica.');
$final = $etapasFinais->first();
$outras = Etapa::query()->paraNota()->ativas()->where('tipo', '!=', 'final')->get();
if ($outras->count() < 2) throw new RuntimeException('Teste de lote exige 2 etapas nao-finais.');
$origem = $outras->first();
$destino = $outras->skip(1)->first();
$totaisAntes = [DB::table('notas')->count(), DB::table('nota_etapa_historicos')->count()];
$falha = null;
DB::beginTransaction();
try {
    // Criar uma Nota vazia de ensaio evita alterar registros reais.
    $nota = app(CriarNota::class)->execute(['cliente_id' => null, 'veiculo_cliente_id' => null, 'itens' => []]);
    $operacao = app(AlterarEtapaNota::class);
    if ((int) $nota->etapa_id !== (int) $origem->id) {
        // Origem padrão pode não ser a primeira no orderBy; ajustar dentro do rollback.
        $origem = Etapa::findOrFail($nota->etapa_id);
        if ($origem->tipo === 'final') throw new RuntimeException('Etapa inicial nao pode ser final.');
        $destino = $outras->first(fn ($e) => (int) $e->id !== (int) $origem->id);
    }
    $rejeitou = false;
    try {
        $operacao->execute($nota, $final, null, 'smoke nao deve mover para a final');
    } catch (InvalidArgumentException $e) {
        $rejeitou = true;
    }
    if (! $rejeitou || (int) $nota->fresh()->etapa_id !== (int) $origem->id) {
        throw new RuntimeException('Bypass simples da etapa final nao foi bloqueado.');
    }
    echo "APROVADO=ETAPA_FINAL_NAO_E_PATCH_COMUM\n";

    $servico = app(MoverEtapaEmLote::class);
    $bloqueouLoteFinal = false;
    try { $servico->previsualizar((int) $origem->id, (int) $final->id); }
    catch (InvalidArgumentException) { $bloqueouLoteFinal = true; }
    if (! $bloqueouLoteFinal) throw new RuntimeException('Etapa final aceita lote indevidamente.');
    echo "APROVADO=LOTE_FINAL_PROIBIDO\n";

    $previa = $servico->previsualizar((int) $origem->id, (int) $destino->id);
    if ($previa['contagem'] === 0 || $previa['excede_limite']) {
        echo "SMOKE_LOTE_SEM_EXECUCAO=CONTAGEM_ZERO_OU_SUPERIOR_A_100\n";
    } else {
        $assinaturaErrada = false;
        try { $servico->executar((int) $origem->id, (int) $destino->id, str_repeat('0', 64), null); }
        catch (InvalidArgumentException) { $assinaturaErrada = true; }
        if (! $assinaturaErrada) throw new RuntimeException('Assinatura antiga nao foi rejeitada.');
        $resultado = $servico->executar((int) $origem->id, (int) $destino->id, $previa['assinatura'], null);
        if ($resultado['movidas'] !== $previa['contagem']) throw new RuntimeException('Lote moveu contagem divergente.');
        if ((int) $nota->fresh()->etapa_id !== (int) $destino->id) throw new RuntimeException('Nota de ensaio nao mudou de etapa.');
        echo "APROVADO=LOTE_TRANSACIONAL_E_ASSINATURA\n";
    }
} catch (Throwable $e) {
    $falha = $e;
} finally {
    DB::rollBack();
}
$totaisDepois = [DB::table('notas')->count(), DB::table('nota_etapa_historicos')->count()];
if ($totaisAntes !== $totaisDepois) {
    throw new RuntimeException('Rollback do smoke deixou registros: '.json_encode([$totaisAntes, $totaisDepois]));
}
if ($falha) throw $falha;
echo "APROVADO=ROLLBACK_SEM_PERSISTENCIA\n";
echo "L03N_SMOKE=APROVADO\n";
