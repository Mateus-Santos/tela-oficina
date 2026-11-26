# FEUX Central de Notas — L02B: O.S. e Nota atomicas nos fluxos web

## Invariante operacional

- Cadastro tradicional da O.S.: uma transacao cria O.S., Nota aberta e `notas_itens`.
- Endpoint de criacao rapida legado: uma transacao cria O.S., Nota aberta e `notas_itens`; a resposta indica a Nota vinculada.
- Cadastro rapido no formulario Nota: produz item de O.S. **na memoria do formulario** (`itemable_id` negativo e `os_nova_setor_servico_id`). Salvar Nota grava a O.S. na mesma transacao da Nota; abandonar o formulario nao deixa registro.
- Edicao Nota: novos rascunhos seguem a transacao de AtualizarNota; o item ja vinculado a O.S. nao pode ser retirado isoladamente nem transferido para outra O.S.
- Protege a remocao de itens O.S. pela rota individual existente.

## Limites conscientes deste lote

A acao historica `CriarOrdemServico::execute` ainda e invocada diretamente por testes e por processos internos. A L02B protege **os fluxos web identificados** e os formularios, nao e uma garantia global de integridade no banco. Nao possui FK `ordem_servicos.nota_id` ou indice unico em vinculacao polimorfica. Auditar antes de integrar.

O codigo `ordemservicos.index` continua no sistema ate que a Central de Notas cubra pesquisa, visualizacao, permissao, PDF e fluxos do portal do cliente. Nao excluir rotas nem arquivos usados pelo portal.

## Validacao requerida

1. A auditoria `notas:auditar-vinculos-os` deve continuar em zero orfas no clone antes e apos testes.
2. Testar criar O.S. avulsa e confirmar Nota vinculada e mesmo cliente/veiculo/valor.
3. Testar rascunho no formulario com Cancelar/Voltar (sem gravar O.S.).
4. Testar rascunho com Salvar: Nota + O.S. + item, sem duplicacao.
5. Testar falha na validacao Nota: O.S. nao deve ser persistida.
6. Testar edicao Nota com O.S., tentando remover item e alterar identidade; deve bloquear.
7. Testar usuario sem permissao e CSRF.
8. Testar testes feature existentes de Nota/O.S. em banco isolado, corrigindo expectativas de arquitetura antes de remover legado.

**Nenhuma operacao de banco ocorre no instalador:** somente troca de codigo da previa; testes de escrita sao manuais no banco CLONADO.
