# SOS Mecânica — P02 / PHP-FPM: baseline e candidato conservador

## Estado medido na VPS — 04/11/2026 (BRT) / 05/11/2026 (UTC)

Ambiente: 2 vCPU, 7.75 GiB de RAM; 6.49 GiB disponíveis; sem swap; load average 0.01/0.02/0.00 quando medido. **Não há saturação detectada.**

| Processo/endpoint | Antes (produçao) |
|---|---:|
| Docker Laravel após GETs | 48.61 MiB; 3 PIDs (master + 2 workers) |
| Docker Nginx | 3.41 MiB |
| Docker MySQL | 549.3 MiB |
| GET /up — média 15 sequenciais | 26.68 ms |
| GET /up — P95 15 sequenciais | 28.98 ms |
| GET /login — média 15 sequenciais | 35.11 ms |
| GET /login — P95 15 sequenciais | 39.86 ms |

Esses tempos são de **baixa carga**, não prova de escalabilidade. Memória via `docker stats` não equivale à soma de RSS individuais porque páginas podem ser compartilhadas.

## Decisão experimental

A configuração atual usa `pm=dynamic`, `pm.start_servers=2`, `pm.min_spare_servers=1`, `pm.max_spare_servers=2`, `pm.max_children=4`. Como o web healthcheck chama `/up` **a cada 30 s**, trocar para `pm=ondemand` com timeout de 10–20 s pode recriar worker repetidamente; timeout maior que 30 s evita churn mas reduz a economia potencial.

**Candidato P02:** manter `pm=dynamic`, preservar `pm.max_children=4`, `pm.max_requests=500` e PHP INI, e reduzir para **um** worker inicial e um spare. É um ajuste de economia ociosa, não tuning de alta concorrência.

A mudança entra **somente na branch de desenvolvimento**, nunca diretamente na VPS. O CI executa testes estruturais e `php-fpm -tt` dentro de um container oficial PHP 8.5 isolado no GitHub runner. Isso valida sintaxe/configuração, **não comprova economia, latência, nem equivalência com a imagem PHP-base customizada**.

## Gate para promover à produção

1. Integrar PR só em `development`. Não usar o deploy V4 para esse branch.
2. Validar build Docker e homologação com banco isolado; não utilizar produção para testar alterações de configuração sem aceite.
3. Comparar `docker stats` após estabilizar a aplicação, quantidade de workers e TTFB P95 de `/up`, `/login` e rotas representativas autenticadas, em condições comparáveis.
4. Só recomendar produção se houver **economia mensurável** sem piora relevante de latência, erros ou concorrência; caso contrário, reverter a mudança do FPM na branch.
5. Nenhuma alteração em MySQL, migrations, dados de clientes, volumes ou rede Docker.

Benchmark auxiliar (somente leitura na VPS, **não executado pelo instalador**): `bash scripts/performance/benchmark_http.sh`. Faz 60 GETs públicos no total (30 em `/up`, 30 em `/login`) com no máximo 3 simultâneos. Não usar durante incidentes ou pico de acesso. Esse benchmark é **complementar**, não substitui medição de telas autenticadas ou uma homologação real.

Fonte técnica: [PHP-FPM configuration](https://www.php.net/manual/en/install.fpm.configuration.php).
