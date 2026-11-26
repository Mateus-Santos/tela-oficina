# FEUX L03L — Compactação e cancelamentos

Origem: `6d3a4101fdfb745cd0916d0536bbfe8751e57271` (L03K).

## Regras funcionais

- `status=Aberto`: exclusivamente abertas.
- `status=Finalizado`: finalizadas e legadas `Concluido`.
- `status=Cancelado`: exclusivamente canceladas **mesmo com o checkbox desmarcado**.
- `status=Todos` + `incluir_canceladas=0` (ou ausente): abertas e finalizadas, canceladas ocultas.
- `status=Todos` + `incluir_canceladas=1`: inclui também canceladas; filtro no servidor ANTES de cálculos e paginação.
- Cards cancelados: superfície cinza-escura discreta, textos legíveis e badge de cancelamento. Etapas mantêm suas cores configuradas, não são repintadas. Cards cancelados não possuem drag-and-drop nem seleção de etapa.
- Nenhum dado é excluído ou alterado ao ocultar canceladas. Sem impacto em Contas a Receber; o checkbox é exclusivamente para Notas da Central.

## UI

- Compacta cabeçalho, filtros, métricas, margens e remove paddings globais injetados em `section`.
- Kanban tem altura máxima dependente da viewport, rolagem vertical própria e barra horizontal acessível no TOPO; setas também permitem navegar por etapas.
- Preserva scroll e cartões reativos da L03K, sem refetch ao mudar de etapa; filtros continuam consultando API assíncrona.
- Tokens de cancelamento usam papéis semânticos com `--sos-bg-*`, `--sos-border-*` e `--sos-text-*`, nunca hex aleatório nos componentes.

## Limites e homologação

Build e testes de contrato não substituem inspeção visual desktop/mobile, Chrome/Firefox, arrastar/drop autenticado e testes com dados reais.
Não tocar em master, development, PR #141, banco original, migrations ou outros containers.
