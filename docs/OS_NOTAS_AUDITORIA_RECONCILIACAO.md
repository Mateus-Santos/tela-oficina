# OS vinculadas às Notas — auditoria e reconciliação segura

Esta etapa **não** muda a finalização normal de Notas: o fluxo existente já finaliza OS abertas vinculadas quando uma Nota é finalizada.

## Auditoria (somente leitura)

`php artisan notas:auditar-os`

Para uma Nota: `php artisan notas:auditar-os --nota=123`

A saída identifica vínculos regulares, OS abertas ligadas a Notas finalizadas, referências ausentes, vínculos duplicados, clientes/veículos divergentes e situações que exigem revisão.

O relatório global exibe até 2.000 vínculos por padrão. Use `--limite=10000` ou `--nota=ID` para evitar resultados truncados. Não execute auditoria nem correção em produção sem autorização operacional.

## Reconciliação autorizada

Somente depois de avaliar os vínculos e aprovar uma Nota específica:

`php artisan notas:auditar-os --nota=123 --corrigir --confirmar=123`

A operação exige Nota `Finalizado`/`Concluido`, OS únicas, existentes, com cliente/veículo compatíveis e situação `aberta` ou `finalizada` com etapa final correta. Atualiza apenas as OS ainda abertas por meio da Action oficial, com etapa final, data de fechamento, histórico e transação única. Repetição não gera nova movimentação.

**Não corrige automaticamente** Nota cancelada, OS cancelada, referências ausentes ou duplicadas, divergência de cliente/veículo, nem OS finalizada em etapa inconsistente. Essas situações precisam de análise humana. Não altera estoque, pagamentos, financeiro, Notas ou OS encerradas corretamente.

A homologação deve usar banco **descartável**, com backup e revisão do diagnóstico antes de qualquer uso em ambiente real.
