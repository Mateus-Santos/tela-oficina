#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../.."
TAG="${1:-$(git rev-parse --short=12 HEAD)}"
[[ "$TAG" =~ ^[a-zA-Z0-9._-]+$ ]] || { echo 'TAG invalida' >&2; exit 1; }
command -v docker >/dev/null || { echo 'Docker indisponivel' >&2; exit 1; }

BASE_HASH="$(sha256sum docker/production/Dockerfile.php-base | cut -c1-20)"
BASE_IMAGE="sosmecanica-php-base:php85-$BASE_HASH"

echo "[1/3] PHP-base reutilizavel: $BASE_IMAGE"
docker build --progress=plain \
  --build-arg "PHP_BUILD_JOBS=${PHP_BUILD_JOBS:-1}" \
  -f docker/production/Dockerfile.php-base -t "$BASE_IMAGE" .

echo "[2/3] Laravel e assets: $TAG"
docker build --progress=plain \
  --build-arg "PHP_BASE_IMAGE=$BASE_IMAGE" \
  -f docker/production/Dockerfile.app -t "sosmecanica-app:$TAG" .

echo "[3/3] Nginx: $TAG"
docker build --progress=plain -f docker/production/Dockerfile.web \
  --build-arg "APP_IMAGE=sosmecanica-app:$TAG" \
  -t "sosmecanica-web:$TAG" .
echo "Imagens concluidas: sosmecanica-app:$TAG / sosmecanica-web:$TAG"
