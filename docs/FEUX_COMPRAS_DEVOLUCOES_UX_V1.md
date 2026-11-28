# FEUX — Compras e Devoluções · UX V1 · 2026-11-25

## Responsabilidade e isolamento

- Trabalho isolado na branch `feature/feux-compras-devolucoes-ux-v3-20261125`, baseada no HEAD commitado do repositorio `~/projetos/sosmecanica` (development local) com contrato D04. O SHA da base consta no log do script. A FEUX original (inclusive alteracoes locais no menu) nao e modificada.
- Não altera `development`, `master`, tabelas, migrações, Actions ou controllers.
- As diferenças FEUX/`development` e os 23 erros do gate L02CR (Nota/O.S.) **permanecem bloqueadores de integração**, não impedindo melhorias isoladas de compras.
- Não é um PR de integração automática. O responsável pela `development` deverá reconciliar as versões e executar CI.

## Entrega frontend

1. Devolução documental D04: prévia acessível de itens/quantidades, limites por tratamento (`recusado` x `estoque`), mensagens por item e inibição visual de envio inválido. A validação server-side continua soberana.
2. Histórico da compra: indicadores de devoluções totais, documentais e pendentes; estados financeiros mais claros.
3. JS isolado na tela documental, sem dependência de novas rotas ou escrita assíncrona.
4. Testes Node sem dependências externas para o cálculo inteiro em milésimos e contratos Blade.

## Contratos de negócio preservados

- **Operacional (D01A):** controle físico; não reduz automaticamente Contas a Pagar.
- **Documental (D04):** documento externo (o sistema não emite NF-e); `recusado` não baixa estoque; `estoque` representa saída de mercadoria recebida; abatimento pode ficar `pendente` até conciliação.
- NF de entrada preservada em seu valor original; não gerar pagamentos automáticos ou créditos isolados.
- Limites de quantidade, idempotência (`operacao_uuid`), permissões administrativas, operações transacionais e conciliação permanecem responsabilidade do backend já existente.
- A prévia client-side **não** calcula abatimento financeiro nem substitui a verificação no servidor.

## Verificações exigidas antes de PR

- `node --test tests/js/feux-compras-devolucoes-v1.test.mjs tests/js/d04-contrato.test.mjs`
- `node --test tests/js/*.test.mjs` (regressão completa JS; se houver falhas pré-existentes, documentar e bloquear integração)
- `npm run build` se dependências locais instaladas
- Regressão PHP em schema descartável da versão reconciliada, sem alterar banco de produção.
- Exercício manual: devolução documental sem itens, saldo recusado, saldo em estoque, troca de modalidade, erros do servidor, permissões de admin, cancelamento e histórico.

## Modularização dos commits

1. `feat(feux-devolucoes): painel e previa segura de devolucao documental`
2. `test(feux-devolucoes): proteger previa e contratos D04`
3. `docs(feux-devolucoes): registrar regras e handoff para development`

## Próximas etapas

- Próximo lote FEUX: melhorar cadastro/listagem/conferência de Compras, sem alterar regras de estoque ou financeiro na camada visual.
- Após estabilizar D04: iniciar UX de Contas a Pagar e, posteriormente, Contas a Receber e vínculos com Notas.
- Mudanças no backend, se necessárias, devem ser commits `fix(backend-...)` separados com relatório do porquê, testes PHP e revisão pelo agente `development`.
