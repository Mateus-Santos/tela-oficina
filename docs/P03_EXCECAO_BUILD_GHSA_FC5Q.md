# P03 — Proposta de exceção TEMPORÁRIA para dependências de compilação

**Estado: PROPOSTA; requer revisão e aprovação humana antes do merge.**

- Referência: PR #116 U02-FE; base `development` em `54eda9ff`.
- Aviso único: `GHSA-fc5q-39xc-vjpf` (CVE-2026-112366), ReDoS de `picomatch`.
- Em 2026-11-17, a base de avisos GitHub ainda indicava *patched versions: none*.
- Diagnóstico P03 V4: npm completo com 14 avisos *high* derivados do aviso; `npm audit --omit=dev` com zero avisos.
- A imagem de aplicação usa Node em estágio frontend de build e copia somente `public/build` para o estágio PHP; `node_modules` está excluído do contexto Docker.
- A falha do GitHub CI impede o merge da PR #116 até a resolução ou exceção aprovada.

## Condições implementadas (fail closed)

1. Executa a auditoria NPM **completa** e Composer como antes; não oculta os achados no relatório.
2. Aceita SOMENTE o ID GHSA acima, gravidade alta, pacotes previamente classificados e caminhos `node_modules` com `dev: true` no lockfile.
3. A dependência direta do advisory precisa ser `picomatch`; toda cadeia transitive deve terminar nela.
4. Exige fingerprints inalteradas de `package.json`, `package-lock.json`, Dockerfile da aplicação e `.dockerignore`.
5. Executa auditoria NPM adicional `--omit=dev` e exige zero avisos e retorno 0.
6. Não aceita avisos novos, severidades adicionais, ausência de evidência ou falha de rede/CLI.
7. Expira automaticamente **após 2026-12-01 (UTC)**; renovar exige nova avaliação explícita.
8. Não altera versões, lockfiles, banco, código de negócio, branches `development`/`master` nem a configuração de deploy.

## Risco residual e decisão

O ReDoS continua possível na ferramenta de build em condições que aceitem padrões glob não confiáveis. O ambiente de CI deve executar código revisado, com dependências fixadas pelo lockfile. Como a vulnerabilidade não está corrigida, o risco não pode ser declarado eliminado. A exceção proposta não equivale a correção de segurança.

**Antes de integrar esta PR:** revisão humana do código e da evidência; verificar se o fluxo de CI não utiliza glob controlado por usuário externo. Uma vez aprovada e integrada, reexecutar a PR #116. Não executar deploy automático.

Referências:
- https://github.com/advisories/GHSA-fc5q-39xc-vjpf
- https://github.com/Mateus-Santos/tela-oficina/pull/116
