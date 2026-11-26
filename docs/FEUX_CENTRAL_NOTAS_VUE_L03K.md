# FEUX L03K — Movimento local de cartões e cores semânticas

Origem: FEUX L03J `a888bb6e0376b2d8247c13485f1e4155c10810f4`.

## O que muda

- `moveNote`: PATCH apenas da Nota movida. Confirmação no servidor antes da alteração reativa. Em falha, não movimenta cartão.
- Reconciliação de `etapa_id`, nome da etapa e indicador da etapa final diretamente em memória. Sem `loadBoard(true)` padrão, sem skeleton, sem fechar modal e sem reinicializar todas as colunas. Se o modal da própria Nota estiver aberto, seu campo de etapa também muda reativamente.
- Quando houver filtro por etapa ou filtro alterado durante o PATCH, os dados são revalidados silenciosamente para conferir métricas e paginação; sem desmontar a tela com o skeleton.
- O feedback de sucesso da movimentação é flutuante, sem deslocar a barra de pesquisa nem o quadro.
- CSS: tokens semânticos `--sos-text-*` para papéis de texto (principal, título, cartão, etapa, secundário, link, status, etc.), isolados na Central Vue. O texto amarelo herdado do tema legado não é aplicado aos títulos das etapas nem ao nome do cliente.
- `NotaCard`: identificação `data-note-id` permite recuperar foco da seleção da etapa após a mudança de coluna.

## Limites

- A requisição PATCH da etapa é indispensável para validação e histórico; elimina-se apenas o refetch global em movimentos comuns.
- A lista do servidor continua sendo recarregada em mudanças de filtro, inclusões, exclusões e finalizações; a migração não promete zero requisições.
- Testes Node da função pura e contratos de fonte NÃO substituem a homologação visual em navegador e com sessão autenticada.
- Banco MySQL original, master, development e PR #141 não devem ser alterados.
