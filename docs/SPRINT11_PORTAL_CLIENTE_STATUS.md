# Sprint 11 — Portal do Cliente

## Estado da implementação

Base: `development` após o commit 0535bc6.

Este documento diferencia implementação de homologação e evita
confundir esta Sprint 11 com a integração do Fornecedor S.

| Subtarefa | Situação |
|---|---|
| Adaptar layout | Implementado; homologação visual pendente |
| Bloquear menus administrativos | Implementado; regressão de acesso necessária |
| Listagem de produtos | Implementação nova neste bloco; validar |
| Esconder informações internas | Proteção da listagem nova; outras telas pendentes |
| Não mostrar produtos sem estoque | Implementação nova neste bloco; validar |
| Notas vinculadas ao cliente | Implementação de consulta neste bloco; validar |
| Notas vinculadas aos veículos | Filtro por veículo autorizado neste bloco; validar |
| OS | Implementação de consulta e acompanhamento neste bloco; validar |
| Veículos | Listagem segura, detalhes, edição exclusiva e bloqueio de exclusão neste bloco; validar |
| Endereços | Pendente |

## Contrato do catálogo do cliente

- Rota: GET /minha-area/produtos
- Nome: cliente.produtos.index
- Autorização: perfil cliente autenticado e ativo
- Origem: somente tabela produtos (cadastros operacionais)
- Critérios fixos: status ativo e quantidade física maior que zero
- Dados consultados: id, nome, marca_id, preco_uni
- Informações públicas: nome, marca e preço de venda
- Informações privadas: códigos, descrições administrativas,
  quantidade, estoque mínimo, fornecedor e dados do catálogo externo
- Busca: nome ou marca, até 80 caracteres
- Paginação: 12 registros, ordem estável
- Não habilita vendas, compras, reservas ou alterações de estoque

## Consulta de Ordens de Serviço

- Rotas: GET /minha-area/ordens-servico e detalhe por ID.
- Acesso exclusivo do perfil cliente.
- Titularidade determinada por ordem_servicos.cliente_id.
- Veículo mostrado apenas quando vinculado ao cliente atual.
- Filtros de veículo e situação.
- Histórico de OS abertas, finalizadas e canceladas.
- Campos públicos: situação, etapa, setor, veículo, datas e valor.
- Descrição interna e demais dados administrativos não expostos.
- Somente leitura, sem alteração de estoque, financeiro ou etapas.
- Homologação visual e regressão ainda necessárias.

## Próximas etapas

1. Executar os testes de veículos no banco isolado.
2. Executar a regressão das funcionalidades do portal.
3. Homologar visualmente produtos, notas, OS e veículos.
4. Consolidar o commit da atualização de veículos.
5. Implementar a consulta segura de endereços.
6. Executar auditoria final de permissões e dados internos.

Produção, migrations e Forge DB Oficina fora do escopo deste bloco.
