"""Verifica explicitamente que o controle P03 falha fechado."""
import importlib.util
import json
from pathlib import Path
import tempfile
import unittest
from datetime import date

ROOT = Path(__file__).resolve().parents[2]
SPEC = importlib.util.spec_from_file_location('p03_excecao_build', ROOT / 'scripts/performance/p03_excecao_build.py')
POL = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(POL)


class ExcecaoP03Test(unittest.TestCase):
    def payload(self):
        return {
            'metadata': {'vulnerabilities': {'critical': 0, 'high': 2, 'moderate': 0, 'low': 0}},
            'vulnerabilities': {
                'picomatch': {
                    'severity': 'high', 'nodes': ['node_modules/picomatch'],
                    'via': [{'name': 'picomatch', 'severity': 'high', 'url': POL.URL_PERMITIDA}],
                },
                'vite': {'severity': 'high', 'nodes': ['node_modules/vite'], 'via': ['picomatch']},
            },
        }

    def test_excecao_exata_dev_tem_prazo(self):
        ok, _ = POL.avaliar_excecao(self.payload(), hoje=date(2026, 11, 17))
        self.assertTrue(ok)

    def test_excecao_expirada_reprova(self):
        ok, _ = POL.avaliar_excecao(self.payload(), hoje=date(2026, 12, 2))
        self.assertFalse(ok)

    def test_novo_aviso_reprova(self):
        data = self.payload()
        data['vulnerabilities']['picomatch']['via'][0]['url'] = 'https://github.com/advisories/GHSA-outra'
        ok, _ = POL.avaliar_excecao(data, hoje=date(2026, 11, 17))
        self.assertFalse(ok)

    def test_novo_pacote_reprova(self):
        data = self.payload()
        data['vulnerabilities']['pacote-novo'] = data['vulnerabilities']['vite']
        data['metadata']['vulnerabilities']['high'] = 3
        ok, _ = POL.avaliar_excecao(data, hoje=date(2026, 11, 17))
        self.assertFalse(ok)

    def test_outra_severidade_reprova(self):
        data = self.payload()
        data['metadata']['vulnerabilities']['moderate'] = 1
        ok, _ = POL.avaliar_excecao(data, hoje=date(2026, 11, 17))
        self.assertFalse(ok)

    def test_caminho_sem_evidencia_dev_reprova(self):
        data = self.payload()
        data['vulnerabilities']['vite']['nodes'] = ['node_modules/nao-existe']
        ok, _ = POL.avaliar_excecao(data, hoje=date(2026, 11, 17))
        self.assertFalse(ok)

    def test_cadeia_fora_aviso_reprova(self):
        data = self.payload()
        data['vulnerabilities']['vite']['via'] = ['vite']
        ok, _ = POL.avaliar_excecao(data, hoje=date(2026, 11, 17))
        self.assertFalse(ok)

    def test_lockfile_modificado_reprova(self):
        data = self.payload()
        with tempfile.TemporaryDirectory() as tmp:
            base = Path(tmp)
            for file in ('package.json', 'package-lock.json', 'docker/production/Dockerfile.app', '.dockerignore'):
                dst = base / file
                dst.parent.mkdir(parents=True, exist_ok=True)
                dst.write_bytes((ROOT / file).read_bytes())
            (base / 'package-lock.json').write_text('{}', encoding='utf-8')
            ok, _ = POL.avaliar_excecao(data, base=base, hoje=date(2026, 11, 17))
            self.assertFalse(ok)


if __name__ == '__main__':
    unittest.main()
