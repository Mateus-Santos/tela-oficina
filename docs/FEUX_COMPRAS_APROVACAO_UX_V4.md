# FEUX Compras - Aprovacao / UX V4

## Contexto
Lote incremental sobre V3 (`a2d0353`). A entrega melhora a explicacao visual dos caminhos de aprovacao e da relacao com Contas a Pagar, sem alterar o POST de aprovacao ou as regras transacionais.

## Regras de negocio preservadas
- Compra somente pode ser aprovada apos conferencia de todos os itens. O Laravel e autoridade final para status e consistencia.
- Quantidades de NF, recebida, avariada e aceita sao independentes. O backend D02 controla divergencias e justificativas.
- Compra com divergencia deve ser aprovada sem gerar conta, deixando conciliacao D03 para momento proprio.
- Em compra sem divergencia, o operador pode aprovar sem gerar conta ou solicitar geracao com parcelas. A interface nao executa pagamento ou baixa de parcelas.
- A aprovacao nao equivale a entrada automatica no estoque, emissao de NF-e, conciliacao nem pagamento.
- D01A operacional segue sem abatimento financeiro; D04 documental possui conciliacao segura e preserva a NF original.
- A UI nao altera a geracao de contas, os metodos HTTP, campos hidden, nomes, autorizacao ou regras do banco.

## Composicao de commits
1. `feat(feux-compras): contexto e orientacao na aprovacao D02-D03` - Blade, JS e SCSS.
2. `test(feux-compras): garantir contratos da aprovacao` - testes Node.
3. `docs(feux-compras): handoff da aprovacao V4` - este documento.

## Validações obrigatorias de integracao
- Os testes Node devem passar, incluindo o conjunto completo `tests/js/*.test.mjs`.
- O build Vite precisa ser concluido em ambiente com dependencias. Caso nao exista `node_modules`, o gate de build permanece pendente, nao aprovado.
- Verificar visualmente no navegador os dois caminhos do modal e a divergencia, em mobile e teclado.
- Executar testes PHP do dominio Compras em banco descartavel antes de transportar commits.
- Integrar apenas commits aprovados em branch propria, nunca merge indiscriminado com FEUX Notas/menu; `development` e `master` preservadas.
- A regressao L02CR Nota/O.S. (23 erros PHP do gate anterior) continua bloqueando a integracao geral ate corrigida.
