# Usabilidade U02 — Contrato Back-end para listagem de Compras

**Base de referência:** `93cdd8af` (`development` com U01 integrada).

O `CompraController@index` fornece, em cada `Compra` paginada, atributos **transitórios** (não persistidos):

- `$compra->integracao_estoque_status`:
  - `sem_itens`: nenhum item registrado;
  - `pendente`: há itens, mas nenhuma entrada registrada;
  - `parcial`: alguns itens possuem entrada;
  - `lancado`: todos os itens possuem entrada;
  - `estornado`: compra cancelada, entradas com estornos identificados para todos os itens;
  - `verificar_estorno`: compra cancelada com entrada não inteiramente estornada pela operação reconhecida;
  - `sem_entrada`: compra cancelada sem entradas registradas.
- `$compra->integracao_financeiro_status`:
  - `nao_vinculada`: não há Conta a Pagar, nem no vínculo novo nem no legado;
  - `vinculada`: existe vínculo com uma Conta a Pagar **não cancelada**;
  - `vinculo_cancelado`: só existem vínculos a contas canceladas.
- Atributos técnicos (contagens e flags) `u02_total_itens`, `u02_itens_com_entrada`,
  `u02_itens_com_estorno`, `u02_pivo_conta_ativa`, `u02_legado_conta_ativa`,
  `u02_pivo_conta_cancelada` e `u02_legado_conta_cancelada`.

**Atenção ao Front-end:** não interpretar `vinculada` como **paga** ou **quitada**;
não interpretar `lancado` como prova de conciliação quantitativa (indica presença
histórica de entrada por item). `verificar_estorno` precisa de badge de alerta,
não de confirmação. Nas Compras canceladas, sempre manter o estado visível e claro.

O Back-end usa `withCount`/`withExists` no SQL da listagem, sem carregar relações
por registro, e não altera `compra.show`, migrations, valores ou movimentos.

**Frontend:** aplicar badges responsivas exclusivamente na listagem
`resources/views/compra/index.blade.php`, sem consultar o banco dentro da Blade.
Estados simples sugeridos: Estoque / Financeiro em colunas ou badges distintos.

**Garantia de compatibilidade:** o patch não exige migrations nem configurações
novas; execuções de testes são restritas a MySQL efêmero isolado.
