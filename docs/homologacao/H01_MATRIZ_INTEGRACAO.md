# H01 — Homologação integrada: Compras, Estoque, Financeiro, Notas e OS

**Estado:** candidata à homologação, **não** autorização para produção.

**Base de referência:** `development` (`49fdb891e1c81d9fb63bb1d77551a1004ecd1ca9`).

## 1. Evidência automatizada

O arquivo `tests/Feature/Homologacao/H01FluxoComprasFinanceiroTest.php` cobre:

1. Compra em conferência → aprovação → entrada de estoque → devolução parcial → crédito registrado **não utilizado** → repetição idempotente; sem pagamento, NF-e ou movimentação bancária.
2. Falta e avaria → estoque somente da quantidade aceita → conciliação `sem_ajuste` sem alterar estoque novamente.
3. Compra divergente com conta **individual legada**, parcela única e sem pagamentos → abatimento em conta, parcela e vínculo, sem alterar estoque ou criar pagamento.

A suíte CI obrigatória do repositório valida também os testes já existentes de Compras, D01A, D02, D03A, D03B, Contas a Pagar, Notas, OS e FEUX, com MySQL **descartável**.

**Condição necessária, mas não suficiente:** CI verde não prova interface operacional autenticada nem qualidade dos dados reais.

## 2. Homologação funcional manual (somente ambiente isolado)

Preencher data, responsável, captura e resultado de **cada** caso antes de marcar H01 como homologada.

| Caso | Cenário | Resultado | Evidência / observação |
|---|---|---|---|
| H01-01 | Criar compra fictícia, conferir, aprovar e verificar entrada única no estoque | Pendente | |
| H01-02 | Registrar falta, avaria e excesso; confirmar que somente quantidade aceita entra no estoque | Pendente | |
| H01-03 | Devolução parcial de compra sem Conta a Pagar ativa; repetir UUID e verificar ausência de saída duplicada | Pendente | |
| H01-04 | Tentar devolver acima do saldo e com conta ativa; ambos devem bloquear sem efeitos parciais | Pendente | |
| H01-05 | Pré-conciliação e efetivação: crédito não utilizado, reembolso a receber, sem ajuste e abatimento restrito | Pendente | |
| H01-06 | Contas agrupadas, várias parcelas ou com pagamentos: abatimento automático deve ser recusado | Pendente | |
| H01-07 | Contas a Pagar: seleção de Compras por ID/NF/fornecedor; filtro de selecionadas, total e fornecedor | Pendente | |
| H01-08 | Notas: finalizar Nota fictícia com OS aberta; conferir encerramento de OS, etapa e histórico | Pendente | |
| H01-09 | OS/Notas legadas: executar **somente auditoria** `notas:auditar-os` e revisar cada divergência | Pendente | |
| H01-10 | Perfis cliente e administrador: acesso autorizado, recusas esperadas, formulários responsivos | Pendente | |
| H01-11 | Conferir visualmente Notas, Clientes, Compras e Contas a Pagar em 320, 390, 768, 1024 e 1440 px | Pendente | |

**Para OS legadas:** `php artisan notas:auditar-os --nota=ID` é somente leitura. **Não usar `--corrigir`** sem autorização específica por Nota e backup validado. Nunca executar em produção como parte deste pacote.

## 3. Critérios de encerramento

- A PR H01 passa no CI completo para o **SHA exato** submetido.
- Os casos H01-01 a H01-11 são homologados em ambiente isolado, com evidência e responsável.
- Não existem inconsistências críticas em estoque, contas, pagamentos, Notas ou OS.
- Divergências legadas são registradas para revisão individual; nenhuma é corrigida em lote.
- Migrations pendentes são avaliadas em cópia descartável **antes** de qualquer plano de produção.
- A integração à `development`, caso aprovada, é independente da publicação na `master`.

## 4. Fora do escopo

Emissão fiscal de NF-e de devolução, consumo automático de créditos, confirmação de reembolso bancário, conciliação de contas agrupadas/multiparcelas/pagas e Forge V2.8.0. Esses itens continuam em sprints próprias.

## 5. Registro final

- SHA homologado: _________________________________
- CI / link: ______________________________________
- Banco isolado / identificador: ___________________
- Homologador e data: _____________________________
- Pendências críticas: ____________________________
- Decisão: [ ] Aprovada [ ] Reprovada [ ] Parcial
