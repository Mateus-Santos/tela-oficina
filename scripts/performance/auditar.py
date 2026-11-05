#!/usr/bin/env python3
"""Inventario estatico, sem banco, rede ou Docker. Nao diagnostica latencia."""
import argparse
import collections
import pathlib
import re
import subprocess


def files_from_git(root):
    data = subprocess.check_output(['git', 'ls-files', '-z'], cwd=root)
    return [pathlib.Path(s.decode('utf-8')) for s in data.split(b'\0') if s]


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--saida', type=pathlib.Path, default=None)
    args = ap.parse_args()
    root = pathlib.Path(__file__).resolve().parents[2]
    tracked = [p for p in files_from_git(root) if (root / p).is_file()]
    size = collections.Counter()
    counts = collections.Counter()
    sizes = []
    for p in tracked:
        n = (root / p).stat().st_size
        key = p.parts[0]
        size[key] += n
        counts[key] += 1
        if p.suffix in ('.php','.css','.scss','.js','.vue'):
            sizes.append((n, str(p)))
    selected = {
        'Controllers': 'app/Http/Controllers/',
        'Actions': 'app/Actions/',
        'Form Requests': 'app/Http/Requests/',
        'Views': 'resources/views/',
        'JavaScript': 'resources/js/',
        'SCSS': 'resources/scss/',
        'Vendor publico': 'public/vendor/',
        'Testes': 'tests/',
    }
    lines = [
        '# Inventario de performance — SOS MECANICA',
        '',
        '> Auditoria estatica de arquivos versionados. NAO e benchmark de RAM, CPU, HTTP ou SQL.',
        '',
        f'- Arquivos Git: **{len(tracked)}**',
        f'- Bytes versionados em `public/vendor/`: **{sum((root/p).stat().st_size for p in tracked if str(p).startswith("public/vendor/")):,}**',
        '', '## Estrutura', '', '| Area | Arquivos |', '|---|---:|'
    ]
    for label, prefix in selected.items():
        lines.append(f'| {label} | {sum(1 for p in tracked if str(p).startswith(prefix))} |')
    lines += ['', '## Maiores fontes (nao inclui bibliotecas public/vendor)', '', '| Bytes | Arquivo |', '|---:|---|']
    for n,p in sorted((n,p) for n,p in sizes if not p.startswith('public/vendor/'))[-15:][::-1]:
        lines.append(f'| {n:,} | `{p}` |')
    controllers = [p for p in tracked if str(p).startswith('app/Http/Controllers/') and p.suffix == '.php']
    inline_val = []
    for p in controllers:
        src = (root/p).read_text(encoding='utf-8-sig')
        if '->validate(' in src or re.search(r'\bValidator::make\(', src):
            inline_val.append(str(p))
    lines += ['', '## Controllers com validacao direta (triagem; revisar contexto)', '']
    lines += ['- `'+p+'`' for p in inline_val] or ['- Nenhum encontrado neste criterio.']
    sql = []
    for p in tracked:
        if str(p).startswith('app/') and p.suffix == '.php':
            src = (root/p).read_text(encoding='utf-8-sig')
            if re.search(r'\b(?:whereRaw|selectRaw|orderByRaw|havingRaw|DB::raw|DB::select|DB::statement)\s*\(', src):
                sql.append(str(p))
    lines += ['', '## SQL manual / raw (triagem, NAO comprovacao de injecao)', '']
    lines += ['- `'+p+'`' for p in sql] or ['- Nenhum encontrado neste criterio.']
    layout = (root/'resources/views/layouts/layout.blade.php').read_text(encoding='utf-8')
    key = '/vendor/waypoints/noframework.waypoints.js'
    lines += ['', '## Carregamento global', '', f'- Ocorrencias de Waypoints no layout: **{layout.count(key)}** (esperado apos correcao: 1).', '- Bibliotecas externas no layout precisam de rastreamento de uso antes da remocao.', '', '## Proximas etapas', '', '1. Baseline de RAM/CPU/tempo por rota antes de mudar FPM.', '2. Dependencias: npm audit/outdated e composer audit --locked em ambiente de desenvolvimento.', '3. Mapear imports e bibliotecas por pagina; dividir JS e CSS por tela.', '4. Query budget + EXPLAIN e testes N+1 para listagens de notas, compras e estoque.', '5. Contratos e integridade: testes de cliente, veiculo, nota e valores financeiros.', '6. Requests/Actions, componentes Blade, duplicacoes e codigo morto.', '']
    content = '\n'.join(lines)
    if args.saida:
        dest = root / args.saida
        dest.parent.mkdir(parents=True, exist_ok=True)
        dest.write_text(content, encoding='utf-8')
        print(f'Inventario gerado: {dest}')
    else:
        print(content)

if __name__ == '__main__': main()
