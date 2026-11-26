# FEUX L03N — Finalização real pela etapa final, lote operacional e melhorias visuais

Base imutável: L02C `bf3c3614389e2369e581b272d3f046393c30e6ff`.

## Regras

1. A etapa final configurada em `etapas.tipo=final` não é um simples status visual. Uma Nota aberta arrastada ou selecionada para a etapa final inicia o diálogo de finalização oficial. A mudança de etapa é persistida **somente depois** da confirmação e da execução bem-sucedida de `FinalizarNota` (validações de itens, estoque, O.S., categoria financeira e parcelas quando aplicável).
2. `AlterarEtapaNota` bloqueia etapa final fora do contexto explícito de `FinalizarNota`. O model Nota valida que futuras mudanças de status/etapa de Notas Finalizado/Concluido pelo Eloquent permaneçam na etapa final. SQL bruto e dados legados são cobertos por auditoria, não por evento Eloquent.
3. O comando `php artisan notas:auditar-etapas-finalizadas` é apenas leitura; `--aplicar` regulariza **somente a etapa e seu histórico** das Notas já finalizadas, sem recalcular finanças, estoque ou modificar O.S. Nunca executar `--aplicar` no banco original sem plano separado.
4. Movimentação em lote: apenas Notas abertas, entre etapas operacionais ativas não finais, até 100 por operação, com prévia, assinatura do conjunto, confirmação e transação única com histórico individual. Mudanças concorrentes exigem nova prévia. **Não há finalização em lote** por segurança financeira.
5. O destino de drag and drop ocupa a altura da maior coluna. Um cartão no fim de uma coluna comprida pode ser arrastado para o espaço vazio de outra coluna.
6. Notas finalizadas têm cor semântica verde suave, distinta da cor da etapa; canceladas conservam cinza. Overlay do modal dispensa o blur de tela inteira, potencialmente custoso na composição gráfica. Fluidez real deve ser homologada no navegador.

## Escopo

Isolado no clone e prévia FEUX (`127.0.0.1:18124`). Sem migration, sem deploy, sem alterações `development`, `master` ou PRs #141/#143. Usar usuário de runtime 1337 nos comandos PHP do container.

## Homologação manual

- Arrastar Nota aberta à última etapa: abrir confirmação; com dados insuficientes, não mover nem finalizar; com dados válidos, status `Finalizado` + etapa final + conta/estoque conforme Action.
- Mover Nota entre etapas não finais: operação reativa sem skeleton.
- De uma coluna com muitas Notas, expandir `Ver mais` e arrastar cartão inferior para coluna vazia.
- Mover lote: prévia, confirmação, histórico, contagem; final bloqueada; refazer prévia após mudança concorrente.
- Modal: abrir/rolar no desktop e celular; conferir redução da sensação de travamento.
- Rodar `notas:auditar-etapas-finalizadas` e decidir separadamente se é necessário `--aplicar` no clone.
