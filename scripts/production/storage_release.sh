#!/usr/bin/env bash
# Cria e verifica copia de storage/app com extracao real em diretorio temporario.
# Nao acessa MySQL nem aplica migrations. O wrapper limita a origem a producao.
set -Eeuo pipefail
umask 077

ROOT="${1:?Diretorio raiz Laravel obrigatorio}"
ARQUIVO="${2:?Destino .tar.gz obrigatorio}"

falhar() { echo "BACKUP STORAGE BLOQUEADO: $*" >&2; exit 1; }
for cmd in tar gzip sha256sum diff mktemp df du stat; do
  command -v "$cmd" >/dev/null || falhar "Ferramenta ausente: $cmd"
done
[[ "$ROOT" == /* && "$ARQUIVO" == /* ]] || falhar 'Caminhos devem ser absolutos.'
[[ -d "$ROOT/storage/app" && ! -L "$ROOT" && ! -L "$ROOT/storage" && ! -L "$ROOT/storage/app" ]] || falhar 'storage/app real nao encontrado ou link simbolico.'
[[ "$ARQUIVO" == *.tar.gz ]] || falhar 'Destino deve terminar em .tar.gz.'
# Sem seguir links externos ou arquivar apenas um ponteiro no lugar do anexo.
if find "$ROOT/storage/app" -type l -print -quit | grep -q .; then
  falhar 'Links simbolicos dentro de storage/app precisam de revisao (conteudo nao seria copiado).'
fi
PASTA="${ARQUIVO%/*}"
[[ -d "$PASTA" && ! -L "$PASTA" ]] || falhar 'Pasta privada de destino ausente ou link simbolico.'
[[ "$(stat -c '%a' "$PASTA")" == 700 ]] || falhar 'Diretorio de backup precisa ter modo 700.'
[[ ! -e "$ARQUIVO" && ! -L "$ARQUIVO" ]] || falhar 'Arquivo destino ja existe.'
[[ ! -e "$ARQUIVO.part" && ! -L "$ARQUIVO.part" ]] || falhar 'Arquivo temporario ja existe.'

ORIGEM_KB="$(du -sk -- "$ROOT/storage/app" | awk '{print $1}')"
LIVRE_KB="$(df -Pk "$PASTA" | awk 'NR==2 {print $4}')"
[[ "$ORIGEM_KB" =~ ^[0-9]+$ && "$LIVRE_KB" =~ ^[0-9]+$ ]] || falhar 'Nao foi possivel medir espaco em disco.'
# Copia comprimida + extracao de ensaio + margem; falhar fechado se incerto.
(( LIVRE_KB > ORIGEM_KB * 3 + 262144 )) || falhar 'Espaco insuficiente para backup e extracao de ensaio (margem de 256 MiB).'

TMP_RESTAURAR=''
limpar() {
  local codigo=$?
  trap - EXIT
  if [[ -n "$TMP_RESTAURAR" && "$TMP_RESTAURAR" == "$PASTA/.storage-restore-"* ]]; then
    rm -rf -- "$TMP_RESTAURAR"
  fi
  if (( codigo != 0 )); then
    rm -f -- "$ARQUIVO.part"
    echo 'FALHA: nenhuma copia de storage foi certificada.' >&2
  fi
  exit "$codigo"
}
trap limpar EXIT

echo '===== BACKUP ARQUIVOS PRIVADOS/PUBLICOS: storage/app ====='
tar -C "$ROOT" -czf "$ARQUIVO.part" -- storage/app
[[ -s "$ARQUIVO.part" ]] || falhar 'Arquivo de storage vazio.'
gzip -t -- "$ARQUIVO.part"
# Extracao de ensaio em local que nao e servido pela web nem e volume da aplicacao.
TMP_RESTAURAR="$(mktemp -d "$PASTA/.storage-restore-XXXXXXXX")"
chmod 700 "$TMP_RESTAURAR"
tar -C "$TMP_RESTAURAR" --no-same-owner --no-same-permissions -xzf "$ARQUIVO.part"
[[ -d "$TMP_RESTAURAR/storage/app" ]] || falhar 'Estrutura storage/app ausente na extracao.'
# Verifica bytes, nomes e links simbolicos; mudanca simultanea no original bloqueia.
diff --no-dereference -qr -- "$ROOT/storage/app" "$TMP_RESTAURAR/storage/app" >/dev/null || \
  falhar 'Arquivo alterado durante captura ou divergencia na restauracao de ensaio.'
mv -- "$ARQUIVO.part" "$ARQUIVO"
gzip -t -- "$ARQUIVO"
echo "STORAGE_BACKUP_OK: $ARQUIVO"
echo 'STORAGE_RESTAURACAO_ENSAIO_OK'
