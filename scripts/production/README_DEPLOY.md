# Publicacao da SOS Mecanica (Docker V3)

## Uso cotidiano: um comando no `jai`

```bash
cd ~/projetos/sosmecanica
bash scripts/deploy.sh
```

O comando pede uma confirmacao `PUBLICAR` e usa **o commit atual de `origin/master`**:

1. Dispara `modo=validar` no GitHub Actions: testa os scripts e valida/cria as imagens PHP e Nginx no GHCR.
2. Confere o resultado e bloqueia se a `master` mudar no meio da operacao.
3. Dispara `modo=publicar` e solicita aprovacao do ambiente protegido `production` pelo GitHub CLI, se a conta tiver permissao.
4. O workflow envia os scripts para a VPS e inicia o deploy das imagens ja validadas.
5. O script da VPS **antes do corte** gera um dump MySQL, copia `storage/app` (arquivos privados e publicos), verifica SHA-256, extrai os anexos em local temporario e importa o dump em MySQL 8.4 efemero **sem rede e sem portas**. Qualquer falha bloqueia o deploy.
6. So apos backup e restauracao de ensaio aprovados, troca `app` e `web`, faz `php artisan migrate --force --no-interaction` e testa `/up` e `/login`.

O dump MySQL fica em `~/backups/jaime-predeploy-<SHA>-<data>.sql.gz` e o pacote de storage, manifesto `SHA256SUMS` e comprovante ficam em `~/backups/release-<SHA>-<data>/`. Nao sao recriados containers da aplicacao nem do MySQL de producao para fazer backups; somente a restauracao de ensaio cria um MySQL descartavel **sem rede e sem volumes existentes**, removido ao terminar. O projeto `forge-db-oficina` permanece fora do fluxo.

**Limites declarados:** nao e um snapshot atomico da combinacao MySQL + uploads sob escrita simultanea. Se anexos mudarem durante a copia, a verificacao interrompe a release. O pacote cobre `storage/app`, nao logs, cache nem sessoes de `storage/framework`. Backup em disco da mesma VPS NAO substitui copia fora do servidor, e teste de restauracao isolado NAO equivale a restaurar toda a aplicacao em uma VPS nova. Nao existe remocao automatica de backups antigos; a politica de retencao/offsite precisa de aprovacao especifica.

## Seguranca e limites

- A protecao de aprovacao do ambiente GitHub `production` permanece ativa. Se a conta nao puder aprovar via API, o script para e apresenta o link para aprovacao manual.
- `migrate` executa **todas as migrations pendentes**, nao `migrate:fresh`; migrations destrutivas ou incompatíveis com a versao anterior exigem revisao separada **antes** do deploy.
- Ao falhar, o deploy tenta restaurar as imagens anteriores, mas **nao reverte alteracoes no banco**. O backup e uma recuperacao potencial, nunca um rollback automatico.
- O gate importa o dump num MySQL 8.4 novo, isolado e sem rede; extrai e compara `storage/app` em diretorio temporario. Isso comprova a restaurabilidade basica dos dois artefatos, mas nao testa uma recuperacao operacional completa da VPS. Mantenha plano de desastre e copia externa.
- `~/backups` deve ter espaco disponivel. Nao remover backups antigos automaticamente sem politica de retencao.
- Existe **um unico workflow**: `.github/workflows/deploy.yml`. Em PRs para `development` ou `master`, o job `qualidade` executa testes de deploy, performance e auditoria de dependencias **sem publicar**. Somente a execucao manual (`workflow_dispatch`) oferece `validar` e `publicar`. Push/merge nunca faz deploy automaticamente.

## Testes sem producao

```bash
bash tests/deploy/test_deploy.sh
```

O teste legado usa Docker falso para testar dump e migrations, sem banco real. `tests/deploy/test_backup_gate.sh` faz backup e extracao real de arquivos ficticios, testa falhas de seguranca e, com `BACKUP_TEST_MYSQL=1`, importa SQL ficticio em MySQL 8.4 descartavel no runner CI. Nenhum teste se conecta a producao.
