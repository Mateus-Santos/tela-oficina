# P04.1 — Bibliotecas legadas carregadas em todas as paginas

## Justificativa e evidencia

O layout global `resources/views/layouts/layout.blade.php` referencia quatro arquivos JS legados que nao apresentam referencias de uso nas fontes da aplicacao:

| Biblioteca | Arquivo em `public/vendor` | Tamanho no repositorio |
|---|---|---:|
| GLightbox | `glightbox/js/glightbox.min.js` | 55.976 bytes |
| Isotope | `isotope-layout/isotope.pkgd.min.js` | 35.445 bytes |
| php-email-form | `php-email-form/validate.js` | 2.646 bytes |
| Swiper | `swiper/swiper-bundle.min.js` | 145.446 bytes |
| **Total** | 4 requisicoes estaticas do layout | **239.513 bytes (234 KiB)** |

Esses sao **tamanhos descomprimidos dos arquivos de origem**, nao bytes reais de rede e nem economia de RAM do servidor. A ausencia de inicializadores e referencias foi verificada por busca no repositorio GitHub em 04/11/2026 e e reavaliada no momento da instalacao local e pelo CI. O script `scripts/performance/auditar_vendor_global.py --strict` verifica todas as fontes versionadas em `app/`, `resources/`, `routes/`, `config/` e `public/` (exceto arquivos `public/vendor/`).

## Mudanca de baixo risco

Remover **somente quatro tags `<script>`** do layout global. Nenhum arquivo `public/vendor/` e removido; bibliotecas podem ser reincluidas em paginas especificas caso haja demanda. AOS permanece, pois existe atributo `data-aos` na home. Waypoints, Inputmask, jQuery, Bootstrap, hooks `@vite`, `@yield('scripts')` e o gerenciamento de modais sao preservados.

## Condicoes para integrar

1. A varredura local e os testes de regressao devem passar; qualquer referencia de uso bloqueia a remocao.
2. CI GitHub Actions: suite `tests/performance` e auditoria de dependencias + `npm ci && npm run build` devem passar.
3. Revisar telas essenciais em homologacao (home, login, clientes, OS, notas, compras, financeiro, modais, formulario de emails caso exista). Nao publicar na VPS diretamente.
4. Medir diferenca de requests, bytes transferidos e tempos reais no navegador em homologacao, considerando cache e compressao HTTP.

O modulo P04 completo continua abrangendo CSS/SCSS, JS por pagina, fontes, e `public/vendor/` nao utilizado. Nao apagar essas bibliotecas sem uma nova auditoria de referencias e testes de interface.
