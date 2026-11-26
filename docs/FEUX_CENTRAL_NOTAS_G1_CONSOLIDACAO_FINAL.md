# Central de Notas FEUX — Integracao G1

- Base da candidata: `2178929b259d004d921a4944a8858cea6aeee37b` (`origin/development` na auditoria).
- Fonte: Gate F homologado, 88 arquivos; G1 reproduziu o patch byte a byte.
- Diferenca autorizada nesta consolidacao: feature flag OFF, middleware em todas as rotas Vue, fallback legado no menu e testes.
- `FEUX_NOTAS_VUE_ENABLED=false` e o padrao. As rotas administrativas Vue retornam 404 enquanto OFF; acesso cliente continua bloqueado pelo middleware `admin`.
- Link do menu OFF: `notasitem.index` (compatibilidade com a listagem tradicional). ON: `notas.workspace`.
- Para habilitar APOS homologacao visual: ajustar a variavel de ambiente da implantacao e reconstruir o cache de configuracao do Laravel de maneira controlada. Nao publicar a flag antes do aceite.
- A migration `2026_11_25_160000_feux_l02c_os_nota_obrigatoria.php` envolve NOT NULL, FK e unicidade. Antes de QUALQUER deploy na producao, ensaiar em copia segura de dados reais e validar a operacao da oficina.
- As migracoes MySQL nao sao revertidas automaticamente por rollback da imagem; release em producao exige plano de contingencia e aprovacao separada.
- O commit local nao equivale a push, PR, merge ou deploy. Publicacao depende de autorizacao explicita.
- Fora do escopo: Compras e Devolucoes FEUX V1–V4, B3FIX SQLite e alteracoes nao commitadas do menu frontend original.
- Homologacao em navegador (agente FEUX): verificar Kanban, busca/filtros, edicao, lotes, modais, finalizacao, cancelamento, responsividade, acessibilidade e fallback legacy com flag OFF.
