#!/usr/bin/env bash
# Integracao local real do backup de storage, mais contrato de integracao com deploy.
set -Eeuo pipefail
umask 077
cd "$(dirname "$0")/../.."
for script in scripts/production/backup_release.sh scripts/production/storage_release.sh \
              scripts/production/mysql_restore_check.sh; do
    bash -n "$script"
done

grep -Fq 'bash "$RELEASE/backup_release.sh" "$TAG"' scripts/production/deploy_release.sh
grep -Fq 'backup_release.sh' .github/workflows/deploy.yml
grep -Fq 'storage_release.sh' .github/workflows/deploy.yml
grep -Fq 'mysql_restore_check.sh' .github/workflows/deploy.yml
grep -Fq 'bash tests/deploy/test_backup_gate.sh' .github/workflows/deploy.yml
grep -Fq -- '--network none' scripts/production/mysql_restore_check.sh
# Regressao: nao considerar pronto o MySQL temporario do bootstrap.
grep -Fq 'MySQL init process done. Ready for start up.' scripts/production/mysql_restore_check.sh
grep -Fq -- 'gzip -cd -- "$DUMP" | timeout 600s docker exec -i' scripts/production/mysql_restore_check.sh

TEMP="$(mktemp -d)"
trap 'rm -rf -- "$TEMP"' EXIT
mkdir -p "$TEMP/app/storage/app/private/faturas" "$TEMP/app/storage/app/public" "$TEMP/backup"
chmod 700 "$TEMP/backup"
printf 'PDF ficticio para prova\n' > "$TEMP/app/storage/app/private/faturas/fatura.pdf"
printf 'Foto ficticia\000binaria' > "$TEMP/app/storage/app/public/foto.png"

ARQUIVO="$TEMP/backup/storage-app.tar.gz"
bash scripts/production/storage_release.sh "$TEMP/app" "$ARQUIVO"
test -s "$ARQUIVO"
gzip -t "$ARQUIVO"
# Uma segunda execucao nao pode substituir backup anterior.
if bash scripts/production/storage_release.sh "$TEMP/app" "$ARQUIVO" > "$TEMP/repetir.log" 2>&1; then
    echo 'FALHA: arquivo anterior foi sobrescrito' >&2; exit 1
fi
# Uma entrada de origem via symlink nao pode ser aceita.
mkdir -p "$TEMP/bad/storage"
ln -s "$TEMP/app/storage/app" "$TEMP/bad/storage/app"
if bash scripts/production/storage_release.sh "$TEMP/bad" "$TEMP/backup/nao.tar.gz" > "$TEMP/symlink.log" 2>&1; then
    echo 'FALHA: origem simbolica aceita' >&2; exit 1
fi
[[ ! -e "$TEMP/backup/nao.tar.gz" ]]
# Links presentes DENTRO do arquivo nao devem resultar em backup incompleto.
ln -s /etc/passwd "$TEMP/app/storage/app/atalho-externo"
if bash scripts/production/storage_release.sh "$TEMP/app" "$TEMP/backup/nao2.tar.gz" > "$TEMP/symlink2.log" 2>&1; then
    echo 'FALHA: link simbolico interno foi aceito' >&2; exit 1
fi
[[ ! -e "$TEMP/backup/nao2.tar.gz" ]]
# Deletar um arquivo da origem nao pode ser confundido com backup consistente.
[[ -e "$TEMP/app/storage/app/private/faturas/fatura.pdf" ]]

echo 'OK: backup de storage, extracao de ensaio, preservacao e bloqueio de symlinks.'

if [[ "${BACKUP_TEST_MYSQL:-0}" == 1 ]]; then
    # CI executa um banco descartavel REAL com rede desabilitada.
    printf 'CREATE TABLE restore_gate_probe (id INT PRIMARY KEY);\nINSERT INTO restore_gate_probe VALUES (1);\n' | gzip -1 > "$TEMP/backup/mysql-ficticio.sql.gz"
    bash scripts/production/mysql_restore_check.sh "$TEMP/backup/mysql-ficticio.sql.gz"
    echo 'OK: ensaio de restauracao MySQL 8.4 descartavel.'
fi
