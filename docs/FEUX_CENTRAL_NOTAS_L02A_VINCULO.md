# FEUX L02A — Regularização controlada da O.S. órfã

## Evidência obtida no clone da development em 24/11/2026

- 47 O.S. cadastradas.
- 1 O.S. sem Nota: `#3`, `aberta`, cliente `#6`, veículo `#6`, valor R$ 500,00, sem fechamento.
- Nota `#59`: mesmo cliente e veículo, porém **finalizada** com total R$ 2.260,00.
- Nenhum vínculo duplicado ou divergência cliente/veículo detectada.

## Decisão desta entrega

A O.S. #3 **não** será associada à Nota finalizada #59 apenas por cliente/veículo.
A Action existente `CriarNota` cria uma **nova** Nota aberta com exatamente um item de O.S.,
subtotal R$ 500,00, desconto zero e total R$ 500,00, respeitando o vínculo
cliente/veículo e criando histórico inicial de etapa.

O comando `notas:regularizar-os-orfa` permite somente `--os=3 --cliente=6 --veiculo=6 --valor=500.00`
e só executa em banco cujo nome corresponda ao padrão do **clone FEUX** `feux_dados_dev_YYYYMMDD_HHMMSS_PID`.
O padrão é dry-run. Escrita exige `--executar --confirmar=3`.

### O que esta entrega NÃO faz

- Não altera Nota #59, financeiro, estoque ou a O.S. original.
- Não migra o MySQL principal, não escreve no banco development e não apaga nada.
- **Ainda não torna universal a relação obrigatória para novas O.S.** `CriarOrdemServico` e o endpoint de O.S. rápida ainda podem criar O.S. avulsa na versão atual; o contrato transacional completo é L02B.
- Não remove a lista legada de O.S. enquanto produtores, portal cliente, APIs e testes não forem migrados.

## Próximas entregas

L02B: criação vinculada atômica e formulário com O.S. em rascunho antes do salvamento; endpoint rápido só persiste com Nota existente, controle de duplicidade e testes de corrida. L02C: backfill auditável, chave canônica `ordem_servicos.nota_id` (FK), verificação de integridade, bloqueio de órfãs e redirecionamento compatível das rotas antigas após regressão. L03: modal de Show completo e remoção de CSS/JS/Blade comprovadamente sem uso.
