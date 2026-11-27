# FEUX Compras - Conferência D02 / lote UX V3

## Objetivo
Prévia de conferência de compra, estritamente visual. A Action `ConferirCompra` é a fonte de verdade. Branch isolada sobre FEUX V2, sem merge, push ou deploy.

## Regras preservadas
- `quantidade` da NF não é alterada pela prévia. `quantidade_conferida` é recebimento físico (inclusive zero), `quantidade_avariada` reduz o máximo aceitável, e `quantidade_aceita` pode ficar vazia para limite automático `min(NF, recebida - avariada)`.
- Excedente não é aceito automaticamente; nenhum movimento de estoque ocorre nesta prévia.
- Divergência (falta, excesso, avaria, recusa) exige justificativa de pelo menos cinco caracteres; até 2000.
- Campos e payload `itens[ID][...]`, CSRF, endpoint `compras.conferir`, aprovação e formulários nativos são preservados.
- Laravel efetua autorização, validação de lote integral e transação; UX não substitui backend.
- Não altera D01A, D03, D04, financeiro, documentos fiscais, banco, migrations ou contratos da Central de Notas.

## Commits separados
1. `feat(feux-compras): previa e validacao visual da conferencia D02` - Blade, JS, SCSS.
2. `test(feux-compras): contratos e quantidades da conferencia` - teste node.
3. `docs(feux-compras): handoff D02 para development` - este documento.

## Critérios para integrar
- Reexecutar suites JS e testes Laravel do módulo compras em banco descartável.
- Rodar build Vite (não declarar aprovado se `node_modules` ausente).
- Validar navegador com teclado e mobile; conferir entradas 0, falta, excesso, avaria, recusa e itens múltiplos.
- Resolver divergências FEUX V3 / development local antes de cherry-pick dos três commits e não realizar merge indiscriminado.
- Corrigir separadamente os 23 erros L02C de Notas; esse bloqueio não desaparece com esta UX.
