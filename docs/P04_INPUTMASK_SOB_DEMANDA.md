# P04.2 — Inputmask somente nos formularios que precisam de mascara

## Evidencia do repositorio

O layout `resources/views/layouts/layout.blade.php` carrega `inputmask@5.0.6` via CDN em **todas** as paginas baseadas nesse layout, mas a chamada `Inputmask(...)` aparece somente em `resources/js/validateForm.js`. Esse modulo e utilizado por sete views: cadastro/edicao de montadoras, veiculos, veiculos de clientes e cadastro de ordem de servico.

## Mudanca proposta

- Substituir o `<script>` global por `@stack('p04-inputmask')` no **mesmo ponto do `<head>`**.
- Usar `@push('p04-inputmask')` nessas sete views, incluindo **a mesma versao** e URL do Inputmask.
- Preservar os formularios, a implementacao de validacao, `@vite`, CSS, PHP, banco, Bootstrap, jQuery e o modal portal.
- Nao atualizar a versao do CDN nem instalar bibliotecas; esse tema fica para outra revisao.

## Beneficio e limitacoes

Outras telas deixam de solicitar o Inputmask ao CDN. Nenhuma promessa de bytes exatos ou ganho de RAM: tamanho de download, cache e compressao precisam de medicao real no navegador. O impacto sobre tela com mascara tende a ser neutro, porque o script continua carregado no head antes da execucao dos modulos Vite diferidos.

## Validacao e aceite

1. Executar `python3 scripts/performance/auditar_inputmask.py --strict` e a suite `tests/performance`.
2. CI do GitHub: dependencias, build Vite e testes estaticos sem falha.
3. Testar em homologacao mascaras de telefone, CPF e CEP, validacao de formularios e inicializacao de ordem de servico; verificar console JavaScript.
4. Medir em navegador: homepage e listas nao devem mais requisitar Inputmask; sete formularios devem continuar carregando-o. Publicar somente depois de homologar a release acumulada.

Sem deploy, migrations, alteracao de dados ou reinicio de containers nesta etapa.
