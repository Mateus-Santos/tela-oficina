#!/usr/bin/env bash
# Restaura dump em container MySQL 8.4 efemero: SEM REDE, SEM PORTAS, SEM VOLUMES DA OFICINA.
# O container anonimo e removido mesmo em falhas. NUNCA restaura no banco de producao.
set -Eeuo pipefail
umask 077
DUMP="${1:?Arquivo .sql.gz obrigatorio}"
[[ -f "$DUMP" && -s "$DUMP" && ! -L "$DUMP" && "$DUMP" == /* && "$DUMP" == *.sql.gz ]] || {
  echo 'BACKUP MYSQL BLOQUEADO: dump invalido' >&2; exit 1;
}
for cmd in docker gzip timeout openssl; do
  command -v "$cmd" >/dev/null || { echo "Ferramenta ausente: $cmd" >&2; exit 1; }
done
gzip -t -- "$DUMP"
docker image inspect mysql:8.4 >/dev/null || { echo 'Imagem mysql:8.4 ausente; sem pull automatico na VPS.' >&2; exit 1; }

PASSWORD="$(openssl rand -hex 32)"
NOME="sosmecanica-restore-$(date -u +%s)-$$"
INICIOU=0
limpar() {
  local codigo=$?
  trap - EXIT
  if (( INICIOU == 1 )); then docker rm -f -v "$NOME" >/dev/null 2>&1 || true; fi
  unset PASSWORD
  exit "$codigo"
}
trap limpar EXIT

echo '===== ENSAIO DE RESTAURACAO MYSQL 8.4 ISOLADO ====='
docker run -d --rm --name "$NOME" --network none --cpus=1 --memory=1024m \
  --memory-swap=1024m --security-opt no-new-privileges \
  -e "MYSQL_ROOT_PASSWORD=$PASSWORD" -e MYSQL_DATABASE=jaime_automotivo \
  mysql:8.4 >/dev/null
INICIOU=1
# A imagem oficial mysql:8.4 inicia um mysqld temporario para bootstrap.
# Um SELECT 1 via socket pode funcionar nesse mysqld, mas ele sera desligado
# antes da subida do servidor definitivo. A importacao NAO pode ocorrer antes
# de o entrypoint terminar o bootstrap e de o servidor final responder.
PRONTO=0
BOOTSTRAP_CONCLUIDO=0
for (( tentativa=1; tentativa<=90; tentativa++ )); do
  if docker logs "$NOME" 2>&1 | grep -Fq 'MySQL init process done. Ready for start up.'; then
    BOOTSTRAP_CONCLUIDO=1
  fi
  if (( BOOTSTRAP_CONCLUIDO == 1 )) && \
     docker exec -e "MYSQL_PWD=$PASSWORD" "$NOME" mysql -uroot -N -e 'SELECT 1' >/dev/null 2>&1; then
    PRONTO=1; break
  fi
  # Se a instancia realmente morreu, nao aguardar o timeout inteiro.
  if [[ "$(docker inspect -f '{{.State.Running}}' "$NOME")" != true ]]; then
    echo 'MySQL 8.4 descartavel encerrou durante inicializacao.' >&2
    docker logs "$NOME" >&2 || true
    exit 1
  fi
  sleep 2
done
(( PRONTO == 1 )) || {
  echo 'MySQL 8.4 definitivo nao inicializou no prazo: ensaio bloqueado.' >&2
  docker logs "$NOME" >&2 || true
  exit 1
}
echo 'MySQL 8.4 de ensaio: bootstrap encerrado e servidor definitivo pronto.'
# Erro do gzip OU do mysql reprova o gate (pipefail). 10min maximo de importacao.
gzip -cd -- "$DUMP" | timeout 600s docker exec -i -e "MYSQL_PWD=$PASSWORD" "$NOME" \
  mysql -uroot --default-character-set=utf8mb4 jaime_automotivo >/dev/null
TABELAS="$(docker exec -e "MYSQL_PWD=$PASSWORD" "$NOME" mysql -uroot -N -e \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='jaime_automotivo' AND table_type='BASE TABLE'")"
[[ "$TABELAS" =~ ^[0-9]+$ ]] && (( TABELAS > 0 )) || {
  echo 'Dump importado sem tabelas: restauracao nao aprovada.' >&2; exit 1;
}
echo "MYSQL_RESTAURACAO_ENSAIO_OK: $TABELAS tabelas na instancia descartavel"
