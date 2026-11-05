#!/usr/bin/env python3
"""P03: auditoria de lockfiles, sem atualizar dependencias nem tocar na VPS."""
import argparse
import json
import os
from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[2]


def carregar_lockfiles(base=ROOT):
    composer = json.loads((base / 'composer.lock').read_text(encoding='utf-8'))
    npm = json.loads((base / 'package-lock.json').read_text(encoding='utf-8'))
    return {
        'php_prod': len(composer.get('packages', [])),
        'php_dev': len(composer.get('packages-dev', [])),
        'npm_packages': max(0, len(npm.get('packages', {})) - 1),
        'npm_lock_version': npm.get('lockfileVersion'),
    }


def contagem_composer(resultado):
    avisos = resultado.get('advisories', {})
    if isinstance(avisos, dict):
        vulnerabilidades = sum(len(v) if isinstance(v, list) else 1 for v in avisos.values())
    elif isinstance(avisos, list):
        vulnerabilidades = len(avisos)
    else:
        vulnerabilidades = 0
    abandonados = resultado.get('abandoned', {})
    return vulnerabilidades, len(abandonados) if isinstance(abandonados, (list, dict)) else 0


def contagem_npm(resultado):
    metadados = resultado.get('metadata', {}).get('vulnerabilities', {})
    severidades = ('info', 'low', 'moderate', 'high', 'critical')
    if isinstance(metadados, dict) and any(x in metadados for x in severidades) and all(isinstance(metadados.get(x, 0), int) for x in severidades):
        return {x: metadados.get(x, 0) for x in severidades}
    # Versoes do npm que omitem o bloco metadata: usar severidade por pacote.
    contagens = {x: 0 for x in severidades}
    for item in resultado.get('vulnerabilities', {}).values():
        if isinstance(item, dict) and item.get('severity') in contagens:
            contagens[item['severity']] += 1
    return contagens


def executar_json(comando, cwd=ROOT, timeout=240):
    try:
        result = subprocess.run(comando, cwd=cwd, capture_output=True, text=True, timeout=timeout, check=False)
    except (OSError, subprocess.TimeoutExpired) as exc:
        return None, f'Erro ao executar {comando[0]}: {type(exc).__name__}'
    try:
        payload = json.loads(result.stdout)
    except ValueError:
        return None, f'{comando[0]} nao retornou JSON valido (codigo {result.returncode}). Verificar conectividade/CLI.'
    if not isinstance(payload, dict) or 'error' in payload or result.returncode not in (0, 1):
        return None, f'{comando[0]} retornou falha da ferramenta/rede (codigo {result.returncode}).'
    return (payload, result.returncode), None


def relatorio(inventario, composer=None, npm=None, erros=()):
    linhas = [
        '# P03 — Auditoria das dependencias', '',
        '> Gerado automaticamente a partir dos lockfiles. Nao executa `install`, `update` ou `audit fix`.', '',
        '## Inventario imutavel', '',
        f"- Composer: {inventario['php_prod']} pacotes de producao e {inventario['php_dev']} de desenvolvimento.",
        f"- NPM: {inventario['npm_packages']} entradas instalaveis; lockfile v{inventario['npm_lock_version']}.", '',
    ]
    if composer is not None:
        vulnerabilidades, abandonados = contagem_composer(composer)
        linhas.extend(['## Composer — consulta ao Packagist', '',
            f'- Avisos de seguranca: **{vulnerabilidades}**',
            f'- Pacotes abandonados sinalizados: **{abandonados}**', ''])
        if vulnerabilidades:
            for pacote, itens in composer.get('advisories', {}).items():
                if isinstance(itens, list):
                    ids = [str(v.get('advisoryId') or v.get('cve') or v.get('title') or 'aviso') for v in itens if isinstance(v, dict)]
                    linhas.append(f'- `{pacote}`: {", ".join(ids[:10])}')
            linhas.append('')
    if npm is not None:
        contagem = contagem_npm(npm)
        linhas.extend(['## NPM — consulta ao registry', '',
            f"- Critical: **{contagem['critical']}**; high: **{contagem['high']}**; moderate: **{contagem['moderate']}**; low: **{contagem['low']}**.", ''])
        for pacote, info in sorted(npm.get('vulnerabilities', {}).items()):
            if isinstance(info, dict):
                fix = info.get('fixAvailable')
                linhas.append(f'- `{pacote}`: {info.get("severity", "indefinida")}; correcao disponivel: {str(fix)[:120]}')
        linhas.append('')
    if erros:
        linhas.extend(['## Falhas da auditoria', ''])
        linhas.extend(f'- {erro}' for erro in erros)
        linhas.append('')
    linhas.extend(['## Proximos passos', '',
        '1. Corrigir vulnerabilidades por grupos pequenos e atualizar ambos lockfiles quando cabivel.',
        '2. Validar `npm ci && npm run build` em ambiente isolado e testes PHP com banco de homologacao.',
        '3. Nao executar `npm audit fix --force`, `composer update` geral nem deploy neste modulo.', ''])
    return '\n'.join(linhas)


def main(argv=None):
    parser = argparse.ArgumentParser()
    parser.add_argument('--live', action='store_true', help='Consultar advisories via Composer e NPM (rede)')
    parser.add_argument('--saida', type=Path, default=None)
    args = parser.parse_args(argv)
    inventario = carregar_lockfiles()
    composer = npm = None
    erros = []
    findings = False
    if args.live:
        c, erro = executar_json(['composer', 'audit', '--locked', '--format=json', '--abandoned=report', '--no-interaction'])
        if erro:
            erros.append(erro)
        else:
            composer, code = c
            vulnerabilidades, _ = contagem_composer(composer)
            findings |= (vulnerabilidades > 0 or code != 0)
        n, erro = executar_json(['npm', 'audit', '--package-lock-only', '--json'])
        if erro:
            erros.append(erro)
        else:
            npm, code = n
            findings |= (sum(contagem_npm(npm).values()) > 0 or code != 0)
    texto = relatorio(inventario, composer, npm, erros)
    if args.saida:
        args.saida.parent.mkdir(parents=True, exist_ok=True)
        args.saida.write_text(texto, encoding='utf-8')
        print(f'Relatorio: {args.saida}')
    print(texto)
    if erros:
        return 2  # Ferramenta/rede falhou; nao declarar auditoria aprovada.
    if findings:
        return 1  # Achados exigem tratamento, nao atualizacao automatica.
    return 0


if __name__ == '__main__':
    sys.exit(main())
