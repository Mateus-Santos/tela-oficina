# P05.1 — Evitar consultas extras nos accessors de parcelas

Base: `development` em `141e004` (05/11/2026).

## Problema confirmado por inspeção

As listagens de contas a pagar e contas a receber usam `withSum()` com as aliases
`valor_pago` e `valor_recebido`. Os modelos tinham accessors com os mesmos nomes,
que, sem `pagamentosAtivos` / `recebimentosAtivos` carregados, executavam outra
consulta `SUM()` por parcela quando o valor era acessado.

## Correção limitada

Os accessors passam a usar o agregado que já veio do SQL quando ele estiver
presente no array bruto de atributos. A prioridade dos relacionamentos
pré-carregados é preservada e, sem agregado nem relação carregada, permanece
a consulta original. `NULL` do agregado significa zero.

Não altera controller, valores persistidos, migrations, índices, deploy ou Docker.

## Validação

- `php artisan test tests/Unit/Models/ParcelaValoresAgregadosTest.php`.
- `php artisan test tests/Feature/Financeiro` com banco **exclusivo de testes**
  e ambiente previamente confirmado; nunca rodar contra o MySQL operacional.
- Na homologação, medir contagem de SQL por listagem e comparar resumos,
  saldos, estornos e status antes/depois.

## Não resolvido nesta entrega

Os controllers ainda usam `(clone $query)->get()` para carregar todas as
parcelas dos resumos. Isso é P05.2, uma mudança distinta que exige comparar
valores agregados em SQL com os resultados atuais usando dados de teste.
