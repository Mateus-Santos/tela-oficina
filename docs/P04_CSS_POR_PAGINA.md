# P04.3 — Separar estilos de chat e listagens de produtos

## Evidencia

O arquivo `resources/scss/_app.scss` incluia todos os estilos de `_chat.scss` (1.420 bytes de fonte) e `_listarproduto.scss` (7.855 bytes de fonte), mesmo em paginas que nao utilizam esses componentes. Esses arquivos incluem seletores que so aparecem nas views `chat.blade.php`, `produto/listarproduto.blade.php` e `produto/listarproduto_unificado.blade.php`, conforme auditoria das views.

## Mudanca

- Retirar apenas os dois imports do SCSS global.
- Criar `resources/scss/chat_entry.scss` e `resources/scss/produto_lista_entry.scss` com seus imports existentes, sem alterar as regras CSS.
- Registrar as duas entradas no `vite.config.js`.
- Adicionar `@stack('p04-css-pagina')` no `<head>` do layout compartilhado, apos o CSS global.
- Incluir `@push('p04-css-pagina')` nas tres views especificas usando `@vite` com os novos caminhos.
- Preservar `_filtros.scss`, `_style_cad.scss`, demais estilos globais, JS, PHP e dados.

## Objetivo e medicao

Diminuir o CSS baixado e analisado em telas que nao sao chat/listagem de produtos. A fonte de 9.275 bytes nao equivale a economia exata de rede: Sass, compressao e duplicacao mudam o resultado. O baseline do ultimo build conhecido do CSS global foi aproximadamente 42,95 kB bruto, devendo ser comparado com o novo output gerado pelo Vite em GitHub Actions e em homologacao.

O chat e as listagens passarao a carregar um CSS adicional especifico. Comparar tamanho total e a cascata de estilos (ordem do CSS global antes do especifico) nessas telas.

## Gate de qualidade

1. Passar auditoria `python3 scripts/performance/auditar_css_por_pagina.py --strict` e suite de testes `tests/performance`.
2. Passar `npm ci && npm run build` no GitHub Actions; verificar entradas CSS e outputs no manifest Vite.
3. Em homologacao revisar visualmente home, chat, listagens de produtos, modal de similares, formulario de produtos e responsividade mobile/desktop.
4. Nao promover a producao antes de homologar a release acumulada e executar regressao PHP.

**Nenhuma acao sobre producao, bancos, volumes, PHP-FPM ou outros projetos Docker.**
