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
5. O script da VPS faz backup com `mysqldump` **antes** de substituir `app` e `web`, confere `gzip -t`, faz `php artisan migrate --force --no-interaction` **depois** da troca e testa `/up` e `/login`.

O backup fica em `~/backups/jaime-predeploy-<SHA>-<data>.sql.gz` na VPS. Nenhum container de MySQL e recriado e o projeto `forge-db-oficina` fica fora do fluxo.

## Seguranca e limites

- A protecao de aprovacao do ambiente GitHub `production` permanece ativa. Se a conta nao puder aprovar via API, o script para e apresenta o link para aprovacao manual.
- `migrate` executa **todas as migrations pendentes**, nao `migrate:fresh`; migrations destrutivas ou incompatíveis com a versao anterior exigem revisao separada **antes** do deploy.
- Ao falhar, o deploy tenta restaurar as imagens anteriores, mas **nao reverte alteracoes no banco**. O backup e uma recuperacao potencial, nunca um rollback automatico.
- `gzip -t` comprova integridade de compressao, nao equivale a um teste real de restauracao. Mantenha testes periodicos de restore e copia externa dos backups.
- `~/backups` deve ter espaco disponivel. Nao remover backups antigos automaticamente sem politica de retencao.
- O workflow `deploy.yml` continua com gatilho **somente manual**; push/merge nunca publica sozinho. A suite `ci-deploy.yml` e automatica em Pull Requests.

## Testes sem producao

```bash
bash tests/deploy/test_deploy.sh
```

Usa um executavel Docker falso para simular sucesso de backup, corrupcao/falha do dump, imagem de app incorreta e falha de migration. Nao toca Docker nem bancos reais. O GitHub Actions tambem roda estes testes em PRs.
