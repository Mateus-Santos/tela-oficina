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
