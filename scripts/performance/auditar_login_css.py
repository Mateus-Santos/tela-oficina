#!/usr/bin/env python3
"""Auditoria read-only P04.5: Bootstrap Icons usado apenas no login Jetstream."""
from __future__ import annotations

import argparse
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
CSS = 'resources/css/app.css'
LOGIN_CSS = 'resources/css/login.css'
GUEST = 'resources/views/layouts/guest.blade.php'
LOGIN = 'resources/views/auth/login.blade.php'
CDN = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'
PUSH = "@push('p04-login-css')"
STACK = "@stack('p04-login-css')"


def verificar(root: Path = ROOT) -> list[str]:
    problems = []
    app = (root / CSS).read_text(encoding='utf-8')
    login_css = (root / LOGIN_CSS).read_text(encoding='utf-8')
    guest = (root / GUEST).read_text(encoding='utf-8')
    login = (root / LOGIN).read_text(encoding='utf-8')
    vite = (root / 'vite.config.js').read_text(encoding='utf-8')
    layout = (root / 'resources/views/layouts/layout.blade.php').read_text(encoding='utf-8')
    if '@import url(' in app or 'bootstrap-icons@' in app or '.icons_login' in app:
        problems.append('CSS Tailwind principal ainda carrega Bootstrap Icons/estilos de login.')
    for directive in ("@import 'tailwindcss/base'", "@import 'tailwindcss/components'", "@import 'tailwindcss/utilities'", '[x-cloak]'):
        if directive not in app:
            problems.append(f'Tailwind ou x-cloak removido: {directive}')
    if login.count(PUSH) != 1 or login.count('@endpush') < 1:
        problems.append('Pagina de login sem push unico.')
    if login.count(CDN) != 1:
        problems.append('CDN Bootstrap Icons deve aparecer exatamente uma vez no login.')
    if login.count("@vite('resources/css/login.css')") != 1:
        problems.append('Login sem CSS local Vite.')
    if "<x-guest-layout>" not in login or login.index(PUSH) > login.index('<x-guest-layout>'):
        problems.append('Push do login deve ser definido antes do componente guest.')
    if guest.count(STACK) != 1 or guest.index(STACK) > guest.index('</head>'):
        problems.append('Pilha CSS login deve ocorrer uma vez no head do guest.')
    if guest.index(STACK) < guest.index("@vite(['resources/css/app.css', 'resources/js/app.js'])"):
        problems.append('CSS login deve ser injetado depois do Tailwind.')
    if "@vite(['resources/css/app.css', 'resources/js/app.js'])" not in guest:
        problems.append('Tailwind compartilhado ausente no guest.')
    if 'resources/css/app.css' not in (root / 'resources/views/layouts/app.blade.php').read_text(encoding='utf-8'):
        problems.append('Layout Jetstream app perdeu Tailwind.')
    if layout.count('bootstrap-icons@1.13.1') != 1:
        problems.append('Layout principal perdeu a biblioteca de icones original.')
    if vite.count("'resources/css/login.css'") != 1:
        problems.append('Vite precisa conhecer o CSS exclusivo do login.')
    if not all(x in login_css for x in ('.icons_login a .bi-google', '.icons_login a .bi-facebook', '.icons_login a .bi-apple')):
        problems.append('Estilos dos provedores de login nao preservados.')
    if 'font-size: 2.2rem' not in login_css:
        problems.append('Tamanho dos icones mudou inesperadamente.')
    for folder in ('auth', 'profile', 'api'):
        for p in (root / 'resources/views' / folder).rglob('*.blade.php'):
            if p.relative_to(root).as_posix() == LOGIN:
                continue
            source = p.read_text(encoding='utf-8')
            if re.search(r'\bbi\s+bi-[a-z0-9-]+', source, re.I):
                problems.append(f'Outro componente Jetstream necessita de Bootstrap Icons: {p.relative_to(root)}')
            if PUSH in source or CDN in source:
                problems.append(f'Carregamento Bootstrap inesperado: {p.relative_to(root)}')
    return problems


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument('--strict', action='store_true')
    args = parser.parse_args()
    errors = verificar()
    print('P04.5 — Auditoria CSS e Bootstrap Icons apenas no login')
    for e in errors:
        print('PROBLEMA:', e)
    if not errors:
        print('OK: CSS Tailwind global dos layouts Jetstream preservado; icones apenas no login.')
    return int(bool(errors) and args.strict)


if __name__ == '__main__':
    raise SystemExit(main())
