# P04.4 — CSS exclusivo da home e paginas informativas

## Premissa

O P04.3 moveu chat e cartões de produto para CSS sob demanda. O `_index.scss`
ainda mistura a home com menu, rodapé, formulários legados, páginas de erros,
e termos. Esta etapa mantém os estilos compartilhados e isola **somente**
seletores com uso restrito confirmado na base de views.

## Mudanças

- `.section-bg`, `.about`, `.team`, `.member`, `.redes`, `#hero` e
  `#footer .footer-top` migram para `_home.scss`, usado por `home_entry.scss`
  exclusivamente em `resources/views/index.blade.php`.
- Breakpoints de `#hero` (991, 768, 575px) migram junto com a home, **após**
  as regras base de `#hero`, evitando que a regra padrão sobrescreva os mobile.
- Regra `.box-questions` em 300px deixa `_responsividade.scss` e acompanha
  `_chat.scss`, já exclusivo do chat desde P04.3.
- `.erro403`, `.erro404`, `.termos-uso` saem de `_index.scss` e passam a
  `informativo_entry.scss`, carregado apenas nas views correspondentes.
- `section`, `.section-title`, o rodapé comum, navbar, menu mobile, formulários,
  máscaras, filtros, modais, e outros estilos continuam globais e intactos.
- Nenhum arquivo de imagem, JS, CSS de terceiros ou dependência é removido.

## Verificações

1. Auditoria de escopo dos seletores na cópia local do projeto.
2. 27 testes de performance anteriores + sete testes P04.4, sem substituir expectativas.
3. `npm run build` local, quando dependências já instaladas, e obrigatório no CI.
4. Validar em homologação: home desktop/mobile, menus, links âncora, rodapé,
   termos, erros 403/404, chat até 300px, cadastro e notas/financeiro.
5. Medir CSS global antes/depois, total de bytes por página e número de requests.

## Segurança operacional

Sem migrations, sem banco, sem deploy, sem restart e sem alterações na VPS.
Não rodar `docker compose down -v`. Não unir ao `master` até validar a release.
