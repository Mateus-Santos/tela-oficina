#!/usr/bin/env python3
"""P04.4 - verifica separacao de CSS da home e paginas informativas; somente leitura."""
import argparse
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
INDEX = 'resources/scss/_index.scss'
RESP = 'resources/scss/_responsividade.scss'
CHAT = 'resources/scss/_chat.scss'
HOME = 'resources/scss/_home.scss'
INFO = 'resources/scss/_informativo.scss'
HOME_ENTRY = 'resources/scss/home_entry.scss'
INFO_ENTRY = 'resources/scss/informativo_entry.scss'
PAGES = {
    'resources/views/index.blade.php': HOME_ENTRY,
    'resources/views/termos/termosdeuso.blade.php': INFO_ENTRY,
    'resources/views/errors/403.blade.php': INFO_ENTRY,
    'resources/views/errors/404.blade.php': INFO_ENTRY,
}
HOME_SELECTORS = ('.section-bg', '.about', '.team', '.member', '.redes', '#hero')
INFO_SELECTORS = ('.erro403,\n.erro404', '.termos-uso')


def verificar(root=ROOT):
    erros = []
    index = (root / INDEX).read_text(encoding='utf-8')
    resp = (root / RESP).read_text(encoding='utf-8')
    chat = (root / CHAT).read_text(encoding='utf-8')
    home = (root / HOME).read_text(encoding='utf-8')
    info = (root / INFO).read_text(encoding='utf-8')
    app = (root / 'resources/scss/_app.scss').read_text(encoding='utf-8')
    vite = (root / 'vite.config.js').read_text(encoding='utf-8')
    layout = (root / 'resources/views/layouts/layout.blade.php').read_text(encoding='utf-8')

    for selector in HOME_SELECTORS:
        p = re.compile(r'(?m)^' + re.escape(selector) + r'\s*\{')
        if p.search(index):
            erros.append(f'{selector}: ainda no CSS global.')
        if len(p.findall(home)) != 1:
            erros.append(f'{selector}: precisa estar no CSS da home exatamente uma vez.')

    for selector in INFO_SELECTORS:
        p = re.compile(r'(?m)^' + re.escape(selector) + r'\s*\{')
        if p.search(index):
            erros.append(f'{selector}: ainda no CSS global.')
        if len(p.findall(info)) != 1:
            erros.append(f'{selector}: nao aparece uma unica vez no CSS informativo.')

    for token in ('section {', '.section-title {', '#footer {', '.footer-bottom {',
                  '.mobile-nav-toggle {', '.navbar-mobile {', '.profile {', '.manutencao {',
                  '.veiculos {'):
        if token not in index:
            erros.append(f'CSS compartilhado ausente: {token}')

    if '.footer-top {' in index or '#footer {' not in home or '.footer-top {' not in home:
        erros.append('Rodape exclusivo da home nao foi isolado corretamente.')
    for max_width in (991, 768, 575):
        if f'@media (max-width: {max_width}px)' not in home:
            erros.append(f'Home sem regra responsiva: {max_width}px.')
    if re.search(r'(?m)^\s*#hero\s*\{', resp):
        erros.append('Regras responsivas #hero continuam no bundle global.')
    if re.search(r'(?m)^\s*\.box-questions\s*\{', resp):
        erros.append('Regra de chat em 300px ainda e global.')
    if '@media screen and (max-width: 300px)' not in chat and '@media (max-width: 300px)' not in chat:
        erros.append('Responsividade especial do chat nao foi migrada.')

    if (root / HOME_ENTRY).read_text(encoding='utf-8').strip() != "@use './_home.scss';":
        erros.append('Entrada CSS da home incorreta.')
    if (root / INFO_ENTRY).read_text(encoding='utf-8').strip() != "@use './_informativo.scss';":
        erros.append('Entrada CSS informativa incorreta.')

    for rel in (HOME_ENTRY, INFO_ENTRY):
        if vite.count(f"'{rel}'") != 1:
            erros.append(f'Vite nao tem uma entrada unica para {rel}.')

    if "@use './_index.scss';" not in app or "@use './_filtros.scss';" not in app or "@use './_style_cad.scss';" not in app:
        erros.append('Importacoes globais essenciais foram modificadas.')
    if "@stack('p04-css-pagina')" not in layout or "@stack('p04-inputmask')" not in layout:
        erros.append('Layout nao preserva pilhas P04.2 e P04.3.')
    if layout.index("'resources/scss/_app.scss'") > layout.index("@stack('p04-css-pagina')"):
        erros.append('CSS de pagina deve vir depois do global.')

    for rel, entry in PAGES.items():
        view = (root / rel).read_text(encoding='utf-8')
        if view.count("@push('p04-css-pagina')") != 1:
            erros.append(f'{rel}: pilha de CSS ausente ou repetida.')
        if view.count(f"@vite('{entry}')") != 1:
            erros.append(f'{rel}: stylesheet especifico incorreto.')
        if view.count('@endpush') < 1:
            erros.append(f'{rel}: pilha nao fechada.')

    return erros


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--strict', action='store_true')
    args = ap.parse_args()
    erros = verificar()
    print('P04.4 - CSS home / termos / erros / responsividade')
    for erro in erros:
        print('ERRO:', erro)
    if not erros:
        print('OK: CSS de pagina separado; CSS compartilhado preservado.')
    return int(bool(erros) and args.strict)


if __name__ == '__main__':
    raise SystemExit(main())
