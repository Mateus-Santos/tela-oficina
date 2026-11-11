# F02.2 — Validação offline e histórico privado no painel Forge

- Novo Artisan `forge:validar-e-registrar {pacote}`: executado no servidor Laravel, fora das requisições HTTP.
- Reutiliza o validador F01.1 já integrado. Sem MySQL, upload, importação ou mudanças nas tabelas.
- Registra cada execução em arquivo JSON distinto em `storage/app/forge-v270/.validacoes/<pacote>/` com permissões privadas (`0700` para diretórios, `0600` para arquivos), publicação atômica.
- Relatórios de reprovação não apresentam detalhes internos no navegador; o motivo detalhado fica apenas na saída do comando.
- O GET administrativo exibe **apenas o último relatório histórico** dos pacotes listados. A página não executa o validador.
- A interface sinaliza mudança no SHA-256 do manifesto desde a verificação, mas isso **não comprova que os CSVs permaneçam intactos**. Todos os CSVs devem ser revalidados antes de eventual importação.
- O formato do pacote e o manifesto ainda não são assinados criptograficamente: o relatório não prova a origem do emissor.
- Em produção, executar o comando de forma autorizada no container Laravel e verificar permissões da pasta staging. Não automatizar até ter fila, autorização, travas e auditoria da futura importação.

- Relatorios aprovados exigem SHA-256 valido e coincidente com o manifesto no momento do registro.
- Se o relatorio mais recente estiver corrompido, nao sera exibida uma aprovacao anterior como atual.
- Essas protecoes nao substituem a validacao dos CSVs.

## Próxima etapa

F02.3: mecanismo assíncrono de solicitação de validação autenticada pelo painel, com fila não sincronizada, rate limit, progresso e auditoria; sem habilitar importação. F02.4: comparação e plano de atualização dos catálogos, sempre preservando dados operacionais.
