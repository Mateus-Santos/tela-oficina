#!/usr/bin/env bash
# Orquestracao remota exclusivamente pelo workflow production.
set -Eeuo pipefail
umask 077
TAG="${1:?SHA da master necessario}"
[[ "$TAG" =~ ^[0-9a-f]{40}$ ]] || exit 2
APP=sosmecanica-prod-app-1
MYSQL=sos-mecanica-mysql-1
DIR="/home/mateus/deploy/sosmecanica/releases/$TAG"
[[ -f "$DIR/reconciliar_os_sem_nota.php" && -f "$DIR/preflight_l02c.php" \
   && -f "$DIR/backup_release.sh" && -f "$DIR/database_release.sh" \
   && -f "$DIR/storage_release.sh" && -f "$DIR/mysql_restore_check.sh" ]] || {
    echo 'BLOQUEADO=ARQUIVOS_DE_REPARO_AUSENTES' >&2; exit 42;
}
[[ "$(docker inspect -f '{{.State.Status}}' "$APP")" == running ]]
[[ "$(docker inspect -f '{{.State.Health.Status}}' "$MYSQL")" == healthy ]]
echo '===== 1. AUDITAR EXATAMENTE 2 OS, SEM ESCRITA ====='
AUDIT="$(docker exec -i -e SOS_ORFAS_MODO=audit "$APP" php < "$DIR/reconciliar_os_sem_nota.php")"
printf '%s\n' "$AUDIT"
IDS="$(printf '%s\n' "$AUDIT" | sed -n 's/^ORFAS_IDS=//p' | tail -n 1)"
TOTAL="$(printf '%s\n' "$AUDIT" | sed -n 's/^ORFAS_TOTAL=//p' | tail -n 1)"
[[ "$TOTAL" =~ ^(0|2)$ ]] || { echo 'BLOQUEADO=CONTAGEM_INESPERADA' >&2; exit 43; }
if [[ "$TOTAL" == 0 ]]; then
    echo 'REGULARIZACAO_JA_CONCLUIDA=SIM'
else
    [[ "$IDS" =~ ^[1-9][0-9]*,[1-9][0-9]*$ ]] || { echo 'BLOQUEADO=IDS_INVALIDOS' >&2; exit 43; }
    echo '===== 2. BACKUP MYSQL E STORAGE + ENSAIO DE RESTAURACAO ====='
    bash "$DIR/backup_release.sh" "$TAG"
    echo '===== 3. REGULARIZAR APENAS AS 2 OS AUDITADAS ====='
    docker exec -i -e SOS_ORFAS_MODO=apply \
        -e SOS_ORFAS_CONFIRMAR=REGULARIZAR_2_OS \
        -e "SOS_ORFAS_IDS_ESPERADOS=$IDS" \
        "$APP" php < "$DIR/reconciliar_os_sem_nota.php"
fi
echo '===== 4. EXECUTAR PREFLIGHT OFICIAL DEPOIS DO REPARO ====='
docker exec -i "$APP" php < "$DIR/preflight_l02c.php"
echo 'REGULARIZACAO_OS_LEGADAS=APROVADA; PRONTO_PARA_WORKFLOW_DEPLOY'
