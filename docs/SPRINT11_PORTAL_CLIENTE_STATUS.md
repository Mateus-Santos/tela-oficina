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
| Endereços | Consulta, cadastro e edição exclusiva implementados neste bloco; validar |

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

## Endereços do cliente

- Titularidade: endereço.pessoa_id = usuário.pessoa_id.
- Listagem paginada e formulário próprio de cadastro/edição.
- O cliente não escolhe nem altera o proprietário do endereço.
- Campos validados conforme os limites reais da tabela.
- Consulta e edição de terceiros retornam 404.
- Rotas administrativas mantidas.
- Exclusão pelo cliente não habilitada.

## Dívida técnica registrada

- Revisar listagem administrativa legada de endereços:
  utiliza id_endereco, enquanto o banco usa id.
- Revisar formulário e validação administrativa legados.
- Não modificar essas rotinas neste bloco.

## Próximas etapas

1. Validar os testes de endereços no banco isolado.
2. Executar regressão do portal e módulos existentes.
3. Consolidar o commit de endereços.
4. Homologar visualmente todas as áreas do cliente.
5. Auditar permissões e exposição de informações internas.
6. Registrar pendências antes de encerrar a Sprint 11.

Produção, migrations e Forge DB Oficina fora do escopo deste bloco.
