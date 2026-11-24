# Sprint U03-U07 — Entrega integrada em worktree isolada

## Concluido anteriormente, nao reimplementado

- Busca e filtros de Compras em Contas a Pagar (PR #132).
- Preservacao de old() e erros de validacao de Compras (PR #134).

## Neste patch

- Ajustes locais de resultados e container de Compras.
- Busca exata de Nota por ID (OS ja tinha filtro), inclusive encerradas ao buscar somente por ID.
- Edicao segura de OS aberta e sem vinculos com Nota: apenas descricao, setor e valor.
- Duplicacao de Nota por confirmacao POST; OS usadas sao recriadas com nova identidade dentro da mesma transacao, sem reutilizar links antigos.
- Duplicacao de Compra pendente com nova NF informada e chave NF-e vazia, sem vinculos, estoque ou anexos.
- UI compartilhada para seletores Notas e OS sem alterar regras Livewire.
- Gestao privada de anexos em Notas e OS usando Actions existentes; Compras mantida.
- Chave NF-e de compras canceladas arquivada em nova coluna; UNIQUE existente de `chave_nf` preservado.

## Avisos e limites

- A migration NF-e e obrigatoria antes do novo codigo de cancelamento ou estorno entrar em producao. **Nao executar na VPS por este patch.**
- A migration move chaves de registros cancelados para `chave_nf_cancelada`, preservando historico no mesmo registro.
- Rollback de migration e bloqueado se uma chave ja tiver sido reutilizada.
- Duplicacao de Notas com OS cria novas OS dentro da transacao; OS legadas sem cliente/veiculo sao bloqueadas para evitar vinculos inconsistentes.
- Este patch nao faz push, PR, merge, migration, deploy nem alteracao em banco real.
- Homologar com MySQL 8.4 descartavel: duplicacoes, OS vinculadas e canceladas, anexos e chaves NF-e.
- Executar testes Laravel e Chromium autenticado antes de integrar.
