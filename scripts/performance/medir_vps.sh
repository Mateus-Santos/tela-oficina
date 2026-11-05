#!/usr/bin/env bash
# Somente leitura. Executar na VPS quando precisarmos medir antes/depois.
set -euo pipefail
printf '%s\n' '=== Memoria geral ==='
free -m
printf '%s\n' '=== Processos dos containers (sem novos containers) ==='
docker stats --no-stream \
  --format 'table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.PIDs}}' \
  sosmecanica-prod-app-1 sosmecanica-prod-web-1 sos-mecanica-mysql-1
printf '%s\n' '=== Processos PHP no container existente ==='
docker exec sosmecanica-prod-app-1 sh -c 'ps -o pid,ppid,rss,stat,comm -C php-fpm 2>/dev/null || ps aux | grep "[p]hp-fpm"'
printf '%s\n' '=== Smoke HTTP local ==='
curl -sS -o /dev/null --max-time 20 \
  --resolve jaimeautomotivo.com.br:443:127.0.0.1 \
  -w 'GET /up: HTTP %{http_code} TTFB %{time_starttransfer}s total %{time_total}s\n' \
  'https://jaimeautomotivo.com.br/up'
echo 'OBS: /up nao representa o desempenho de telas autenticadas; medir sob carga separadamente.'
