# FEUX L03 — Central de Notas Vue 3

## Escopo

Central `/notas/painel` passa a ser renderizada por Vue 3 (Vite + `@vitejs/plugin-vue`). Blade vira somente o shell de montagem. Vue controla:

- Quadro Kanban e Lista, etapas cadastradas e cores, filtros e indicadores agregados, paginação progressiva;
- Modal de Show, criação, edição, PDF cliente e conta a receber via links, movimentação, cancelamento, exclusão protegida e finalização com múltiplas parcelas / conta preexistente;
- Pesquisa de clientes/veículos/produtos/OS ainda não vinculadas, criação de O.S. em rascunho e gravação transacional pelas Actions existentes;
- Loading por requisição, indicação de erros e confirmação do servidor antes de atualizar o quadro. Sem `location.reload()`.

## Compatibilidade e limites

- O Controller Vue só está exposto nas rotas `auth`, `check.blocked`, `admin` existentes.
- **Não remove** `NotaController`, `NotasItemController`, `CriarNota`, `AtualizarNota`, serviços de estoque/financeiro nem rotas/PDF existentes.
- O Show Vue cobre as operações principais, PDFs e finalização parcelada; parcelas múltiplas, cadastro rápido de cliente/veículo/produto e operações financeiras especializadas continuam acessíveis no Show legado durante a transição. **Não declarar paridade total até migrar/validar essas funções.**
- `CriarOrdemServico::execute` interno ainda pode gerar OS sem Nota: L02C segue pendente.
- `notas/painel/api/dados` carrega até 60 por lote; métricas representam o total filtrado. Contagem por coluna mostra apenas os cards carregados (não inventar contagem global por etapa).
- `etapa_final` não comprova que a Nota pode ser finalizada; a Action oficial valida ao finalizar.
- Kanban drag usa HTML drag-and-drop em desktop; seleção de etapa dá suporte a dispositivos de toque e teclado.
- A API não devolve informações financeiras privadas a usuários cliente porque todas as rotas estão no grupo admin.

## Plano de testes obrigatório

1. `npm run build` e todos os testes estáticos (L02A, L02B, L03) em worktree isolado.
2. Conferir login, acesso admin, permissões negadas para clientes, CSRF e sessão expirada (419).
3. Navegar Quadro/Lista/status/filtros, clicar e arrastar Notas, usar seletor mobile e confirmar historico.
4. Abrir Show Vue, editar produtos, criar Nota com peças, O.S. rascunho, confirmar rollbacks em falha. Sem DB de produção.
5. Confirmar finalização com estoque insuficiente, parcelamento e conta a receber; testar cancelamento / exclusão protegida, confirmar ações sem duplicidade.
6. Conferir PDFs, Show legado, portal do cliente, área financeira e produto.
7. Finalizar migração de recursos avançados antes de remover demais telas Blade / Livewire do módulo.

## Instalação

O script L03 copia o código, instala dependências isoladas em worktree próprio (pode precisar acesso ao npm), testa, builda, commita localmente e só então atualiza o app Docker FEUX. Não executa migrations, não toca no DB original e preserva volumes.

## Componentes reutilizáveis

`resources/js/components/sos/UiModal.vue` e `UiFilterDrawer.vue` estão desacoplados da Nota e podem ser reaproveitados em Compras/Estoque/Clientes.
