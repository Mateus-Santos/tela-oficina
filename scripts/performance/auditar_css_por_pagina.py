#!/usr/bin/env python3
"""P04.3: auditoria sem efeitos colaterais para separacao do CSS por pagina."""
from pathlib import Path
import argparse

ROOT = Path(__file__).resolve().parents[2]
PAGES = {
    'resources/views/chat.blade.php': 'resources/scss/chat_entry.scss',
    'resources/views/produto/listarproduto.blade.php': 'resources/scss/produto_lista_entry.scss',
    'resources/views/produto/listarproduto_unificado.blade.php': 'resources/scss/produto_lista_entry.scss',
}


def verificar(root=ROOT):
    erros = []
    app = (root / 'resources/scss/_app.scss').read_text(encoding='utf-8')
    layout = (root / 'resources/views/layouts/layout.blade.php').read_text(encoding='utf-8')
    vite = (root / 'vite.config.js').read_text(encoding='utf-8')
    for bad in ("@use './_chat.scss';", "@use './_listarproduto.scss';"):
        if bad in app:
            erros.append(f'CSS global ainda inclui: {bad}')
    for rel, partial in (
        ('resources/scss/chat_entry.scss', "@use './_chat.scss';"),
        ('resources/scss/produto_lista_entry.scss', "@use './_listarproduto.scss';"),
    ):
        p = root / rel
        if not p.is_file() or p.read_text(encoding='utf-8').strip() != partial:
            erros.append(f'Entrada SCSS ausente ou diferente: {rel}')
        if vite.count(f"'{rel}'") != 1:
            erros.append(f'Versao Vite sem entrada unica: {rel}')
    if layout.count("@stack('p04-css-pagina')") != 1:
        erros.append('Pilha de CSS especifico ausente/duplicada no layout.')
    if layout.find("@stack('p04-css-pagina')") < layout.find("'resources/scss/_app.scss'"):
        erros.append('Ordem CSS global/pagina invertida.')
    if layout.index("@stack('p04-css-pagina')") > layout.find('</head>'):
        erros.append('CSS por pagina deve ser injetado dentro do <head>.')
    if "@stack('p04-inputmask')" not in layout:
        erros.append('Layout perdeu Inputmask condicional do P04.2.')
    for rel, entry in PAGES.items():
        contents = (root / rel).read_text(encoding='utf-8')
        push = "@push('p04-css-pagina')"
        if contents.count(push) != 1 or contents.count("@endpush") < 1:
            erros.append(f'View sem stack CSS unico: {rel}')
        if contents.count(f"@vite('{entry}')") != 1:
            erros.append(f'View sem entrada CSS correta: {rel}')
    views = root / 'resources/views'
    hits = {k: [] for k in ('produto-container', 'box-questions')}
    for file in views.rglob('*.blade.php'):
        c = file.read_text(encoding='utf-8')
        for token in hits:
            if token in c:
                hits[token].append(file.relative_to(root).as_posix())
    if set(hits['produto-container']) != {p for p in PAGES if '/produto/' in p}:
        erros.append(f'produto-container reaproveitado fora das listagens: {hits["produto-container"]}')
    if set(hits['box-questions']) != {'resources/views/chat.blade.php'}:
        erros.append(f'box-questions reaproveitado fora do chat: {hits["box-questions"]}')
    return erros


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--strict', action='store_true')
    args = ap.parse_args()
    erros = verificar()
    for e in erros:
        print('PROBLEMA:', e)
    if not erros:
        print('P04.3: CSS especifico apenas para chat e listagens; layout compartilhado preservado.')
    return int(bool(erros) and args.strict)


if __name__ == '__main__':
    raise SystemExit(main())
