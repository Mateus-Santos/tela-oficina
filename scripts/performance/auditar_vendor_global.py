#!/usr/bin/env python3
"""P04.1: valida fontes do projeto antes de retirar JS legado do layout global.

Somente leitura. Nao consulta rede nem modifica arquivos de producao.
"""
import argparse
import re
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
LAYOUT = Path('resources/views/layouts/layout.blade.php')

# Padroes deliberadamente amplos: se houver uma referencia, conservamos o asset.
VENDORS = {
    'glightbox': {
        'script': "<script src=\"{{ asset('/vendor/glightbox/js/glightbox.min.js') }}\"></script>",
        'patterns': (r'\bGLightbox\b', r'\bglightbox\b', r'\blightbox\b'),
        'bytes': 55976,
    },
    'isotope': {
        'script': "<script src=\"{{ asset('/vendor/isotope-layout/isotope.pkgd.min.js') }}\"></script>",
        'patterns': (r'\bIsotope\b', r'\bisotope\b', r'\bisotope-layout\b'),
        'bytes': 35445,
    },
    'php-email-form': {
        'script': "<script src=\"{{ asset('/vendor/php-email-form/validate.js') }}\"></script>",
        'patterns': (r'php-email-form',),
        'bytes': 2646,
    },
    'swiper': {
        'script': "<script src=\"{{ asset('/vendor/swiper/swiper-bundle.min.js') }}\"></script>",
        'patterns': (r'\bSwiper\b', r'\bswiper(?:-|_)', r'\bswiper\b'),
        'bytes': 145446,
    },
}

ALLOWED_ROOTS = ('app/', 'resources/', 'routes/', 'public/', 'config/')
TEXT_SUFFIXES = ('.php', '.js', '.ts', '.jsx', '.tsx', '.vue', '.scss', '.css', '.html', '.mjs')


def fontes_de_aplicacao(root=ROOT):
    proc = subprocess.run(['git', 'ls-files', '-z', '--', *[x.rstrip('/') for x in ALLOWED_ROOTS]],
                          cwd=root, capture_output=True, check=True)
    for b in proc.stdout.split(b'\x00'):
        if not b:
            continue
        rel = Path(b.decode('utf-8', errors='replace'))
        text = rel.as_posix()
        if text.startswith('public/vendor/') or not text.endswith(TEXT_SUFFIXES):
            continue
        path = root / rel
        if path.is_file():
            yield rel, path.read_text(encoding='utf-8', errors='replace')


def referencias(root=ROOT):
    """Retorna dict vendor => [(arquivo, linha, fragmento)] de fontes, ignorando os includes a retirar."""
    achados = {vendor: [] for vendor in VENDORS}
    for rel, content in fontes_de_aplicacao(root):
        if rel == LAYOUT:
            # Remover apenas os includes exatos; outras referencias neste layout contam.
            for info in VENDORS.values():
                content = content.replace(info['script'], '')
        for num, linha in enumerate(content.splitlines(), 1):
            for vendor, info in VENDORS.items():
                if any(re.search(p, linha, re.I) for p in info['patterns']):
                    achados[vendor].append((rel.as_posix(), num, linha.strip()[:140]))
    return achados


def formatar(achados):
    lines = ['P04.1 — referencias de bibliotecas legadas em codigo da aplicacao']
    for vendor, hits in achados.items():
        lines.append(f'{vendor}: {len(hits)} referencia(s)')
        for path, num, line in hits[:15]:
            lines.append(f'  {path}:{num}: {line}')
        if len(hits) > 15:
            lines.append(f'  ... e mais {len(hits) - 15}')
    return '\n'.join(lines)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--strict', action='store_true', help='falhar se houver referencias aos quatro assets')
    args = parser.parse_args()
    hits = referencias()
    print(formatar(hits))
    return int(args.strict and any(hits.values()))


if __name__ == '__main__':
    raise SystemExit(main())
