# FEUX L02C — contrato O.S. ↔ Nota

## Invariantes

1. `ordem_servicos.nota_id` é a FK canônica, obrigatória (`NOT NULL`) e com `ON DELETE RESTRICT`.
2. `notas_itens` continua o lançamento comercial; `feux_os_unica` (gerada, index único) impede o mesmo serviço em duas Notas. Produtos continuam reutilizáveis.
3. `CriarOrdemServico::execute()` cria Nota e O.S. em uma transação. `criarParaNota()` é operação interna: só recebe Nota aberta existente, dentro de uma transação.
4. `CriarOsComNota` cria Nota transitória vazia, depois O.S. e item; uma falha em qualquer etapa reverte tudo.
5. `OsRascunhoNaNota` grava sua O.S. somente na transação da Nota, com FK da Nota correta.
6. A migration exige 0 órfãs, 0 múltiplas Notas e 0 divergências antes de adicionar a FK. NÃO regulariza automaticamente registros inesperados.
7. O código antigo e a API Vue permanecem protegidos pelos guards já existentes; exclusões e cancelamentos não devem desvincular a O.S.

## Limites e homologação

- O banco de dados só impõe a FK e unicidade do item O.S.; a existência de `notas_itens` para toda O.S. é uma invariável transacional da aplicação, não uma `FOREIGN KEY` reversa.
- A migration utiliza DDL MySQL, que não participa de rollback transacional. Na FEUX clona-se o banco previamente; verifique o snapshot existente.
- Depois da publicação, o smoke deve provar que uma O.S. criada com `execute()` já nasce com Nota e item, que uma O.S. rápida possui a mesma `nota_id`, e que não há mudança nas contagens após rollback.
- Testes históricos que criam O.S. avulsa para vinculá-la posteriormente a uma segunda Nota precisam ser adaptados para o novo contrato antes de integrar à development.
- Não usar a migration no banco original nem no ambiente de produção.

## Reversão

A migration possui `down()` para retirar os índices, a coluna e a FK no clone. Uma reversão de schema deve ocorrer ANTES de restaurar código anterior. Não execute `migrate:rollback` globalmente; indique explicitamente o arquivo.
