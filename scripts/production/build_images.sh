#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../.."
TAG="${1:-$(git rev-parse --short=12 HEAD)}"
[[ "$TAG" =~ ^[a-zA-Z0-9._-]+$ ]] || { echo 'TAG invalida' >&2; exit 1; }
command -v docker >/dev/null || { echo 'Docker indisponivel' >&2; exit 1; }

echo "[1/2] Construindo PHP e assets: $TAG"
docker build --progress=plain \
  --build-arg "PHP_BUILD_JOBS=${PHP_BUILD_JOBS:-1}" \
  -f docker/production/Dockerfile.app -t "sosmecanica-app:$TAG" .
echo "[2/2] Construindo Nginx com os mesmos assets: $TAG"
docker build --progress=plain -f docker/production/Dockerfile.web \
  --build-arg "APP_IMAGE=sosmecanica-app:$TAG" \
  -t "sosmecanica-web:$TAG" .
echo "Imagens construidas: sosmecanica-app:$TAG / sosmecanica-web:$TAG"
