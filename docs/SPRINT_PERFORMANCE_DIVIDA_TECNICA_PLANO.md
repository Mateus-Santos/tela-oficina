# SOS MECÂNICA — Sprints de performance e dívida técnica (plano por módulos)

**Base auditada:** `master` em `b5b482b`, 04/11/2026. **Prazo informado:** 07/11/2026. Não confundir prazo com conclusão garantida: a base contém mais de 800 arquivos e exige testes de regressão.

## Fonte das tarefas e limitação

- Foi recebido um conjunto de 32 URLs do ClickUp associado à sprint de limpeza, padronização e performance e o título **SPRINT 11 — Auditoria de performance e dívida técnica** acompanhado de 10 URLs. A descrição detalhada de cada subtask não estava disponível para consulta autenticada. Assim, a divisão abaixo representa a priorização técnica proposta **a partir do pedido**, não reclassificação afirmativa das tarefas individuais do ClickUp.
- Escopo obrigatório adicional fornecido verbalmente: menos RAM/CPU, dependências atuais, vulnerabilidades conhecidas, SQL injection, CSS/JS/Blade, modularização, duplicações, requests/actions, integridade cliente/veículo/nota, backend, código legado, empacotamento e testes.

## Inventário real do GitHub

| Pasta/categoria | Arquivos |
|---|---:|
| app/ | 242 |
| resources/ | 245 |
| app/Http/Controllers/ | 39 |
| app/Http/Requests/ | 46 |
| app/Actions/ | 62 |
| resources/views/ | 179 |
| resources/js/ | 40 |
| resources/scss/ | 23 |
| tests/ | 51 |
| public/vendor/ | 78 (13,7 MB versionados) |

Observações verificadas: Waypoints referenciado duas vezes em `layouts/layout.blade.php`; cinco outras bibliotecas e `php-email-form` carregadas globalmente nesse layout; `npm` com Vite e `webpack.mix.js` legado coexistem; `ClienteController` ainda chama `$request->validate`; PHP-FPM com `pm=dynamic`, dois processos iniciais e no máximo quatro. **Nenhuma dessas evidências demonstra, por si só, gargalo mensurado**.

## Prioridade e entregas

| Módulo | Foco | Categoria | Condição de aceite |
|---|---|---|---|
| **P01 — nesta entrega** | Corrigir execução duplicada de Waypoints, cache imutável de assets Vite, inventário e medidor sem alterações de produção | Performance | Testes CI, diff revisado, build Vite e validação Nginx no fluxo Docker antes de publicar |
| **P02** | Baseline em VPS e decisão sobre PHP-FPM `ondemand` vs `dynamic`, OPcache, workers, Nginx e métricas | RAM/CPU | Memória RSS/PIDs, CPU e p95 de rotas representativas antes/depois, sem regredir disponibilidade |
| **P03** | Dependências Composer/NPM, versões, pacotes abandonados e advisories | Atualização e segurança | `composer audit --locked`, `npm audit`, testes PHP e `npm ci && npm run build`; versões fixadas no lock |
| **P04** | Inventário de CSS/SCSS, biblioteca global por rota, remoção de legado comprovadamente não referenciado e JS por página | Frontend | Sem JS/CSS duplicado; comparativos de bytes transferidos, Web Vitals, formulários e modais funcionando |
| **P05** | Query profiling, N+1, eager loading, índices/EXPLAIN, paginação e caches com invalidação | Banco/performance | Medição de queries e tempo nas telas notas/compras/estoque antes/depois; SQL de produção preservado |
| **P06** | Requests/Actions/Services, SQL dinâmico, sanitização, controllers grandes e responsabilidades | Dívida técnica | Testes de autorização/validação e SQL parametrizado; nenhuma alteração de fluxo funcional sem teste |
| **P07** | Integridade e vínculo cliente ↔ veículo ↔ OS ↔ nota ↔ recebimento, totais em centavos e transações | Dívida técnica crítica | Testes end-to-end em banco isolado; dados reais intocados |
| **P08** | Limpeza geral, remoção de código morto com prova de não uso, consistência Blade e fechamento | Ambas | Testes de regressão, métricas finais, revisão do diff, documentação e deploy controlado |

## Regras do trabalho

1. Não migrar Blade para Vue agora; é outra iniciativa.
2. Menos linhas **não** é métrica de desempenho. Remover duplicação e lógica redundante; conservar legibilidade.
3. Não fazer upgrade em massa sem solver, lock, audit e testes; nenhum `npm audit fix --force` automático.
4. Nenhum `migrate:fresh`, `db:wipe`, remoção de volume ou alteração da VPS pelo instalador da sprint.
5. Uma branch por iniciativa/sprint, nao por microtarefa; PRs revisados e testados. Bug independente de producao pode usar hotfix pequeno a partir de master, seguido de sincronizacao com desenvolvimento. Nao misturar SQL/contratos com ajustes apenas de CSS/JS sem validacao especifica.
6. Gate da performance: latência, consultas, CPU, memória RSS, bytes de resposta e taxa de erros **antes e depois**, por tela e em condições comparáveis.
7. Deploy Docker V4 já está configurado; apenas usar após revisão e integração na `master`.

## Fontes oficiais consultadas

- Laravel 13 — Deployment: https://laravel.com/framework/docs/deployment
- Vite — Static Asset Handling: https://vite.dev/guide/assets
- PHP-FPM — Configuração: https://www.php.net/manual/pt_BR/install.fpm.configuration.php
- Composer audit: https://getcomposer.org/doc/03-cli.md#audit
- npm audit: https://docs.npmjs.com/cli/commands/npm-audit/
