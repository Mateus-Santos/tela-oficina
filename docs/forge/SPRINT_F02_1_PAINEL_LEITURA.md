# F02.1 — Painel Forge administrativo (somente leitura)

- GET `/admin/integracoes/forge`, nome `admin.forge.index`.
- Protegido pelos middlewares `auth`, `check.blocked` e `admin`.
- Mostra até 30 diretórios `pronto-*` existentes no staging privado `storage/app/forge-v270`.
- Verifica apenas nome/diretório e presença de manifesto pequeno e legível. Não interpreta o manifesto nem lê CSVs.
- Não há endpoint de importação, upload, validação integral, comparação de conteúdo nem operações de escrita.
- A ação “Verificar pacotes recebidos” recarrega a lista; **não pesquisa o computador Forge remoto**.
- Testes de autorização e interface exigem MySQL de teste isolado, por utilizarem `RefreshDatabase`.

## Próximas fases

F02.2: validação sob demanda fora da requisição HTTP e histórico persistente de pacotes.
F02.3: comparação de snapshots, com definições explícitas para alterações, exclusões e dados locais preservados.
F02.4: fila, travas, auditoria, confirmação administrativa e gates de importação `--somente-catalogo`.

**Não habilitar atualizações em produção sem restauro de backup testado e homologação em clone recente.**
