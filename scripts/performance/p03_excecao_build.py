"""P03: excecao TEMPORARIA e verificavel para GHSA unica em dependencias de build.

NAO dispensa auditoria de producao, nao ignora advisories novos e expira automaticamente.
"""
from datetime import date, datetime, timezone
import hashlib
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
VENCE_EM = date(2026, 12, 1)
AVISO_PERMITIDO = 'GHSA-fc5q-39xc-vjpf'
URL_PERMITIDA = 'https://github.com/advisories/' + AVISO_PERMITIDO
LOCK_SHA = '29e4d233a14984e4ff395ba7727a1c4ffbe121f8a7276b1473e4c2b43f9f5061'
PACKAGE_SHA = '69cfb2e04a81fa84c3a7e59bb41ba9b47accb2283c20898a8d62e972da259324'
DOCKERFILE_SHA = '06745cdf6874b4c3878bf1fedd913fc8559250da649f8f91478518dbfb2fb865'
DOCKERIGNORE_SHA = '53d476c8f368722cc0a72c3b8ca6678012c3ae0a8c75b3c331e23534e878be82'
PACOTES_ORIGINAIS = frozenset({
    '@parcel/watcher', 'anymatch', 'chokidar', 'fast-glob', 'fdir',
    'laravel-vite-plugin', 'micromatch', 'picomatch', 'readdirp',
    'sucrase', 'tailwindcss', 'tinyglobby', 'vite', 'vite-plugin-full-reload',
})


def _sha(arquivo):
    return hashlib.sha256(arquivo.read_bytes()).hexdigest()


def avaliar_excecao(resultado, base=ROOT, hoje=None):
    """(permitido, motivo). Qualquer ambiguidade gera reprovação (fail closed)."""
    if hoje is None:
        hoje = datetime.now(timezone.utc).date()
    if hoje > VENCE_EM:
        return False, 'excecao expirada em 2026-12-01'
    if not isinstance(resultado, dict):
        return False, 'relatorio npm ausente/invalido'
    checks = {
        'package-lock.json': LOCK_SHA,
        'package.json': PACKAGE_SHA,
        'docker/production/Dockerfile.app': DOCKERFILE_SHA,
        '.dockerignore': DOCKERIGNORE_SHA,
    }
    try:
        if any(_sha(base / caminho) != sha for caminho, sha in checks.items()):
            return False, 'lockfile ou empacotamento mudou: revisar excecao'
        lock = json.loads((base / 'package-lock.json').read_text(encoding='utf-8'))
        package = json.loads((base / 'package.json').read_text(encoding='utf-8'))
        docker = (base / 'docker/production/Dockerfile.app').read_text(encoding='utf-8')
        ignore = (base / '.dockerignore').read_text(encoding='utf-8')
    except (OSError, ValueError):
        return False, 'nao foi possivel confirmar arquivos de empacotamento'
    if any(package.get(k) for k in ('dependencies', 'optionalDependencies')):
        return False, 'ha dependencias npm de runtime'
    if any(lock.get('packages', {}).get('', {}).get(k) for k in ('dependencies', 'optionalDependencies')):
        return False, 'lockfile declara dependencias npm de runtime'
    if ('node_modules' not in ignore.splitlines()
            or 'AS frontend' not in docker
            or 'AS runtime' not in docker
            or 'COPY --from=frontend' not in docker
            or '/build/public/build' not in docker):
        return False, 'premissas de imagem de producao nao confirmadas'

    vulnerabilidades = resultado.get('vulnerabilities')
    metadata = resultado.get('metadata', {}).get('vulnerabilities', {})
    if not isinstance(vulnerabilidades, dict) or not vulnerabilidades:
        return False, 'nenhuma vulnerabilidade para enquadrar na excecao'
    nomes = set(vulnerabilidades)
    if not nomes.issubset(PACOTES_ORIGINAIS):
        return False, 'novo pacote vulneravel fora da lista revisada'
    if not isinstance(metadata, dict) or any(metadata.get(s) != 0 for s in ('critical', 'moderate', 'low')):
        return False, 'severidades adicionais detectadas ou metadata incompleta'
    if metadata.get('high') != len(vulnerabilidades):
        return False, 'contagem de vulnerabilidades divergente'

    # Confirma nos 'nodes' de cada pacote, sem confiar apenas em metadata npm.
    diretos = set()
    for nome, info in vulnerabilidades.items():
        if not isinstance(info, dict) or info.get('severity') != 'high':
            return False, 'severidade desconhecida/adicional em ' + nome
        nodes = info.get('nodes')
        if not isinstance(nodes, list) or not nodes:
            return False, 'npm nao forneceu os caminhos afetados para ' + nome
        for node in nodes:
            if not isinstance(node, str) or not node.startswith('node_modules/'):
                return False, 'caminho npm inesperado em ' + nome
            pacote_lock = lock.get('packages', {}).get(node)
            if not isinstance(pacote_lock, dict) or pacote_lock.get('dev') is not True:
                return False, 'dependencia nao comprovadamente dev: ' + node
        vias = info.get('via')
        if not isinstance(vias, list) or not vias:
            return False, 'cadeia de avisos incompleta para ' + nome
        for via in vias:
            if isinstance(via, str):
                if via not in nomes:
                    return False, 'cadeia desconhecida via ' + via
            elif isinstance(via, dict):
                if (via.get('url') != URL_PERMITIDA
                        or via.get('name') != 'picomatch'
                        or via.get('severity') != 'high'):
                    return False, 'aviso nao autorizado encontrado em ' + nome
                diretos.add(nome)
            else:
                return False, 'formato npm desconhecido em ' + nome
    if diretos != {'picomatch'}:
        return False, 'aviso direto nao corresponde somente ao picomatch'

    # Cadeias transitivas devem terminar no aviso diretamente autorizado.
    def chega_ao_picomatch(nome, visitados):
        if nome in visitados:
            return False
        if nome == 'picomatch':
            return True
        for via in vulnerabilidades[nome]['via']:
            if isinstance(via, str) and chega_ao_picomatch(via, visitados | {nome}):
                return True
        return False

    if not all(chega_ao_picomatch(nome, set()) for nome in nomes):
        return False, 'cadeia nao termina no aviso autorizado'
    return True, ('EXCECAO TEMPORARIA: GHSA-fc5q-39xc-vjpf SOMENTE DEV; '
                  'arquivo de dependencias/empacotamento fixado; expira 2026-12-01')
