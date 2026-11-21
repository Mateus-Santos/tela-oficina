# Incidente: editar Conta a Pagar com compras vinculadas e aumentar parcelas

**Situação:** relato operacional ainda não diagnosticado; NAO aplicar correção ou migração sem reproduzir e confirmar. A RC H01/S01 NAO modifica intencionalmente dados financeiros para tratar este relato.

## Hipóteses para verificar (não são diagnóstico confirmado)

- A edição de parcelas pode estar recalculando valores ou vínculos de uma conta vinculada a várias compras.
- Validação pode impedir aumento da quantidade de parcelas em contas com pagamentos, pivôs ou vínculos existentes.
- Formulário pode estar enviando `valor_original` ou linhas de parcelas diferentes do esperado; verificar idempotência, arredondamentos e transações.

## Investigação segura da VPS

1. Acessar pelo mecanismo existente do GitHub Actions/SSH autorizado. O erro anterior `Permission denied (publickey)` ocorreu num SSH manual; **não solicitar, copiar nem registrar chaves privadas em logs**. Não usar host novo sem autenticação verificada.
2. Identificar o SHA implantado, hora aproximada, rota/método da requisição, controller, service/Action e status HTTP. Examinar apenas logs de erro redigidos (sem dados pessoais, sessões ou tokens).
3. Capturar IDs técnicos de **uma conta afetada e suas compras** com autorização do operador. Rodar consultas `SELECT` de parcelas, pagamentos, vínculos em pivôs e valores agregados, com usuário SQL somente leitura, transação read-only; não exportar base completa de clientes.
4. Comparar somatório das compras, total da conta, parcelas antes/depois, parcelas pagas e vínculos legados; distinguir erro exibido de alteração efetivamente persistida.
5. Reproduzir com dados **fictícios** no banco descartável H01/S01. Implementar teste de regressão que edite uma conta ligada a múltiplas compras e acrescente parcelas preservando o valor total, ou que rejeite de forma clara operação incompatível.
6. Só depois corrigir Action/Request/tela de forma mínima e atômica; testar pagamentos existentes, arredondamentos, reenvio e autorização. Abrir PR separada; não corrigir a conta real manualmente nem rodar SQL UPDATE sem plano e aprovação.

## Critério de encerramento

Reprodução documentada, causa-raiz confirmada, testes no CI e em banco descartável, revisão financeira da conta afetada e homologação operacional explícita.

## Bloco de diagnostico read-only (PR do incidente)

A action `diagnosticar` do workflow `deploy.yml` executa **somente consultas agregadas em transacao READ ONLY** via SSH previamente configurado no GitHub, sob aprovacao do ambiente `production`. Nao faz deploy, backup ou migration.

- `conta_id=0` exibe somente contagens globais e classificacao de erros do final do log Laravel, sem dados pessoais.
- `conta_id=ID` exibe quantidade de parcelas/compras e diferencas em centavos, sem nomes, NF, CPF ou anexos.
- O runner nao mostra mensagens completas das excecoes nem stack traces, pois podem conter dados pessoais.
- Caso o erro tenha sido validacao de formulario, talvez nao haja entrada nos logs; reproduzir em homologacao.
- Nao foi executada alteracao na conta real; nao confundir diferenca historica com causa raiz confirmada.

O hotfix protege o total de contas com compras vinculadas contra alteracoes por redistribuicao de parcelas e retorna mensagem de validacao, em vez de propagar `InvalidArgumentException` como erro 500. Testes automatizados precisam passar antes de publicar.
