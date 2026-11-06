# Atualizacao de home e menu — SOS Mecanica

## Escopo

- `resources/views/index.blade.php`: hero, seis cards de servicos, sobre/processo, minimapa e contato, sem secao de equipe vazia.
- `resources/scss/_home.scss`: substitui cerca de 369 linhas de CSS legado por estilos de home encapsulados em `.sos-home`, responsivos.
- `resources/views/layouts/layout.blade.php`: substitui o cabecalho repetitivo por um menu orientado a dados, preservando todos os links reais dos perfis administrador/cliente e as rotas de perfil/logout. Remove apenas links `href="#"` ficticios (Historico, Fluxo de Caixa e Relatorios) dos menus.
- `resources/scss/_menu.scss`: melhora rolagem/responsividade de dropdowns sem remover classes compartilhadas.
- `config/oficina.php`: centraliza dados publicos da oficina e permite configuracao por variaveis de ambiente.
- Rodape compartilhado: contato da oficina e credito do desenvolvedor apenas em `details` recolhido, sem contatos e redes pessoais.
- `tests/Feature/HomePublicaTest.php`: verifica carregamento da pagina, servicos, mapa e contato.

## Contatos a confirmar

O numero publico identificado da oficina e +55 (75) 98108-6649. O link antigo da pagina inicial (`557581086649`) parecia estar sem o digito 9. O endereco da pagina antiga foi mantido, sem CEP (cadastros publicos divergem no CEP).

O e-mail da oficina e um segundo telefone **nao foram comprovados**. O template nao publica dados inventados: configure no `.env` local/servidor somente apos confirmar.

```dotenv
OFICINA_EMAIL=contato-confirmado@exemplo.com
OFICINA_TELEFONE_2=+55 75 00000-0000
```

Os valores acima sao **exemplos**, nao contatos verdadeiros. Apague-os ou substitua pelos dados confirmados. Para o numero principal, os defaults em `config/oficina.php` ja estao preenchidos.

Se o endereco publicado nao corresponder a entrada correta no Google Maps, ajuste `OFICINA_ENDERECO=` na configuracao de ambiente e execute `php artisan config:cache` no ambiente apropriado. O iframe usa Google Maps com `loading="lazy"` sem chave API.

## Limites de seguranca e qualidade

- Nenhuma migration, banco, Docker ou workflow e alterado.
- O comando nao cria branch e exige `development` com arquivos rastreados limpos.
- O script nao executa `git add`, `git commit`, `git push` nem deploy.
- O layout compartilhado conserva os scripts existentes (Bootstrap, jQuery, modais, AOS e Waypoints); retira-los exigiria auditoria em outras telas.
- A home remove a frase sobre garantia fixa de 30 dias (verificar politica/lei antes de anunciar esse prazo).

## Validacao depois da aplicacao

1. `git diff --check` e `git diff --stat`.
2. `php artisan view:cache` para validar compilacao das views.
3. `npm run build` para compilar CSS/JS.
4. `php artisan test tests/Feature/HomePublicaTest.php` sob ambiente `testing` isolado.
5. Acessar `/` no celular e no desktop e conferir Google Maps/WhatsApp, login/registro, admin/cliente e os dropdowns.
6. Preencher **dados reais** no `.env` antes de publicar e testar contato/rota.

## Limpeza das branches locais

Execute o script `limpeza_git.sh` no modo leitura para identificar branches locais ancestrais de `origin/development` ou `origin/master`. Exclua apenas aquelas demonstradamente integradas usando `--aplicar`. Nao ha exclusao de branch remota ou worktree; `compose.desktop.yml` permanece intacto.
