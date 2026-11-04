#!/usr/bin/env bash
# Testes de contrato, positivos e negativos, sem Docker real ou rede.
set -Eeuo pipefail
cd "$(dirname "$0")/../.."

bash -n scripts/deploy.sh scripts/production/database_release.sh \
  scripts/production/deploy_release.sh
bash scripts/deploy.sh --help | grep -q 'Uso:'

test -f .github/workflows/deploy.yml
grep -Fq 'database_release.sh' scripts/production/deploy_release.sh
grep -Fq 'database_release.sh' .github/workflows/deploy.yml
grep -Fq 'database_release.sh' scripts/production/deploy_release.sh
grep -Fq 'php artisan migrate --force --no-interaction' scripts/production/database_release.sh

TEMP="$(mktemp -d)"
trap 'rm -rf "$TEMP"' EXIT
mkdir -p "$TEMP/mockbin"
cat > "$TEMP/mockbin/docker" <<'MOCK'
#!/usr/bin/env bash
set -euo pipefail
if [[ "$1" == inspect ]]; then
  if [[ "$3" == '{{.State.Health.Status}}' ]]; then echo healthy; exit 0; fi
  if [[ "$3" == '{{.Config.Image}}' ]]; then
    echo "sosmecanica-app:${MOCK_TAG:-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa}"
    exit 0
  fi
fi
if [[ "$1" == exec && "$2" == sosmecanica-prod-app-1 && "$3" == php ]]; then
  if [[ "${MOCK_BAD_DB:-0}" == 1 ]]; then echo 'Banco incorreto' >&2; exit 25; fi
  echo 'Banco de producao confirmado: jaime_automotivo'
  exit 0
fi
if [[ "$1" == exec && "$2" == sos-mecanica-mysql-1 ]]; then
  if [[ "${MOCK_DUMP_FAIL:-0}" == 1 ]]; then
    echo 'Falha no dump simulada' >&2
    exit 21
  fi
  printf '%s\n' '-- MySQL dump simulado' 'CREATE TABLE teste (id int);'
  exit 0
fi
if [[ "$1" == exec && "$2" == sosmecanica-prod-app-1 && "$3" == sh ]]; then
  if [[ "${MOCK_MIGRATE_FAIL:-0}" == 1 ]]; then exit 22; fi
  echo migrate >> "$MOCK_LOG"
  exit 0
fi
echo "Comando Docker inesperado: $*" >&2
exit 23
MOCK
chmod +x "$TEMP/mockbin/docker"
export HOME="$TEMP/home" PATH="$TEMP/mockbin:$PATH"
export MOCK_LOG="$TEMP/migrated.log" MOCK_TAG=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa
mkdir -p "$HOME"

# Backup positivo, com gzip valido e permissoes restritas.
bash scripts/production/database_release.sh backup "$MOCK_TAG"
mapfile -t ARQUIVOS < <(find "$HOME/backups" -maxdepth 1 -name '*.sql.gz' -type f)
test "${#ARQUIVOS[@]}" -eq 1
gzip -t "${ARQUIVOS[0]}"
test "$(stat -c '%a' "${ARQUIVOS[0]}")" = 600
test "$(stat -c '%a' "$HOME/backups")" = 700

echo 'OK: backup validado com Mock Docker'

# Executar migrations somente com a imagem correta.
bash scripts/production/database_release.sh migrate "$MOCK_TAG"
test "$(wc -l < "$MOCK_LOG")" -eq 1

MOCK_TAG=bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb \
  bash scripts/production/database_release.sh migrate \
    aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa \
  > "$TEMP/err_migrate.log" 2>&1 && {
    echo 'FALHA: tag divergente nao bloqueada' >&2; exit 1;
  }
test "$(wc -l < "$MOCK_LOG")" -eq 1

echo 'OK: migrations bloqueadas com imagem divergente'

# Dump com erro nao gera artefato final, mesmo com gzip produzindo bytes.
ANTES="$(find "$HOME/backups" -maxdepth 1 -name '*.sql.gz' | wc -l)"
if MOCK_DUMP_FAIL=1 bash scripts/production/database_release.sh backup "$MOCK_TAG" \
  > "$TEMP/err_dump.log" 2>&1; then
  echo 'FALHA: mysqldump com erro foi aceito' >&2; exit 1
fi
DEPOIS="$(find "$HOME/backups" -maxdepth 1 -name '*.sql.gz' | wc -l)"
test "$ANTES" = "$DEPOIS"
test -z "$(find "$HOME/backups" -name '*.part' -print)"

echo 'OK: falha no backup bloqueia deploy e remove arquivo parcial'

if MOCK_BAD_DB=1 bash scripts/production/database_release.sh backup "$MOCK_TAG" \
  > "$TEMP/err_db.log" 2>&1; then
  echo 'FALHA: banco incorreto nao foi bloqueado' >&2; exit 1
fi
test "$(find "$HOME/backups" -maxdepth 1 -name '*.sql.gz' | wc -l)" = "$ANTES"
echo 'OK: banco incorreto bloqueia backup e deploy'

# Falha de migration nao e mascarada.
if MOCK_MIGRATE_FAIL=1 bash scripts/production/database_release.sh migrate "$MOCK_TAG" \
  > "$TEMP/err_migration.log" 2>&1; then
  echo 'FALHA: erro na migration foi ignorado' >&2; exit 1
fi

echo 'OK: falha de migration retorna erro'
echo 'TODOS OS TESTES DE DEPLOY PASSARAM'

# Exercita tambem o orquestrador: valida -> publica -> aprova, sem GitHub real.
mkdir -p "$TEMP/mockgit" "$TEMP/root"
cat > "$TEMP/mockgit/git" <<'MOCKGIT'
#!/usr/bin/env bash
set -euo pipefail
case "$*" in
  'rev-parse --show-toplevel') echo "$MOCK_ROOT" ;;
  'status --porcelain') ;;
  'remote get-url origin') echo 'https://github.com/Mateus-Santos/tela-oficina.git' ;;
  'fetch --no-tags origin master') ;;
  'rev-parse origin/master') echo 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' ;;
  *) echo "Git mock inesperado: $*" >&2; exit 3 ;;
esac
MOCKGIT
cat > "$TEMP/mockgit/gh" <<'MOCKGH'
#!/usr/bin/env bash
set -euo pipefail
case "$1 $2" in
  'auth status') exit 0 ;;
  'workflow run')
     if [[ " $* " == *' modo=validar'* ]]; then
       echo validar >> "$MOCK_GH_LOG"; echo 101 > "$MOCK_STATE"
     elif [[ " $* " == *' modo=publicar'* ]]; then
       echo publicar >> "$MOCK_GH_LOG"; echo 102 > "$MOCK_STATE"
     else exit 4; fi ;;
  'run list')
     if [[ "$*" == *'.[0].databaseId // 0'* ]]; then
       if [[ -f "$MOCK_STATE" ]]; then cat "$MOCK_STATE"; else echo 100; fi
     else
       if [[ -f "$MOCK_STATE" ]]; then cat "$MOCK_STATE"; fi
     fi ;;
  'run watch')
     echo "watch $3" >> "$MOCK_GH_LOG"
     if [[ "$3" == 101 && "${MOCK_VALIDATE_FAIL:-0}" == 1 ]]; then exit 5; fi ;;
  'api --method') echo aprovar >> "$MOCK_GH_LOG" ;;
  'api repos/'*)
     PARAM="${*: -1}"
     if [[ "$*" == *'/pending_deployments'* ]]; then echo 3184029496
     elif [[ "$PARAM" == .status ]]; then echo waiting
     elif [[ "$PARAM" == .conclusion ]]; then echo success
     elif [[ "$PARAM" == .head_sha ]]; then echo aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa
     else echo "GH API mock invalido: $*" >&2; exit 6; fi ;;
  *) echo "GH mock inesperado: $*" >&2; exit 7 ;;
esac
MOCKGH
chmod +x "$TEMP/mockgit/git" "$TEMP/mockgit/gh"
export MOCK_ROOT="$TEMP/root" MOCK_GH_LOG="$TEMP/gh.log" MOCK_STATE="$TEMP/gh-state"

printf 'PUBLICAR\n' | PATH="$TEMP/mockgit:$PATH" bash scripts/deploy.sh

test "$(grep -xc validar "$MOCK_GH_LOG")" = 1
test "$(grep -xc publicar "$MOCK_GH_LOG")" = 1
test "$(grep -xc aprovar "$MOCK_GH_LOG")" = 1

echo 'OK: comando unico validou, publicou e aprovou em ambiente simulado'

rm -f "$MOCK_STATE" "$MOCK_GH_LOG"
if printf 'PUBLICAR\n' | MOCK_VALIDATE_FAIL=1 PATH="$TEMP/mockgit:$PATH" \
    bash scripts/deploy.sh > "$TEMP/cli_fail.log" 2>&1; then
  echo 'FALHA: validacao reprovada nao bloqueou publicacao' >&2; exit 1
fi
test "$(grep -xc validar "$MOCK_GH_LOG")" = 1
! grep -qx publicar "$MOCK_GH_LOG"
echo 'OK: falha da validacao bloqueou o deploy'
echo 'TODOS OS TESTES DE ORQUESTRACAO PASSARAM'
