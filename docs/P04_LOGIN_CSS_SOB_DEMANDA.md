# P04.5 — Tailwind preservado; Bootstrap Icons apenas no login

## Evidências e justificativa

O bundle `resources/css/app.css` compilado pelo Vite tinha aproximadamente **51,98 KB (8,93 KB gzip)** antes da etapa. Esse CSS **não é o CSS global da oficina**: é incluído nos layouts `resources/views/layouts/guest.blade.php` e `resources/views/layouts/app.blade.php`, que atendem autenticação e telas Jetstream/Livewire. Seus estilos Tailwind, formulário, reset, tipografia e `[x-cloak]` são necessários e não serão desativados.

A fonte `resources/css/app.css` também fazia `@import` de `bootstrap-icons@1.11.3` em **todas** as telas guest/app. No código da aplicação, o uso de `<i class="bi bi-...">` dentro das telas `auth`, `profile` e `api` só foi identificado na tela de login (`auth/login.blade.php`). Já o layout principal de oficina carrega outra versão da biblioteca, **1.13.1**, que não será modificada.

## Mudança

1. Manter Tailwind e `[x-cloak]` em `resources/css/app.css`.
2. Extrair os seletores `.icons_login` para `resources/css/login.css` (entrada própria do Vite).
3. Remover do app.css a importação remota de Bootstrap Icons.
4. Adicionar uma stack CSS de login, dentro do `<head>` do layout guest e depois da entrada Tailwind.
5. Usar essa stack **somente em `auth/login.blade.php`** para incluir Bootstrap Icons v1.11.3 e `resources/css/login.css`.
6. Manter Bootstrap Icons v1.13.1 no layout principal da oficina, sem misturar versões e sem tocar no registro, banco, Docker ou produção.

## Medições

O instalador executa `npm run build` antes e depois da modificação e relata bytes reais e tamanho gzip para `app.css` e `login.css`. O ganho no CSS compilado será pequeno; o benefício principal é impedir o pedido de folha de estilos e fonte Bootstrap Icons em páginas Jetstream/guest que não necessitam de ícones. O próprio login continuará baixando a biblioteca.

A extensão das chamadas de fonte CSS, a transferência HTTP real e o desempenho precisam ser aferidos na aba Network do navegador, considerando cache e compressão do servidor/CDN.

## Validação e aceitação

- Suite completa `tests/performance` e auditoria `auditar_login_css.py --strict`.
- Build Vite real e manifest contendo a nova entrada `resources/css/login.css`.
- GitHub Actions verdes.
- Homologar visualmente login, Google login, registro, recuperação e redefinição de senha, perfil e Jetstream, especialmente visibilidade de ícones de autenticação, antes de release.
- Não executar deploy, migrations, reiniciar containers, limpar volume ou modificar dados.
