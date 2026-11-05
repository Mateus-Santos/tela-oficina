# SOS MECÂNICA — P03 / Atualização de dependências

**Base:** `development` após P02. **Este pacote é apenas P03.1 (inventário + auditoria + CI de build), não uma atualização em massa.**

## Inventário extraído dos lockfiles

- Composer: 114 pacotes `packages` e 39 `packages-dev` = 153 entradas.
- NPM: 194 entradas em `packages`, incluindo a raiz; 193 pacotes instaláveis.
- Laravel/framework bloqueado em `v13.38.0` no `composer.lock`.
- Vite bloqueado em `8.3.5`; Laravel Vite Plugin em `3.2.0`; Tailwind CSS em `3.4.19`.
- `@babel/plugin-proposal-optional-chaining` consta no NPM e `webpack.mix.js` ainda existe. Investigar uso antes de remover.

## Critérios e sequência

1. **P03.1 (este pacote):** consultar advisories via Composer e NPM, validar `composer.json`, testar `npm ci`/`npm run build`, gerar relatório e falhar o CI se houver achados relevantes ou indisponibilidade dos serviços de auditoria. Nenhum `composer update`, `npm audit fix` nem alteração de lockfile é feito aqui.
2. **P03.2:** corrigir advisories de maior gravidade em alterações pequenas; atualizar `composer.lock` e `package-lock.json` com ferramentas oficiais e versões compatíveis, sem `--force`.
3. **P03.3:** retirar dependências obsoletas comprovadamente sem uso, validar assets gerados, testes PHP em base isolada e regressão de telas críticas.

O workflow CI anexará `P03_AUDITORIA_DEPENDENCIAS.md` ao PR, inclusive se identificar vulnerabilidades e reprovar a verificação. **CI vermelho nessa primeira auditoria não significa erro no instalador:** indica achados que precisam ser tratados antes do merge. Não publicar na VPS nesta etapa.

**Limites:** sem banco, migrations, deploy, alteração de containers de produção ou ligação com o Forge DB Oficina.

**Referências oficiais:** https://getcomposer.org/doc/03-cli.md#audit e https://docs.npmjs.com/cli/commands/npm-audit/.
