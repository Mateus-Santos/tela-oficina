#!/usr/bin/env bash
# P02: apenas GET em /up e /login. Amostras curtas; sem escrita no servidor.
# Executar na VPS (ou via SSH) antes e depois de uma mudanca de release.
set -Eeuo pipefail
HOST="${1:-jaimeautomotivo.com.br}"
[[ "$HOST" =~ ^[a-zA-Z0-9.-]+$ ]] || { echo 'Host invalido' >&2; exit 2; }
command -v curl >/dev/null
command -v awk >/dev/null
command -v sort >/dev/null
TEMP="$(mktemp -d)"
trap 'rm -rf -- "$TEMP"' EXIT

medir() {
    local rota="$1" arquivo="$TEMP/${1}.txt"
    : > "$arquivo"
    # 30 GETs com concorrencia maxima de 3; cada chamada tem timeout.
    for (( i=1; i<=30; i++ )); do
        printf '%s\n' "$i"
    done | xargs -P 3 -I '{}' \
        curl --fail --silent --show-error --max-time 10 \
        --resolve "$HOST:443:127.0.0.1" \
        --output /dev/null \
        --write-out '%{time_starttransfer}\n' \
        "https://$HOST/$rota" >> "$arquivo"
    test "$(wc -l < "$arquivo")" -eq 30
    LC_ALL=C sort -n "$arquivo" | awk -v rota="$rota" '
       { v[NR]=$1; total+=$1 }
       END { if (NR != 30) exit 1;
         printf "/%s | 30 GETs, 3 simultaneos | media %.2f ms | p95 %.2f ms | max %.2f ms\n", \
           rota,total/NR*1000,v[29]*1000,v[NR]*1000 }
    '
}

printf 'P02 / HTTP: host=%s | %s\n' "$HOST" "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
medir up
medir login
echo 'Observacao: GET /login sem sessao nao representa notas/compras autenticadas.'
