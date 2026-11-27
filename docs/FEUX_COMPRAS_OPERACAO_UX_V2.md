# FEUX — Compras / Devolução operacional · UX V2 · 2026-11-25

## Escopo entregue

- Compras: atalhos GET por estado na listagem (`pendente`, `conferindo`, `aprovada`, `cancelada`), mantendo os demais filtros e retirando `page` para evitar paginação inválida.
- Detalhe da compra: navegação por âncoras para status, devoluções e integrações (última só para compras aprovadas); sem novas rotas.
- Devolução operacional D01A: prévia dinâmica das quantidades exatas em milésimos, mensagens junto aos campos e prevenção visual de envio inválido.
- CSS escopado, teclado, foco, alvos de toque, uso opcional de JS. Sem biblioteca nova.
- Modelo Laravel, controllers, Actions, migrations, dados, estoque e contas a pagar **não foram alterados**.

## Regras de negócio preservadas

- D01A é devolução operacional: exige conferência física, registra saída, não emite NF-e e não desconta automaticamente Contas a Pagar.
- D04 é devolução documental: documentos fiscais fora do sistema, item recusado sem entrada não baixa estoque, devolução após entrada movimenta estoque, abatimento pode ficar pendente de conciliação.
- `operacao_uuid` e validações/permissões server-side não mudaram; botão desabilitado é somente proteção de UX, não regra de autorização.
- A NF original da compra e todos os contratos financeiros permanecem intactos.
- Não interfere com FEUX L02CR/Notas; os 23 erros Laravel do gate anterior continuam pendentes de correção na `development`.

## Homologação exigida

- `node --test tests/js/*.test.mjs` e `node --check resources/js/compras/devolucao-operacional.js`.
- `npm run build` quando o worktree tiver dependências, pois o script não instala pacotes.
- Teste manual autenticado: compras status/filter, `show`, D01A sem item, 0.001, acima do saldo, confirmar caixa física, erro do backend, tentativa sem permissão, D04 intacta.
- Testes Laravel em schema descartável antes de qualquer integração (bloqueado até reconciliar development/Notas).

## Estrutura de commits

1. `feat(feux-compras): navegacao e previa operacional D01A`
2. `test(feux-compras): contratos e validacao da UX operacional`
3. `docs(feux-compras): regras e handoff da UX V2`

## Integração

Branch isolada originada de `7afcf06` (FEUX Devoluções V1), que nasceu do commit local `bd9c17b` da development. Não realizar merge direto sem resolver divergência da `development` remota e revisar os commits precedentes. Transportar somente commits FEUX homologados. Nada foi enviado a produção.
