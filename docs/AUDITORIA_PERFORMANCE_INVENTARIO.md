# Inventario de performance — SOS MECANICA

> Auditoria estatica de arquivos versionados. NAO e benchmark de RAM, CPU, HTTP ou SQL.

- Arquivos Git: **816**
- Bytes versionados em `public/vendor/`: **13,732,006**

## Estrutura

| Area | Arquivos |
|---|---:|
| Controllers | 39 |
| Actions | 62 |
| Form Requests | 46 |
| Views | 179 |
| JavaScript | 40 |
| SCSS | 23 |
| Vendor publico | 78 |
| Testes | 51 |

## Maiores fontes (nao inclui bibliotecas public/vendor)

| Bytes | Arquivo |
|---:|---|
| 76,348 | `resources/js/notaitem/itens.js` |
| 54,896 | `resources/views/compra/show.blade.php` |
| 52,931 | `tests/Feature/Notas/FinalizarNotaTest.php` |
| 41,424 | `resources/views/notas_item/show_notas_itens.blade.php` |
| 37,285 | `resources/views/notas_item/_form.blade.php` |
| 35,180 | `resources/js/notaitem/descontos.js` |
| 34,800 | `tests/Feature/Financeiro/ContasReceberTest.php` |
| 34,092 | `resources/views/financeiro/contas_receber/show.blade.php` |
| 32,267 | `app/Services/Catalogo/FornecedorSCsvImporter.php` |
| 31,216 | `resources/views/pdf/nota_interna.blade.php` |
| 29,078 | `resources/views/livewire/nota/seletor-cliente-veiculo.blade.php` |
| 28,474 | `app/Livewire/Nota/SeletorClienteVeiculo.php` |
| 28,190 | `resources/views/produto/showproduto.blade.php` |
| 26,845 | `resources/views/compra/_form.blade.php` |
| 26,177 | `resources/views/layouts/layout.blade.php` |

## Controllers com validacao direta (triagem; revisar contexto)

- `app/Http/Controllers/Api/CatalogoBuscaController.php`
- `app/Http/Controllers/Api/ClienteContaReceberBuscaController.php`
- `app/Http/Controllers/Api/NotaContaReceberBuscaController.php`
- `app/Http/Controllers/Api/NotaItemBuscaController.php`
- `app/Http/Controllers/Api/NotaOrdemServicoRapidaController.php`
- `app/Http/Controllers/Api/ProdutoBuscaController.php`
- `app/Http/Controllers/CatalogoProdutoComplementoController.php`
- `app/Http/Controllers/ClienteController.php`
- `app/Http/Controllers/EstoqueController.php`
- `app/Http/Controllers/EtapaController.php`
- `app/Http/Controllers/FinanceiroSociosController.php`
- `app/Http/Controllers/FinanceiroSociosReceberController.php`
- `app/Http/Controllers/OrdemServicoController.php`

## SQL manual / raw (triagem, NAO comprovacao de injecao)

- `app/Http/Controllers/CatalogoProdutoComplementoController.php`
- `app/Http/Controllers/ContaPagarController.php`
- `app/Http/Controllers/ContaReceberController.php`
- `app/Http/Controllers/FornecedorController.php`
- `app/Http/Controllers/VeiculosClientesController.php`
- `app/Services/Catalogo/FornecedorSCsvImporter.php`
- `app/Services/Produto/ListarProdutosUnificados.php`
- `app/Services/Produto/ResolverAplicacoes.php`
- `app/Services/Produto/SincronizarAplicacoesCatalogo.php`

## Carregamento global

- Ocorrencias de Waypoints no layout: **1** (esperado apos correcao: 1).
- Bibliotecas externas no layout precisam de rastreamento de uso antes da remocao.

## Proximas etapas

1. Baseline de RAM/CPU/tempo por rota antes de mudar FPM.
2. Dependencias: npm audit/outdated e composer audit --locked em ambiente de desenvolvimento.
3. Mapear imports e bibliotecas por pagina; dividir JS e CSS por tela.
4. Query budget + EXPLAIN e testes N+1 para listagens de notas, compras e estoque.
5. Contratos e integridade: testes de cliente, veiculo, nota e valores financeiros.
6. Requests/Actions, componentes Blade, duplicacoes e codigo morto.
