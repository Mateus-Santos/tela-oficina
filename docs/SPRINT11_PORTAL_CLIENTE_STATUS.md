# Sprint 11 — Portal do Cliente

## Estado da implementação

Base técnica homologada: `development` no commit `4d386e1`, em 09/11/2026.

Este documento diferencia implementação de homologação e evita
confundir esta Sprint 11 com a integração do Fornecedor S.

| Subtarefa | Situação |
|---|---|
| Adaptar layout | Homologado no ambiente local |
| Bloquear menus administrativos | Testado e homologado localmente |
| Listagem de produtos | Testada e homologada localmente |
| Esconder informações internas | Proteções do portal testadas; auditoria ampliada fora do escopo |
| Não mostrar produtos sem estoque | Testado e homologado localmente |
| Notas vinculadas ao cliente | Testadas e homologadas localmente |
| Notas vinculadas aos veículos | Filtros testados e homologados localmente |
| OS | Consulta e acompanhamento homologados localmente |
| Veículos | Cadastro, consulta, edição e restrições testados |
| Endereços | Cadastro, consulta e edição homologados localmente |

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
- Homologação visual e regressão de escopo concluídas localmente.

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

## Homologação final — 09/11/2026

- BLOCO 126A: 385 testes aprovados, 1.411 assertions.
- BLOCO 128: 8 de 8 verificações manuais aprovadas.
- BLOCO 129: 385 testes aprovados, 1.413 assertions.
- BLOCO 129: testes de cadastro habilitado e desabilitado aprovados.
- Oito testes ignorados na regressão integral:
  três de API, três de verificação de e-mail e dois de
  cadastro desabilitado.
- Banco de testes: testing_s11_catalogo_20261106.
- Cadastro público desabilitado por padrão no Fortify.
- Ativação local controlada pelo arquivo .env.
- Link de cadastro condicionado à existência da rota.
- Homologação realizada no ambiente local, sem deploy.

## Decisão técnica

Sprint 11 homologada funcionalmente em ambiente local.
O encerramento técnico depende da publicação deste registro
e da correção final do link de cadastro.

Esta homologação não substitui auditoria de segurança ampliada,
validação de infraestrutura nem homologação em produção.

## Pendências fora do encerramento local

1. Corrigir inconsistências legadas do módulo administrativo
   de endereços em atividade separada.
2. Preparar a homologação de implantação antes de qualquer deploy.
3. Definir explicitamente a política de cadastro público em produção.
4. Manter a integração do Forge DB Oficina fora deste escopo.

## Próxima etapa

Consolidar o encerramento técnico na branch development.
Não executar deploy em produção automaticamente.

Produção, migrations e Forge DB Oficina fora do escopo deste bloco.
