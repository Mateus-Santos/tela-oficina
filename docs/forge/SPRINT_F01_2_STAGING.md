# Sprint F01.2 — Recepcao segura de pacote Forge

Escopo: somente novos arquivos em `feature/integracao-forge`, sem migracoes, sem importacao, sem acesso MySQL.

## Arquivos

- `app/Services/Forge/PrepararStagingForge.php`: valida o pacote de origem com o validador F01.1, recebe em diretorio privado `.recebendo-*`, copia os CSVs em streaming, revalida a copia, protege contra escrita acidental e publica com `rename` no mesmo filesystem.
- `app/Console/Commands/PrepararStagingForge.php`: comando Artisan futuro `forge:preparar-staging {pacote} --origem=...`. Precisa de dependencias Composer da **mesma** branch, portanto nao executar contra o container Laravel compartilhado.
- `tests/Unit/Forge/PrepararStagingForgeTest.php`: cinco testes unitarios offline, sem MySQL, para publicacao, reexecucao bloqueada, adulteracao, symlink e permissoes de destino.

## Fluxo aprovado para ensaio WSL

1. Verificar branch, HEAD e arquivos da F01.1.
2. Instalar novos arquivos sem sobrescrever F01.1.
3. Executar todos os testes unitarios F01.1 + F01.2 em container efemero PHP, somente leitura, rede desativada.
4. Executar o **servico puro** F01.2 em container efemero com a origem Forge montada em leitura e apenas `storage/app` montado para escrita.
5. Conferir staging recebido e revalidar imediatamente antes de eventual importacao futura.

## Garantias e limites

- O pacote ja publicado nao e sobrescrito pelo comando.
- Concorrrencia entre processos que usam este preparador e controlada por `flock` na pasta privada.
- A publicacao do diretório definitivo usa `rename` no mesmo filesystem (troca atomica do nome).
- Fonte, banco e produtos operacionais nao sao modificados.
- A copia e protegida por permissoes 0400 (arquivos) / 0500 (pacote) / 0700 (raiz), mas o proprietario Unix ou root pode modifica-las. Nao se trata de imutabilidade absoluta.
- O manifesto Forge V1 nao e assinado; o preparador nao autentica criptograficamente o emissor.
- Os CSVs extras sao preservados, mas nao consumidos pelo importador existente.
- Esta fase NAO habilita importacao nem garante seguranca contra alterações feitas apos o staging. Verificar hashes novamente imediatamente antes do consumo, sob controle de concorrencia.
- A trilha `development` e seu MySQL nunca devem ser usados para executar estes testes.

## Pendencias para fases seguintes

- Ampliar verificacao de referencias cruzadas para todos os conjuntos.
- Registrar recebimentos e execucoes por lote com trilha de auditoria persistente.
- Provisionar MySQL especifico da branch e testar migration de identidade em copia isolada.
- Revalidar e travar snapshot durante importacao, reconciliar resultados e comparar baseline antes/depois.
