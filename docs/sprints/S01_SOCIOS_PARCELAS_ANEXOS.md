# S01 — Parcelas e anexos do Contas a Pagar dos Sócios

**Base:** Laravel `development` SHA `49fdb891e1c81d9fb63bb1d77551a1004ecd1ca9`.

- Cadastro de dívidas permite parcelas iguais (legado preservado) ou valores/vencimentos diferentes, desde que somem o total exato.
- Na tela da dívida, alterar valores e datas de parcelas existentes; nenhuma parcela é removida, nenhum número é trocado. Mantém a soma total, não aceita valor menor que pagamentos ativos nem altera contas pagas/canceladas.
- Cada alteração gera histórico imutável na tabela `parcelas_contas_pagar_socios_ajustes` com usuário, data, antes/depois e motivo.
- Anexos privados do sócio usam os mesmos tipos, 2 MiB e rotas existentes de visualização, download, edição e exclusão; bloqueiam a edição em conta paga/cancelada. Não mexer em saldo empresarial.
- Formulários de Bootstrap e Vite seguem a UI atual; não restaurar o modelo antigo.

## Regras de publicação

1. CI MySQL descartável, build Vite, testes de autorização e anexos aprovados no SHA final.
2. Validar cadastro e edição em telas 320, 390, 768 e 1440 px (admin e cliente).
3. Aplicar migration somente após **backup verificado** e plano de rollback; nunca executar `migrate:fresh` em dados existentes.
4. Revisar H01; PR de desenvolvimento não autoriza publicação automática em produção.
5. Não confundir este módulo **pessoal dos sócios** com Contas a Pagar empresariais.
