#!/usr/bin/env bash
# Executado SOMENTE via SSH na VPS; recebe o GITHUB_TOKEN na primeira linha de stdin.
set -Eeuo pipefail
umask 077

TAG="${1:?Tag SHA obrigatoria}"
ACTOR="${2:?Usuario GitHub obrigatorio}"
[[ "$TAG" =~ ^[0-9a-f]{40}$ ]] || { echo 'SHA invalido' >&2; exit 1; }
[[ "$ACTOR" =~ ^[A-Za-z0-9-]+$ ]] || { echo 'Usuario GHCR invalido' >&2; exit 1; }

PROJECT_DIR=/var/www/sos-mecanica
DEPLOY_DIR=/home/mateus/deploy/sosmecanica
RELEASE="$DEPLOY_DIR/releases/$TAG"
NOVO="$RELEASE/compose.prod.yml"
ROLLBACK="$RELEASE/rollback.compose.yml"
APP_REPO=ghcr.io/mateus-santos/sosmecanica-app
WEB_REPO=ghcr.io/mateus-santos/sosmecanica-web

# Um lock global pode ser compartilhado pelos proximos projetos da VPS.
mkdir -p /home/mateus/deploy
exec 9>/home/mateus/deploy/.docker-release-global.lock
flock -w 180 9 || { echo 'Outro deploy em andamento na VPS.' >&2; exit 1; }

# O token nao aparece em argumentos, em logs ou na configuracao Docker permanente.
IFS= read -r GHCR_TOKEN || { echo 'Token ausente' >&2; exit 1; }
[[ -n "$GHCR_TOKEN" ]] || { echo 'Token vazio' >&2; exit 1; }
DOCKER_CFG="$(mktemp -d)"
cleanup() { rm -rf -- "$DOCKER_CFG"; }
trap cleanup EXIT

printf '%s' "$GHCR_TOKEN" | docker --config "$DOCKER_CFG" login ghcr.io \
  --username "$ACTOR" --password-stdin >/dev/null
unset GHCR_TOKEN

printf '%s\n' '========== 1. VERIFICACOES SEM ALTERACOES =========='
test -f "$PROJECT_DIR/.env"
test -d "$PROJECT_DIR/storage"
test -f "$NOVO"
test -f "$RELEASE/database_release.sh"
docker network inspect sos-mecanica_sail >/dev/null
test "$(docker inspect -f '{{.State.Health.Status}}' sos-mecanica-mysql-1)" = healthy
test "$(docker inspect -f '{{.State.Health.Status}}' sosmecanica-prod-web-1)" = healthy
test "$(docker inspect -f '{{.State.Status}}' sosmecanica-prod-app-1)" = running

APP_ATUAL="$(docker inspect -f '{{.Config.Image}}' sosmecanica-prod-app-1)"
WEB_ATUAL="$(docker inspect -f '{{.Config.Image}}' sosmecanica-prod-web-1)"
case "$APP_ATUAL" in sosmecanica-app:*) ;; *) echo 'Imagem APP atual inesperada' >&2; exit 1 ;; esac
TAG_ANTIGA="${APP_ATUAL#sosmecanica-app:}"
[[ "$WEB_ATUAL" == "sosmecanica-web:$TAG_ANTIGA" ]] || { echo 'Tags atuais diferentes' >&2; exit 1; }

COMPOSE_ANTIGO="$(docker inspect -f '{{ index .Config.Labels "com.docker.compose.project.config_files" }}' sosmecanica-prod-web-1)"
case "$COMPOSE_ANTIGO" in /*) ;; *) echo 'Caminho do Compose anterior inesperado' >&2; exit 1 ;; esac
case "$COMPOSE_ANTIGO" in *,*) echo 'Mais de um Compose anterior: revisar manualmente' >&2; exit 1 ;; esac
test -f "$COMPOSE_ANTIGO"
cp -p "$COMPOSE_ANTIGO" "$ROLLBACK"

SOS_IMAGE_TAG="$TAG" docker compose --project-directory "$PROJECT_DIR" \
  -f "$NOVO" config --quiet

echo '========== 2. BACKUP VALIDADO ANTES DO CORTE =========='
bash "$RELEASE/database_release.sh" backup "$TAG"

echo '========== 3. BAIXANDO SOMENTE CAMADAS NOVAS =========='
# O registro pode ser privado. Credencial e mantida apenas ate o final do script.
docker --config "$DOCKER_CFG" pull "$APP_REPO:$TAG"
docker --config "$DOCKER_CFG" pull "$WEB_REPO:$TAG"
# Mantemos nomes locais compativeis com compose.prod.yml e o rollback existente.
docker tag "$APP_REPO:$TAG" "sosmecanica-app:$TAG"
docker tag "$WEB_REPO:$TAG" "sosmecanica-web:$TAG"
docker image inspect "sosmecanica-app:$TAG" "sosmecanica-web:$TAG" >/dev/null

INICIOU=0
recuperar() {
  CODIGO=$?
  trap - ERR
  if [[ "$INICIOU" == 1 ]]; then
    echo 'Falha durante o corte: tentando restaurar imagens anteriores...' >&2
    echo 'ATENCAO: rollback da imagem NAO reverte migrations no MySQL.' >&2
    if SOS_IMAGE_TAG="$TAG_ANTIGA" docker compose --project-directory "$PROJECT_DIR" \
        -f "$ROLLBACK" up -d --wait --wait-timeout 120 \
        --no-deps --no-build --pull never --force-recreate app web \
      && curl -fsS --max-time 20 \
        --resolve jaimeautomotivo.com.br:443:127.0.0.1 \
        https://jaimeautomotivo.com.br/up -o /dev/null; then
      echo 'ROLLBACK confirmado.' >&2
    else
      echo 'ALERTA: rollback nao confirmado. Intervencao manual obrigatoria.' >&2
    fi
  fi
  exit "$CODIGO"
}
trap recuperar ERR

echo '========== 4. ATUALIZANDO APENAS APP E WEB =========='
INICIOU=1
SOS_IMAGE_TAG="$TAG" docker compose --project-directory "$PROJECT_DIR" \
  -f "$NOVO" up -d --wait --wait-timeout 120 \
  --no-deps --no-build --pull never --force-recreate app web

echo '========== 5. APLICAR MIGRATIONS PENDENTES =========='
bash "$RELEASE/database_release.sh" migrate "$TAG"

echo '========== 6. TESTES HTTPS =========='
curl -fsS --max-time 20 \
  --resolve jaimeautomotivo.com.br:443:127.0.0.1 \
  https://jaimeautomotivo.com.br/up -o /dev/null
curl -fsS --max-time 20 \
  --resolve jaimeautomotivo.com.br:443:127.0.0.1 \
  https://jaimeautomotivo.com.br/login -o /dev/null

trap - ERR
echo "DEPLOY APROVADO: $TAG"
echo "TAG ANTERIOR: $TAG_ANTIGA"
docker ps --filter name=sosmecanica-prod- --format 'table {{.Names}}\t{{.Status}}'
