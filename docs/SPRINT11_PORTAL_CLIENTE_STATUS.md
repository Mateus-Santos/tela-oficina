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
| Notas vinculadas ao cliente | Pendente |
| Notas vinculadas aos veículos | Pendente |
| OS | Pendente |
| Veículos | Parcial; revisão de autorização pendente |
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

## Próximas etapas

1. Validar os novos testes no banco isolado.
2. Executar regressão de rotas, permissões, catálogo e estoque.
3. Homologar visualmente no navegador.
4. Registrar commit controlado.
5. Avançar para notas do próprio cliente, com testes de isolamento
   entre clientes e associação aos veículos.

Produção, migrations e Forge DB Oficina fora do escopo deste bloco.
