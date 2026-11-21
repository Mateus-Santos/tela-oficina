#!/usr/bin/env bash
# Gate obrigatorio antes do corte de producao.
# Usa o dump legado (ja validado) + copia real de storage/app + testes de restauracao.
# Nunca executa migrations, nao troca imagens e nao modifica a instancia MySQL existente.
set -Eeuo pipefail
umask 077

TAG="${1:?Commit completo obrigatorio}"
[[ "$TAG" =~ ^[0-9a-f]{40}$ ]] || { echo 'SHA de release invalido.' >&2; exit 1; }
RELEASE="/home/mateus/deploy/sosmecanica/releases/$TAG"
PROJETO=/var/www/sos-mecanica
BACKUPS="$HOME/backups"
[[ -f "$RELEASE/database_release.sh" && -f "$RELEASE/storage_release.sh" && -f "$RELEASE/mysql_restore_check.sh" ]] || {
  echo 'Scripts do gate de backup incompletos na release.' >&2; exit 1;
}
[[ -f "$PROJETO/.env" && -d "$PROJETO/storage/app" ]] || {
  echo 'Estrutura real da aplicacao nao identificada.' >&2; exit 1;
}
[[ ! -L "$BACKUPS" ]] || { echo 'Raiz de backups nao pode ser symlink.' >&2; exit 1; }
mkdir -p -m 700 "$BACKUPS"
[[ -O "$BACKUPS" ]] || { echo 'Raiz de backups nao pertence ao operador.' >&2; exit 1; }
chmod 700 "$BACKUPS"
exec 8>"$BACKUPS/.sosmecanica-backup.lock"
flock -w 180 8 || { echo 'Outro backup em andamento.' >&2; exit 1; }

DATA="$(date -u +%Y%m%d-%H%M%S)"
PASTA="$BACKUPS/release-${TAG}-${DATA}-$$"
mkdir -m 700 -- "$PASTA"
[[ ! -L "$PASTA" ]] || exit 1
LOG="$PASTA/mysql-backup.log"
ARQUIVOS="$PASTA/storage-app.tar.gz"
echo '===== 1. BACKUP MYSQL PADRAO DA OFICINA ====='
bash "$RELEASE/database_release.sh" backup "$TAG" | tee "$LOG"
MYSQL="$(sed -n 's/^BACKUP VALIDADO: //p' "$LOG" | tail -n 1)"
case "$MYSQL" in
  "$BACKUPS/jaime-predeploy-${TAG}-"*.sql.gz) ;;
  *) echo 'Dump SQL nao identificado no formato esperado; bloqueado.' >&2; exit 1 ;;
esac
[[ -f "$MYSQL" && -s "$MYSQL" && ! -L "$MYSQL" ]] || { echo 'Dump SQL invalido.' >&2; exit 1; }
gzip -t -- "$MYSQL"

echo '===== 2. BACKUP STORAGE/APP + EXTRACAO DE ENSAIO ====='
bash "$RELEASE/storage_release.sh" "$PROJETO" "$ARQUIVOS"

echo '===== 3. RESTAURAR DUMP NUM MYSQL ISOLADO, SEM REDE ====='
bash "$RELEASE/mysql_restore_check.sh" "$MYSQL"

echo '===== 4. FECHAR MANIFESTO SHA-256 E CONFIRMAR ====='
(
  cd "$PASTA"
  sha256sum -- "$MYSQL" "$ARQUIVOS" > SHA256SUMS
  sha256sum -c SHA256SUMS
)
cat > "$PASTA/RESULTADO.txt" <<EOF
Backup automatico da release: $TAG
MySQL: $MYSQL
Storage/app: $ARQUIVOS
Verificado: gzip + SHA256 + extracao de storage/app + importacao MySQL8.4 isolada
Escopo: copia local no disco da VPS; nao substitui copia externa/offsite.
Aviso: dump e anexos capturados com app em funcionamento, nao e snapshot atomico entre os dois.
Nao executar restauracao em producao sem plano e autorizacao explicita.
EOF
chmod 600 "$PASTA/RESULTADO.txt" "$PASTA/SHA256SUMS" "$LOG"
echo "BACKUP_DUPO_OK: $PASTA"
echo 'RESTORE_SQL_E_STORAGE_OK; DEPLOY AUTORIZADO PELO GATE DE BACKUP.'
