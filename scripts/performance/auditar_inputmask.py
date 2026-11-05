#!/usr/bin/env python3
"""P04.2: verifica a carga do Inputmask por pagina; apenas leitura."""
from pathlib import Path
import argparse
import subprocess

ROOT = Path(__file__).resolve().parents[2]
LAYOUT = Path('resources/views/layouts/layout.blade.php')
JS = Path('resources/js/validateForm.js')
VITE = "@vite(['resources/js/validateForm.js'])"
STACK = "@stack('p04-inputmask')"
PUSH = "@push('p04-inputmask')"
CDN = '<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.6/dist/inputmask.min.js"></script>'
PAGINAS = (
    'resources/views/montadora/cadastromontadora.blade.php',
    'resources/views/montadora/editarmontadora.blade.php',
    'resources/views/veiculo/cadastroveiculos.blade.php',
    'resources/views/veiculo/editarveiculos.blade.php',
    'resources/views/veiculosclientes/cadastroveiculosclientes.blade.php',
    'resources/views/veiculosclientes/editarveiculosclientes.blade.php',
    'resources/views/ordemservico/cadastro_os.blade.php',
)


def verificar(root=ROOT):
    """Retorna (erros, resumo); sem gravacao, conexao ou dependencias externas."""
    erros = []
    layout = (root / LAYOUT).read_text(encoding='utf-8')
    if layout.count(STACK) != 1:
        erros.append('Layout global deve conter uma unica pilha p04-inputmask.')
    if CDN in layout:
        erros.append('Inputmask continua global no layout.')
    if 'Inputmask(' not in (root / JS).read_text(encoding='utf-8'):
        erros.append('validateForm.js nao utiliza Inputmask; revisar escopo.')
    encontrados = set()
    pages = root / 'resources/views'
    for f in pages.rglob('*.blade.php'):
        relative = f.relative_to(root).as_posix()
        content = f.read_text(encoding='utf-8')
        if VITE in content:
            encontrados.add(relative)
        if content.count(CDN):
            if relative not in PAGINAS:
                erros.append(f'Inclusao Inputmask nao prevista: {relative}')
            if content.count(CDN) != 1:
                erros.append(f'Inputmask duplicado: {relative}')
        if PUSH in content and relative not in PAGINAS:
            erros.append(f'Pilha Inputmask inesperada: {relative}')
    if encontrados != set(PAGINAS):
        erros.append(f'Paginas que usam validateForm.js mudaram: {sorted(encontrados ^ set(PAGINAS))}')
    for relative in PAGINAS:
        content = (root / relative).read_text(encoding='utf-8')
        if content.count(PUSH) != 1 or content.count('@endpush') < 1 or content.count(CDN) != 1:
            erros.append(f'{relative}: pilha ou script Inputmask ausente/duplicado')
        if VITE not in content:
            erros.append(f'{relative}: falta Vite validateForm.js')
    return erros, {'formularios': len(PAGINAS), 'paginas_com_validateForm': len(encontrados)}


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--strict', action='store_true')
    args = parser.parse_args()
    errs, resumo = verificar()
    print('P04.2 — Inputmask sob demanda')
    print(f'Formularios com Inputmask: {resumo["formularios"]}')
    print(f'Paginas com validateForm.js: {resumo["paginas_com_validateForm"]}')
    if errs:
        for issue in errs:
            print('PROBLEMA:', issue)
    else:
        print('OK: Inputmask exclusivo dos formularios identificados.')
    return int(args.strict and bool(errs))


if __name__ == '__main__':
    raise SystemExit(main())
