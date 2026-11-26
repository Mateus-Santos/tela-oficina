# FEUX · Central de Notas e O.S. — plano de substituição segura

## L01 (entregue neste pacote)

- Rota experimental `/notas/painel` sob `auth + check.blocked + admin`.
- Kanban por `etapas` ativas aplicáveis a Notas; lista alternativa; filtros por status, etapa, cliente, placa e tipo de item.
- Indicadores calculados sobre TODAS as notas filtradas, não apenas os primeiros 200 cartões. Limite 200 é informado no UI.
- Mudança de etapa via `AlterarEtapaNota`, com autorização, histórico, transação e erros de validação; finalização NÃO ocorre ao arrastar.
- `dialog` nativo com carregamento sob demanda e links para edição e Show completo; exclusão apenas se aberta sem O.S. nem financeiro, mantendo backend oficial.
- Loadings baseados em requisições reais; seleção para toque/teclado alternativa ao drag/drop nativo de desktop.
- Componentes reutilizáveis `ui/metric-card`, `ui/filter-drawer`; CSS restrito ao workspace.
- Auditoria `php artisan notas:auditar-vinculos-os` somente leitura.
- Guardas adicionais impedem excluir Nota ligada a O.S./financeiro e excluir O.S. já ligada a Nota.
- Nenhuma tabela ou dado migrado. Rotas e listagem legadas preservadas como fallback temporário.

## L02 — contrato obrigatório O.S. ↔ Nota (PENDENTE)

**Não remover a listagem de O.S. ainda.** Hoje `CriarOrdemServico` e o endpoint de O.S. rápida podem criar O.S. sem Nota; o vínculo vigente é `notas_itens` polimórfico. Esta é uma regra de domínio, não uma troca de Blade.

1. No clone de homologação, auditar O.S. órfãs, duplicadas, divergentes, fechadas/canceladas e referenciadas em outros módulos.
2. Escolher representação canônica: recomenda-se `ordem_servicos.nota_id` com FK; durante migração a coluna é nullable, e `notas_itens` continua como linha comercial. Não manter duas fontes sem serviço transacional de sincronização.
3. Preparar backfill **auditável e reversível** dos vínculos existentes. O.S. órfãs: criar ou associar Nota apenas após regra explícita para cliente, veículo, item, valor e histórico. Não finalizar ou movimentar estoque automaticamente.
4. Alterar todos os caminhos de criação (formulário de Nota, O.S. rápida, controller O.S., integrações) para criar/associar O.S. e Nota na mesma transação. Em formulário novo, a Nota precisa existir antes de persistir a O.S., ou os dados da O.S. devem ficar em rascunho até salvar a Nota.
5. Testar exclusão/cancelamento da Nota e O.S.; bloqueios de estoque, financeiro, histórico, geração de PDF e portal cliente. Proibir órfãs com constraints depois de `COUNT(órfãs) = 0` e todos os produtores atualizados.

## L03 — experiência unificada e aposentadoria do legado (PENDENTE)

- Migrar Show completo para `dialog` sem iframe e com editor de itens, O.S., estoque, cliente, pagamento, edição, finalização e cancelamento protegidos.
- Preparar indicadores de **prontidão para finalizar** com serviço de validação read-only (não confundir com etapa operacional final).
- Pagination/carga incremental por coluna em vez de limite 200; otimizar consultas e testes N+1.
- Substituir o menu admin de O.S. por filtros de Notas; implementar redirecionamento compatível de `ordemservicos.index` para Notas filtradas somente após `COUNT(órfãs)=0` e regressões.
- Retirar CSS/Blade/JS órfãos **somente após** checagem de referências em rotas, menus, testes, portal cliente e integrações. Inventário de uso com `rg` antes de remover.

## Critérios de aceite

- Uma O.S. possui exatamente uma Nota; nenhuma rota ou API cria O.S. órfã.
- Alterações na Nota não geram duplicidade financeira nem baixa indevida de estoque.
- Kanban, Lista e Show referem-se aos mesmos filtros e regras.
- A interface funciona em desktop, toque, teclado, conexões lentas e erros HTTP.
- Banco de origem nunca é escrito nos testes: somente clone isolado da prévia.
