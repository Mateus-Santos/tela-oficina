# SOS Mecânica — Docker de produção, entrega inicial (NÃO implantada)

## Diagnóstico que fundamenta a arquitetura

- VPS: Laravel 13.38.0, PHP 8.5.12; usuário efetivo 1000:1000.
- `storage` no host pertence a 1000:1000 e usa modo 0700. **Não fazer chmod 777**.
- Sessão `database`, cache `file`, fila `sync`, sem tarefas agendadas.
- Stack atual: Sail com bind `/var/www/sos-mecanica:/var/www/html` e MySQL 8.4
  persistido em `sos-mecanica_sail-mysql`; Laravel exposto só em 127.0.0.1:8080.
- Forge DB Oficina é independente e **não faz parte** desta implantação.

## Arquitetura da primeira migração (sem interrupção do MySQL)

`compose.prod.yml` cria somente `app` e `web`, em projeto Compose **separado**
`sosmecanica-prod` e com porta 127.0.0.1:18081. O container `app` usa a
rede **externa** `sos-mecanica_sail` para alcançar o serviço `mysql` já ativo.
Não declara um serviço MySQL, não monta seu volume e **não recria o banco**.
Esta fase mantém o MySQL gerenciado pela stack antiga. A separação administrativa
futura do MySQL deve ter procedimento próprio e backup testado.

A aplicação não tem bind de código PHP: `COPY` incorpora o código à imagem.
O armazenamento `./storage` permanece montado no `app` (leitura/escrita), e
apenas `./storage/app/public` é montado em `web` como somente leitura.
Ambos os containers rodam com UID/GID 1000:1000 (compatível com a VPS).
A variável `APP_KEY` vem do `.env` existente via `env_file` **em runtime**;
jamais execute `key:generate` sobre a produção.

## Passo A — Inspecionar código e variáveis, sem tocar a VPS

Revisar extensões PHP realmente usadas fora de Composer, `.env` sem revelar
segredos, `TRUSTED_PROXIES`, links de arquivos privados, tamanho de uploads,
permissões e Nginx de host. As imagens e o Compose aqui NÃO foram testados em
Docker pelo agente: precisam de build e validação reais.

## Passo B — Construir imagens versionadas (no jai, sem alterar produção)

```bash
cd ~/projetos/sosmecanica
bash scripts/production/build_images.sh $(git rev-parse --short=12 HEAD)
```

O script faz `docker build` do PHP+Composer+Vite e Nginx; nenhuma das imagens
possui `.env`, `vendor` de desenvolvimento, dados do Forge ou arquivos de usuários.
O build falha se alguma extensão/dependência exigida estiver ausente.

**Cuidado:** o `Dockerfile.app.dockerignore` é específico para esse Dockerfile.
Confirme que nenhum recurso necessário foi excluído antes de homologar.

## Passo C — Homologar em rede/volumes totalmente separados

```bash
cp docker/production/homolog.env.example .env.homolog
chmod 600 .env.homolog
# Editar .env.homolog: segredos NOVOS e tag construída; não copiar APP_KEY real.
# Ex.: php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'
docker compose --env-file .env.homolog -f compose.homolog.yml config --quiet
docker compose --env-file .env.homolog -f compose.homolog.yml up -d
# Apenas no BANCO DE HOMOLOGAÇÃO recém-criado e isolado:
docker compose --env-file .env.homolog -f compose.homolog.yml exec -T app php artisan migrate --force
curl -fsS http://127.0.0.1:18080/up
```

**NÃO usar o backup de dados reais sem sanitização.** Confira manualmente
cadastro, login, consultas, produtos, OS, notas, compras, imagens e uploads.
Prove que a base da homologação não enxerga registros da produção.

## Passo D — Preparação de produção (AINDA NÃO EXECUTAR)

1. Backup **pós-importação**, restaurável (MySQL + uploads + .env), cópia externa.
2. Confirmar `docker network inspect sos-mecanica_sail`, health do MySQL e nomes.
3. Conferir tag do commit, SHA das imagens, permissões e `APP_KEY` existente.
4. Distribuir as duas imagens por registry privado ou `docker save`/`docker load`.
5. Validar configurações sem criar container: `SOS_IMAGE_TAG=<tag> docker compose -f compose.prod.yml config --quiet`.
6. Iniciar a nova camada **em paralelo** (porta 18081). Aguardar healthcheck;
   testar apenas `/up` e rotas de leitura autorizadas; não duplicar processos de escrita.
7. Ajustar upstream no **Nginx do host** de 8080 para 18081 em janela
   coordenada; executar `nginx -t` e reload. Monitorar logs e funcionalidades.
8. Somente após aceite: `docker compose stop laravel.sosmecanica` na stack
   **antiga**; **não** parar MySQL. A remoção do Sail é etapa controlada.

## Reversão planejada

- Se nova aplicação falhar, apontar Nginx do host de volta à porta 8080
  (desde que Sail ainda esteja ligado); testar e recarregar Nginx.
- Se já parou Sail, iniciar APENAS ele: `docker compose up -d --no-deps laravel.sosmecanica`
  (após confirmar porta 8080 e serviço MySQL intactos).
- Nunca usar `down -v`, `prune --volumes`, `migrate:fresh`, `db:wipe`.
- Rollback do código NÃO desfaz migrations nem alterações em dados. Nesta
  migração, não executar migrations de schema sem análise própria.

## Limites de recursos e gráficos

`compose.limits.yml` é um **overlay opcional** para testar aproximadamente 1,48 GB de RAM
na camada app+web (`1350m` + `128m`) e 1,25 vCPU somados, sem limitar MySQL.
Para homologar limites, usar:

```bash
docker compose --env-file .env.homolog \
  -f compose.homolog.yml -f compose.limits.yml config --quiet
```

Não ativar quotas sem medir picos, uploads, geração de PDF e importações.
Planejado: Beszel privado para histórico de CPU/RAM/rede/disco, alertas e
medição do Laravel + MySQL + Forge separadamente.

## Segurança e pendências antes de produção

- Verificar `docker build` real; php/extensões e publicação de imagens não foram
  executados no sandbox do assistente.
- Não habilitar deploy automático enquanto o pipeline antigo recria MySQL.
- Validar se o Nginx host encaminha `Host`, `X-Forwarded-Proto` corretamente
  e ajustar `TRUSTED_PROXIES` para a nova rede de Docker.
- Confirmar ferramentas PHP opcionais no código (ex.: Imagick), pois o ZIP
  fornecido contém dependências e config, mas não o código completo.
- Os uploads existentes usam permissão 0700; UID1000 foi fixado para respeitar.
- Registrar smoke tests, processo de rollback testado e backup externo.
- Se volumes não montarem, não executar scripts de `chown -R` no host sem plano.
