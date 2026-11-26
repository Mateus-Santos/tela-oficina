# FEUX L03M — Navegação Kanban sem captura do scroll e métricas separadas

## Diagnóstico

A L03L usava `railRef` e `boardRef` como superfícies de rolagem sincronizadas bidirecionalmente. Ao movimentar, os eventos `scroll` retroalimentavam a outra barra. O Kanban também tinha `overflow-y:auto`, `max-height` e `overscroll-behavior:contain`, causando captura da rolagem vertical da página em desktop e dispositivos móveis.

## Correção

- Um único scroller horizontal nativo (`boardRef`); controle superior é `input[type=range]` e não outro scroller.
- Botões anterior/próxima etapa movem o único scroller **sem animação suave ou snap**.
- Seletor `Ir para etapa` para acesso direto por teclado, mobile e tecnologias assistivas.
- Sem scroll vertical interno, altura máxima nem overscroll vertical contido; rolagem vertical pertence à página.
- Renderiza inicialmente **dois cartões por etapa** com botão `Ver mais` em grupos de três; preserva o total contabilizado e o `Carregar mais` global.
- Ao mover uma nota para outra etapa, torna o cartão recém-movido visível, sem refetch geral.
- Cartão `Peças e Serviços` apresenta `Peças`, `Serviços`, `Total dos itens` (soma das parcelas comerciais de itens; não equivale necessariamente a faturamento).
- Tokens de cores: `--sos-text-metric-label`, `--sos-text-metric-value`, `--sos-border-metric-divider`.

## Homologar no navegador

1. Desktop: scroll vertical com mouse sobre o quadro deve mover a **página**, não ficar preso.
2. Mobile: gesto vertical sobre o cartão deve rolar a página; gesto horizontal atravessa as etapas.
3. Botões esquerda/direita e seletor de etapa percorrem todas as colunas sem retornar alguns pixels.
4. Slider horizontal superior não treme; testar arraste rápido, teclado e troca de view.
5. `Ver mais` funciona em coluna com 3+ cartões sem ocultar novas Notas e sem desmontar modais.
6. Em `Todas` (com e sem canceladas), métricas discriminam valores e respeitam o filtro.
7. Nenhum registro, migration ou tabela alterado.

**Observação:** esta correção não realiza a L02C e não modifica o PR #143 nem o PR #141.
