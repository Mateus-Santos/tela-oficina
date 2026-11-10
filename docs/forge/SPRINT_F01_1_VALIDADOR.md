# F01.1 — Validação offline de pacote Forge

Escopo: somente arquivos novos na branch `feature/integracao-forge`.

- `app/Services/Forge/ValidarPacoteForge.php`: valida o formato do manifesto, inventário permitido, tamanho, SHA-256, cabeçalho e forma dos CSVs, produtos referenciados em aplicações, identidade composta dos veículos e avisos de similares ignoráveis. Não grava dados.
- `app/Console/Commands/ValidarPacoteForge.php`: comando Artisan `forge:validar-pacote {pacote}`, que aceita apenas o nome de um diretório `pronto-*` dentro de `storage/app/forge-v270/`.
- `tests/Unit/Forge/ValidarPacoteForgeTest.php`: testes unitários sem banco para pacote íntegro, adulteração, traversal, órfão e symlink.

Atenção: o pacote analisado no Forge permanece em `~/projetos/forge-db-oficina/data/transferencias/laravel/` e **não deve ser movido nem importado** durante esta etapa. Antes do comando Artisan, será necessário configurar vendor e staging privado da branch Forge. Para o diagnóstico intermediário, é possível executar o serviço com PHP CLI em um contêiner efêmero, com montagens de leitura.

Limites: não há registro persistente de execução, reserva imutável dos CSVs, autenticação criptográfica da origem, fila, controle de concorrência ou rollback automático. Esses componentes ficam para as próximas entregas. O validador não confirma compatibilidade mecânica de peças; somente a coerência estrutural.
