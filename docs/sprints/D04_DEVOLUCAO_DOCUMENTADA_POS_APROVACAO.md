# D04 — Devolução documentada após aprovação da Compra

## Contrato

- A compra e sua NF original são históricas. `compras.valor_total` não é reescrito.
- Devolução possui documento externo (referência, data, eventual chave NF-e, motivo), usuário, UUID e linhas com quantidade, tipo e valor de abatimento.
- `recusado`: item não aceito na conferência; nenhum lançamento de estoque.
- `estoque`: item previamente aceito e lançado no estoque; baixa com origem rastreável na linha de devolução.
- Sem duplicar a baixa: saldo do item confrontado com entradas e devoluções anteriores, sob lock da compra.
- Valor abatido proporcional ao valor líquido original do item, com arredondamento cumulativo em centavos.
- Compra aprovada permanece aprovada.
- D01A antiga permanece `modalidade=operacional`, `situacao_financeira=nao_aplicavel`: histórico e estoque preservados, **sem abatimento automático** do valor devido. Pode ser vinculada a conta pelo valor original se não houver outros bloqueios.
- D04 nova é cadastrada dentro da Compra e mantém `compra_id`; apenas documentos `modalidade=documental` e `situacao_financeira=aplicada` integram o valor financeiro líquido.
- D04 respeita as quantidades já devolvidas pela D01A, sem repetir baixa de estoque. Não converte documentos antigos em novos automaticamente.
- Documento D04 com `situacao_financeira=pendente` bloqueia novas vinculações; abatimentos D03B anteriores exigem revisão antes de nova vinculação para evitar sobrescrita.
- Divergência de excesso (recebido maior que a NF) não é resolvida simplesmente por `aceita == quantidade da NF`: continua sujeita a regularização D03.
- Se a compra foi DESVINCULADA anteriormente, nenhuma conta antiga e inferida ou alterada: o operador ajusta/revincula explicitamente a conta desejada.
- Descontos globais e discrepancias que fariam abatimento superar o saldo liquido geram pendencia, nunca saldo negativo.
- Conta vinculada aberta, sem nenhum histórico de pagamento, com pivôs/parcelas consistentes: ajustar total, pivô e parcelas numa transação.
- Contas pagas, parciais, canceladas ou com parcelas sem saldo: devolver mercadoria/documentar, mas abatimento financeiro fica PENDENTE de conciliação. Registros D01A antigos são apenas operacionais e não geram abatimento automático.
- Contas agrupadas do mesmo fornecedor e com parcelas múltiplas: redistribuição proporcional em centavos, preservando datas e números e impedindo parcelas zeradas.
- `VincularComprasContaPagar` e `GerarContaPagarCompra` usam valor líquido calculado e exigem que TODAS as pendências estejam resolvidas.
- Divergência por item só é considerada resolvida se o quantitativo não aceito foi formalmente recusado e financeiramente aplicado.
- D03 legado continua disponível para seus registros anteriores; duplicidade de abatimento D03/D04 é bloqueada.
- Não gera NF-e fiscal, nota de crédito, reembolso nem crédito genérico com fornecedor.
- Sem migrations em banco real, sem push, sem merge, sem deploy neste patch.

## Homologação obrigatória antes de merge

- MySQL 8.4 isolado com migrations e banco descartável.
- Uma NF com dois itens, óleo aceito e bomba recusada: apenas óleo deve entrar em estoque; bomba gera abatimento sem saída.
- Compra aprovada com estoque já lançado: devolução parcial baixa apenas quantidade disponível.
- Conta com duas compras e duas parcelas, ainda sem pagamentos: total e pivôs ficam iguais à soma líquida das compras; soma das parcelas igual à conta.
- Conta com pagamentos: documento e movimento físico sem alterar valores financeiros, com aviso pendente.
- UUID repetido (sem dupla baixa) e UUID repetido com dados diferentes (bloquear).
- Produto sem estoque suficiente: transação inteira revertida, incluindo documento e alterações da conta.
- Chave NF-e duplicada, login cliente, compra não aprovada, item de outra compra, estorno anterior: bloquear corretamente.
- Validar cálculos com desconto por item, fracionados, frete e descontos globais. O abatimento deste módulo cobre itens, não rateia encargos da compra.

## Decisões pendentes

- Reembolso e crédito do fornecedor continuam fora do escopo D04.
- Devolução totalmente independente de uma compra terá fluxo próprio e reconciliação de fornecedor; não é segura como desconto automático sem origem identificada.
