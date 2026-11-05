# P03 — Auditoria das dependencias

> Gerado automaticamente a partir dos lockfiles. Nao executa `install`, `update` ou `audit fix`.

## Inventario imutavel

- Composer: 114 pacotes de producao e 39 de desenvolvimento.
- NPM: 193 entradas instalaveis; lockfile v3.

## Proximos passos

1. Corrigir vulnerabilidades por grupos pequenos e atualizar ambos lockfiles quando cabivel.
2. Validar `npm ci && npm run build` em ambiente isolado e testes PHP com banco de homologacao.
3. Nao executar `npm audit fix --force`, `composer update` geral nem deploy neste modulo.
