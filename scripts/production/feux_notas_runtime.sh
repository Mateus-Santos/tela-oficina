#!/usr/bin/env bash
# Alternancia pontual da Central de Notas, executada somente pelo Actions.
# Sem migrations, sem acesso ao MySQL, sem troca de imagem; reverte .env e app em falha.
set -Eeuo pipefail
umask 077
ACAO="${1:-}"
case "$ACAO" in status|ativar|reverter) ;; *) echo 'BLOQUEADO=ACAO_INVALIDA' >&2; exit 2;; esac
PROJETO=/var/www/sos-mecanica
ENV_FILE="$PROJETO/.env"
APP=sosmecanica-prod-app-1
WEB=sosmecanica-prod-web-1
MYSQL=sos-mecanica-mysql-1
DEPLOY=/home/mateus/deploy/sosmecanica
HOST=jaimeautomotivo.com.br

[[ -f "$ENV_FILE" && ! -L "$ENV_FILE" ]] || { echo 'BLOQUEADO=ENV_AUSENTE_OU_SYMLINK' >&2; exit 41; }
[[ "$(docker inspect -f '{{.State.Status}}' "$APP")" == running ]] || { echo 'BLOQUEADO=APP_NAO_EXECUTANDO' >&2; exit 41; }
[[ "$(docker inspect -f '{{.State.Health.Status}}' "$WEB")" == healthy ]] || { echo 'BLOQUEADO=WEB_SEM_HEALTH' >&2; exit 41; }
[[ "$(docker inspect -f '{{.State.Health.Status}}' "$MYSQL")" == healthy ]] || { echo 'BLOQUEADO=MYSQL_SEM_HEALTH' >&2; exit 41; }
APP_IMAGE="$(docker inspect -f '{{.Config.Image}}' "$APP")"
WEB_IMAGE="$(docker inspect -f '{{.Config.Image}}' "$WEB")"
[[ "$APP_IMAGE" =~ ^sosmecanica-app:([0-9a-f]{40})$ ]] || { echo 'BLOQUEADO=IMAGEM_APP_DESCONHECIDA' >&2; exit 42; }
TAG="${BASH_REMATCH[1]}"
[[ "$WEB_IMAGE" == "sosmecanica-web:$TAG" ]] || { echo 'BLOQUEADO=IMAGENS_DIVERGENTES' >&2; exit 42; }
COMPOSE="$DEPLOY/releases/$TAG/compose.prod.yml"
[[ -f "$COMPOSE" && ! -L "$COMPOSE" ]] || { echo 'BLOQUEADO=COMPOSE_DA_IMAGEM_AUSENTE' >&2; exit 42; }
COMPOSE_ATUAL="$(docker inspect -f '{{ index .Config.Labels "com.docker.compose.project.config_files" }}' "$WEB")"
[[ "$COMPOSE_ATUAL" == "$COMPOSE" ]] || { echo 'BLOQUEADO=COMPOSE_NAO_CORRESPONDE_A_RELEASE' >&2; exit 42; }

# Impede duplicidade/ambiguidade da chave, mesmo em modo status.
QTD="$(grep -Ec '^[[:space:]]*FEUX_NOTAS_VUE_ENABLED[[:space:]]*=' "$ENV_FILE" || true)"
[[ "$QTD" =~ ^[0-9]+$ && "$QTD" -le 1 ]] || { echo 'BLOQUEADO=FLAG_DUPLICADA_NO_ENV' >&2; exit 43; }
ENV_VAL="$(sed -nE 's/^[[:space:]]*FEUX_NOTAS_VUE_ENABLED[[:space:]]*=[[:space:]]*([^#[:space:]]*).*/\1/p' "$ENV_FILE" | head -n1)"
if [[ "$QTD" != 0 ]]; then
  case "$ENV_VAL" in true|false|1|0|'"true"'|'"false"') ;; *) echo 'BLOQUEADO=FLAG_ENV_NAO_CANONICA' >&2; exit 43;; esac
fi
[[ "$QTD" == 0 || -n "$ENV_VAL" ]] || { echo 'BLOQUEADO=FLAG_ENV_NAO_CANONICA' >&2; exit 43; }
LARAVEL_FLAG() {
  docker exec "$APP" php -r '
    chdir("/var/www/html");
    require "vendor/autoload.php";
    $app = require "bootstrap/app.php";
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    echo config("feux_notas.central_vue3", false) ? "true" : "false";
  '
}
ATIVA="$(LARAVEL_FLAG)"
[[ "$ATIVA" == true || "$ATIVA" == false ]] || { echo 'BLOQUEADO=CONFIG_LARAVEL_INDETERMINADA' >&2; exit 44; }
printf 'RELEASE_ATIVA=%s\nFEUX_ENV_CONFIGURADA=%s\nFEUX_LARAVEL_EFETIVA=%s\n' "$TAG" "${ENV_VAL:-ausente}" "$ATIVA"
if [[ "$ACAO" == status ]]; then
  echo 'DIAGNOSTICO=APENAS_LEITURA; NENHUM_CONTAINER_RECRIADO'
  exit 0
fi

[[ -O "$ENV_FILE" && -w "$ENV_FILE" && -w "$PROJETO" ]] || {
  echo 'BLOQUEADO=ENV_SEM_PERMISSAO_DE_ESCRITA_PARA_OPERADOR' >&2; exit 45;
}
[[ "$ACAO" == ativar ]] && ALVO=true || ALVO=false
if [[ "$ATIVA" == "$ALVO" && "$ENV_VAL" == "$ALVO" ]]; then
  echo "FEUX_CENTRAL_NOTAS=JA_NO_ESTADO_$ALVO"
  exit 0
fi
exec 9>"$DEPLOY/.docker-release-global.lock"
flock -w 180 9 || { echo 'BLOQUEADO=OUTRA_PUBLICACAO_EM_ANDAMENTO' >&2; exit 46; }
# Revalidar app apos lock; nenhuma operacao pode usar a imagem errada.
[[ "$(docker inspect -f '{{.Config.Image}}' "$APP")" == "$APP_IMAGE" ]] || { echo 'BLOQUEADO=RELEASE_ALTERADA_DURANTE_PRECHECK' >&2; exit 46; }
SOS_IMAGE_TAG="$TAG" docker compose --project-directory "$PROJETO" -f "$COMPOSE" config --quiet

BACKUP_DIR="$HOME/backups/feux-notas"
[[ ! -L "$BACKUP_DIR" ]] || { echo 'BLOQUEADO=BACKUP_DIR_SYMLINK' >&2; exit 47; }
mkdir -p -m 700 "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"
BACKUP="$BACKUP_DIR/env-$TAG-$(date -u +%Y%m%d-%H%M%S)-$$.bak"
cp -p -- "$ENV_FILE" "$BACKUP"
chmod 600 "$BACKUP"
[[ -s "$BACKUP" ]] || { echo 'BLOQUEADO=BACKUP_ENV_VAZIO' >&2; exit 47; }
echo 'BACKUP_ENV=CONCLUIDO; ARQUIVO_PRIVADO_PRESERVADO'
MUTOU=0
RECUPERAR() {
  local rc="${1:-1}"
  trap - ERR INT TERM
  if [[ "$MUTOU" == 1 ]]; then
    echo 'ALERTA=FALHA_NA_ATIVACAO; TENTANDO_RESTAURAR_ENV_E_APP' >&2
    cp -p -- "$BACKUP" "$ENV_FILE" || true
    SOS_IMAGE_TAG="$TAG" docker compose --project-directory "$PROJETO" -f "$COMPOSE" \
      up -d --wait --wait-timeout 120 --no-deps --no-build --pull never --force-recreate app || true
    local nginx_ok=1
    docker exec "$WEB" nginx -s reload >/dev/null 2>&1 || nginx_ok=0
    docker exec "$APP" php artisan config:clear --no-ansi >/dev/null 2>&1 || true
    if [[ "$(LARAVEL_FLAG 2>/dev/null || true)" == "$ATIVA" && "$nginx_ok" == 1 ]] \
        && curl -fsS --max-time 15 --resolve "$HOST:443:127.0.0.1" "https://$HOST/up" -o /dev/null; then
      echo "ROLLBACK_FLAG_E_HTTPS=CONFIRMADOS_ESTADO_ANTERIOR_$ATIVA" >&2
    else
      echo 'ALERTA=ROLLBACK_NAO_CONFIRMADO; NECESSARIA_REVISAO' >&2
    fi
  fi
  exit "$rc"
}
trap 'RECUPERAR $?' ERR
trap 'RECUPERAR 130' INT
trap 'RECUPERAR 143' TERM
TMP="$(mktemp "$PROJETO/.env.feux.XXXXXXXX")"
trap '[[ -z "${TMP:-}" ]] || rm -f -- "$TMP"' EXIT
# Sempre uma unica chave com formato canonico; preserva todo o resto.
awk -v alvo="$ALVO" '
  /^[[:space:]]*FEUX_NOTAS_VUE_ENABLED[[:space:]]*=/ { n++; if (n>1) exit 43; print "FEUX_NOTAS_VUE_ENABLED=" alvo; next }
  {print}
  END { if (n == 0) print "FEUX_NOTAS_VUE_ENABLED=" alvo }
' "$ENV_FILE" > "$TMP"
chmod 600 "$TMP"
MUTOU=1
mv -f -- "$TMP" "$ENV_FILE"
SOS_IMAGE_TAG="$TAG" docker compose --project-directory "$PROJETO" -f "$COMPOSE" \
  config --quiet
# Recria apenas o PHP-FPM usando a mesma imagem/mesmo banco/mesmo Nginx.
SOS_IMAGE_TAG="$TAG" docker compose --project-directory "$PROJETO" -f "$COMPOSE" \
  up -d --wait --wait-timeout 120 --no-deps --no-build --pull never --force-recreate app
[[ "$(docker inspect -f '{{.Config.Image}}' "$APP")" == "$APP_IMAGE" ]]
# nginx.conf usa fastcgi_pass app:9000; reload re-resolve DNS apos recreate.
docker exec "$WEB" nginx -t
docker exec "$WEB" nginx -s reload
docker exec "$APP" php artisan config:clear --no-ansi
EFETIVA="$(LARAVEL_FLAG)"
[[ "$EFETIVA" == "$ALVO" ]] || { echo 'BLOQUEADO=FLAG_NAO_APLICADA' >&2; false; }
# Confirma que a rota foi registrada e que o site continua atendendo sem credenciais.
docker exec "$APP" php artisan route:list --name=notas.workspace --no-ansi | grep -F 'notas/painel' >/dev/null
curl -fsS --max-time 20 --resolve "$HOST:443:127.0.0.1" "https://$HOST/up" -o /dev/null
curl -fsS --max-time 20 --resolve "$HOST:443:127.0.0.1" "https://$HOST/login" -o /dev/null
trap - ERR INT TERM
printf 'CENTRAL_NOTAS_FLAG=%s\nIMAGEM_PRESERVADA=%s\nMYSQL_E_NGINX_NAO_RECRIADOS=SIM; NGINX_RELOAD=SIM\nSAUDE_HTTPS=APROVADA\n' "$EFETIVA" "$TAG"

