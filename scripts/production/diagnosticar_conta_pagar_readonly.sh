#!/usr/bin/env bash
# Uso: invocado exclusivamente pelo job manual "diagnosticar" na master.
# Lê agregados SQL e assinaturas dos logs; nao altera banco nem containers.
set -Eeuo pipefail
umask 077
CONTA_ID="${1:-0}"
[[ "$CONTA_ID" =~ ^[0-9]{1,10}$ ]] || { echo 'ID tecnico invalido.' >&2; exit 2; }
APP=sosmecanica-prod-app-1
[[ "$(docker inspect -f '{{.State.Status}}' "$APP")" == running ]] || exit 1
IMAGEM="$(docker inspect -f '{{.Config.Image}}' "$APP")"
[[ "$IMAGEM" == sosmecanica-app:* ]] || exit 1
echo "DIAGNOSTICO_READONLY: imagem=$IMAGEM"
# PHP executado por STDIN; nenhuma carga ou exportacao do banco.
docker exec -i -e "CP_DIAG_ID=$CONTA_ID" "$APP" php <<'PHP'
<?php
chdir('/var/www/html');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('production')) { throw new RuntimeException('Ambiente diferente de producao.'); }
$pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== 'jaime_automotivo') {
    throw new RuntimeException('Banco inesperado; consulta bloqueada.');
}
$pdo->exec('START TRANSACTION READ ONLY');
try {
    // Somente contagens de integridade: sem nomes, notas, CPF, cliente ou anexos.
    $sql = 'SELECT COUNT(*) contas_com_compras,
        SUM(CASE WHEN ABS(c.valor - cv.total_vinculado) > 0.009 THEN 1 ELSE 0 END) compras_divergentes,
        SUM(CASE WHEN ABS(c.valor - COALESCE(p.total_parcelas,0)) > 0.009 THEN 1 ELSE 0 END) parcelas_divergentes
      FROM contas_pagar c
      JOIN (SELECT conta_pagar_id,SUM(valor_vinculado) total_vinculado
        FROM compra_conta_pagar GROUP BY conta_pagar_id) cv ON cv.conta_pagar_id=c.id
      LEFT JOIN (SELECT conta_pagar_id,SUM(valor) total_parcelas
        FROM parcelas_contas_pagar GROUP BY conta_pagar_id) p ON p.conta_pagar_id=c.id';
    $resumo = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
    foreach ($resumo as $k=>$v) {
        echo strtoupper($k).'='.(int) $v.PHP_EOL;
    }
    $id = (int) (getenv('CP_DIAG_ID') ?: 0);
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT c.status, c.valor, c.compra_id,
          (SELECT COUNT(*) FROM compra_conta_pagar cv WHERE cv.conta_pagar_id=c.id) compras_count,
          (SELECT COALESCE(SUM(valor_vinculado),0) FROM compra_conta_pagar cv WHERE cv.conta_pagar_id=c.id) total_compras,
          (SELECT COUNT(*) FROM parcelas_contas_pagar p WHERE p.conta_pagar_id=c.id) parcelas_count,
          (SELECT COALESCE(SUM(valor),0) FROM parcelas_contas_pagar p WHERE p.conta_pagar_id=c.id) total_parcelas,
          (SELECT COALESCE(SUM(valor),0) FROM pagamentos_contas_pagar pg WHERE pg.conta_pagar_id=c.id AND pg.estornado_em IS NULL) total_pago
          FROM contas_pagar c WHERE c.id=? LIMIT 1');
        $stmt->execute([$id]);
        $c = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$c) {
            echo "CONTA_ID=$id NAO_ENCONTRADA".PHP_EOL;
        } else {
            $cent = static fn($v) => (int) round((float)$v * 100);
            echo "CONTA_ID=$id STATUS=".$c['status'].
              " COMPRAS=".$c['compras_count'].
              " PARCELAS=".$c['parcelas_count'].
              " LEGADO=".($c['compra_id'] === null ? 'NAO' : 'SIM').PHP_EOL;
            echo 'DIF_COMPRAS_CENTAVOS='.($cent($c['valor'])-$cent($c['total_compras'])).PHP_EOL;
            echo 'DIF_PARCELAS_CENTAVOS='.($cent($c['valor'])-$cent($c['total_parcelas'])).PHP_EOL;
            echo 'TOTAL_PAGO_MAIOR_QUE_CONTA='.($cent($c['total_pago']) > $cent($c['valor']) ? 'SIM' : 'NAO').PHP_EOL;
        }
    }
} finally { $pdo->exec('ROLLBACK'); }

// Ultimos erros: nao imprime mensagens nem stack trace que podem conter dados pessoais.
$arquivo='/var/www/html/storage/logs/laravel.log';
if (!is_file($arquivo) || !is_readable($arquivo)) {
    echo "LOG_LARAVEL_INDISPONIVEL".PHP_EOL;
} else {
    $fp=fopen($arquivo,'rb');
    $size=filesize($arquivo);
    fseek($fp,max(0,$size-2097152));
    if ($size>2097152) fgets($fp);
    $tipos=[];
    $total=0;
    while (($line=fgets($fp))!==false) {
        if (!preg_match('/^\[(\d{4}-\d\d-\d\d \d\d:\d\d:\d\d)\].*\.ERROR:/',$line,$m)) continue;
        $total++;
        if (preg_match('/(QueryException|PDOException|InvalidArgumentException|ValidationException|TypeError|ErrorException)/',$line,$e)) {
            $tipo=$e[1];
        } else {
            $tipo='Outro';
        }
        $tipos[$tipo]=($tipos[$tipo]??0)+1;
    }
    fclose($fp);
    echo 'ERROS_NO_TRECHO_DE_LOG='.$total.PHP_EOL;
    foreach ($tipos as $tipo=>$n) echo "ERRO_TIPO_$tipo=$n".PHP_EOL;
}
PHP
