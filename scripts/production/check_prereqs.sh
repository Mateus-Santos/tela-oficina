#!/usr/bin/env bash
# Somente leitura — valida se a arquitetura paralela pode ser usada nesta VPS.
set -euo pipefail
cd "$(dirname "$0")/../.."
command -v docker >/dev/null
printf '\n===== STACK ATUAL =====\n'
docker compose ps
printf '\n===== MYSQL E VOLUME PRESERVADO =====\n'
docker inspect sos-mecanica-mysql-1 --format '{{.Name}} status={{.State.Status}}{{range .Mounts}}{{printf "\n%s: %s -> %s" .Type .Source .Destination}}{{end}}{{println}}'
docker volume inspect sos-mecanica_sail-mysql --format '{{.Name}}'
printf '\n===== REDE PARA ACESSO AO MYSQL =====\n'
docker network inspect sos-mecanica_sail --format '{{.Name}}{{range .Containers}}{{printf "\n%s" .Name}}{{end}}{{println}}'
printf '\n===== PERSISTENCIA E IDENTIDADE =====\n'
for d in storage storage/app storage/app/public storage/framework storage/logs bootstrap/cache; do
    test -d "$d"
    stat -c '%u:%g %a %n' "$d"
done
printf '\n===== PORTA STAGING DE PRODUCAO =====\n'
if command -v ss >/dev/null; then ss -ltn | grep -E '(:18081[[:space:]])' || true; fi
printf '\nDiagnostico apenas leitura concluido. NENHUM container alterado.\n'
