# FEUX L03J — Refinamento de interação da Central de Notas Vue 3

Base obrigatória: `69b28a856f24b29c46b077c6227f5ff1742b0f83` (L03R).

## Escopo

- Evita resultados de filtros ou páginas antigas sobrescreverem a consulta mais recente;
  cancela também requisições pendentes de "Carregar mais" quando se altera o filtro.
- Evita exibir detalhes de uma Nota após abrir outra ou encerrar o modal.
- Mostra erro retornado pelo servidor dentro do formulário de criação/edição, mantendo
  as regras oficiais de validação no Laravel.
- Isola pesquisas assíncronas de cliente, veículo e item, e evita resultados de cliente
  anterior serem exibidos após seleção/troca de atendimento.
- Reforça a navegação por teclado, Escape, retorno de foco e indicações de foco no modal
  e painel de filtros.

Não altera Actions, controladores, banco de dados, migrations, rotas, contratos de
Nota/O.S., PDF, financeiro, nem os módulos Blade/Livewire de contingência.

## Gates restantes

O build Vite e os testes estáticos não substituem teste manual real nos navegadores.
Verificar no navegador: busca rápida alternando cliente e peças, abrir/fechar vários
modais, foco por Tab/Shift+Tab, filtro em mobile, recusa de validação com mensagem
no formulário, arrastar cartões e carregar mais durante mudança de filtros.

A L02C de integridade estrutural de O.S. e a revisão do PR #141 são trabalhos
separados e continuam pendentes. Sem merge/deploy.
