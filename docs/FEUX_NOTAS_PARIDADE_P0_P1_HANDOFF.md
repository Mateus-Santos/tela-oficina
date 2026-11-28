# FEUX — Paridade operacional da Central Vue P0/P1

Base de trabalho `development` b3794337a8f11469b61c64a0c954ff3124ea9466.

- **P0 — Busca**: Vue consome a rota existente `api.notas-itens.buscar` (`/api/notas-itens/buscar?tipo=produto`). A busca já era protegida por `auth`, `check.blocked` e `admin`. O mesmo controlador retorna marca, descrição, códigos, preço, imagem, estoque e referências de catálogo. Acrescentada pesquisa por nome da marca ao controlador oficial. Referências de catálogo não recebem ID operacional nem podem ser selecionadas; acesso ao cadastro oficial só por URL interna confiável. Requisições antigas não substituem resultados novos; 401/419, 403 e ausência de resultados aparecem na interface.
- **P1 — Garantia**: campo editável `garantia_dias` exposto para os itens; já suportado por Requests e Actions.
- **P1 — KM**: campo de diferença em KM restaura cálculo legado (KM atual + distância = próxima troca); grava somente `km_proxima_troca_oleo`, contrato existente.
- **P1 — Descontos**: o legado mostra somatório dos descontos por item, não existe regra independente de desconto global nas Requests/Actions. Vue agora mostra subtotal, descontos e total, sem inventar payload `desconto_global`.
- **P2 — Legado**: Show legado preservado para gestão de anexos, estoque avançado, financeiro, PDFs e operações ainda não homologadas na Central.
- **NPM**: removida dependência direta depreciada `@babel/plugin-proposal-optional-chaining` por não haver configuração Babel que a utilize; lockfile atualizado com npm. Não aprovar scripts de terceiros automaticamente.

## Matriz e gates

| Função | Legado | Vue base | Correção | Dependência | Teste |
|---|---|---|---|---|---|
| Busca produto rica | Disponível | Incompleta | Rota oficial + cartão com marca, imagem, códigos e estoque | Route/Controller legado | Node P0 + build + navegador |
| Referência catálogo | Consulta, sem vínculo | Ausente | Identificação e link oficial sem seleção | Catálogo atual | Node P0 |
| Antigas requisições/erros/sessão | Legado controla | Parcial | AbortController, geração, status HTTP | Sessão web | Node + navegador |
| Garantia por item | Disponível | Payload oculto | Input vinculado | Store/UpdateNotaRequest | Node P1 + navegador |
| Diferença de KM | Calculada | Só KM final | Distância calculada | Contrato KM atual/próxima | Node P1 + navegador |
| Total de descontos | Exibido | Só total líquido | Subtotal/descontos/total | Somatório dos itens | Node P1 |
| Desconto geral editável independente | Não comprovado | Ausente | Não introduzido | Contrato financeiro exige desenho se necessário | Sem teste |
| Anexos CRUD completo | Disponível | Envio + link | Fallback preservado | Show legado | Navegador pendente |
| Estoque e finanças avançadas | Disponível | Leitura parcial | Fallback preservado | Actions Laravel | Navegador pendente |
| O.S. vinculada e finalização | Disponível | API e rascunho | Sem alteração | Integridade L02C | CI PHP/MySQL isolado pendente |

## Limitações e homologação

Sem migrations, mudanças financeiras ou gravações automáticas. Os testes Node validam contrato e funções auxiliares; **não provam** respostas reais do MySQL, arquivos de imagem acessíveis, fluxos de edição, permissão administrativa ou operação de finalização. Validar no Docker isolado com dados fictícios (não aplicar migrations no volume antigo sem backup). O CI deve executar PHP/MySQL isolado. Não fazer merge nem deploy sem aprovação.
