#!/usr/bin/env bash
# Invocado pelo release da VPS: backup antes da troca de imagem, migrate depois.
set -Eeuo pipefail
umask 077

MODO="${1:-}"
TAG="${2:-}"
[[ "$MODO" == backup || "$MODO" == migrate ]] || { echo 'Uso: database_release.sh backup|migrate SHA' >&2; exit 2; }
[[ "$TAG" =~ ^[0-9a-f]{40}$ ]] || { echo 'SHA invalido' >&2; exit 2; }

APP=sosmecanica-prod-app-1
MYSQL=sos-mecanica-mysql-1
BANCO=jaime_automotivo

verificar_banco() {
  test "$(docker inspect -f '{{.State.Health.Status}}' "$MYSQL")" = healthy
  docker exec "$APP" php -r '
    chdir("/var/www/html");
    require "vendor/autoload.php";
    $app = require "bootstrap/app.php";
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    if (!app()->environment("production")) {
      throw new \RuntimeException("Laravel nao esta em producao");
    }
    $nome = \Illuminate\Support\Facades\DB::selectOne("SELECT DATABASE() AS nome")->nome;
    if ($nome !== "jaime_automotivo") {
      throw new \RuntimeException("Banco inesperado: " . $nome);
    }
    echo "Banco de producao confirmado: " . $nome . PHP_EOL;
  '
}

verificar_banco

if [[ "$MODO" == backup ]]; then
  PASTA="$HOME/backups"
  [[ ! -L "$PASTA" ]] || { echo 'Diretorio de backup nao pode ser symlink' >&2; exit 1; }
  mkdir -p -m 700 "$PASTA"
  test -O "$PASTA"
  chmod 700 "$PASTA"

  # Nao iniciar alteracoes de schema com o volume de backup quase cheio.
  LIVRE_KB="$(df -Pk "$PASTA" | awk 'NR==2 {print $4}')"
  [[ "$LIVRE_KB" =~ ^[0-9]+$ ]] && (( LIVRE_KB >= 102400 )) || {
    echo 'Espaco insuficiente para backup (>100 MiB necessarios)' >&2
    exit 1
  }

  DATA="$(date -u +%Y%m%d-%H%M%S)"
  DESTINO="$PASTA/jaime-predeploy-${TAG}-${DATA}-$$.sql.gz"
  TEMP="${DESTINO}.part"
  trap 'rm -f -- "$TEMP"' EXIT

  echo '========== BACKUP MYSQL ANTES DO DEPLOY =========='
  # pipefail garante que falhas do mysqldump invalidem o backup.
  docker exec "$MYSQL" sh -ec '
    export MYSQL_PWD="${MYSQL_ROOT_PASSWORD:?}"
    exec mysqldump --protocol=socket -uroot \
      --single-transaction --quick \
      --routines --triggers --events \
      --no-tablespaces --set-gtid-purged=OFF \
      jaime_automotivo
  ' | gzip -1 > "$TEMP"
  test -s "$TEMP"
  gzip -t "$TEMP"
  mv -- "$TEMP" "$DESTINO"
  trap - EXIT
  echo "BACKUP VALIDADO: $DESTINO"
  ls -lh "$DESTINO"
else
  test "$(docker inspect -f '{{.Config.Image}}' "$APP")" = "sosmecanica-app:$TAG" || {
    echo 'Imagem de app diferente do SHA aprovado; migrations bloqueadas' >&2
    exit 1
  }
  echo '========== EXECUTAR SOMENTE MIGRATIONS PENDENTES =========='
  # Laravel registra as ja aplicadas; jamais usar migrate:fresh ou rollback automatico.
  docker exec "$APP" sh -ec '
    cd /var/www/html
    php artisan migrate --force --no-interaction
  '
  echo 'MIGRATIONS CONCLUIDAS'
fi
