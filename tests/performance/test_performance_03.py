"""P03: teste offline do inventario/interpretacao; consulta live no Actions."""
import importlib.util
import json
from pathlib import Path
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
SCRIPT = ROOT / 'scripts/performance/auditar_dependencias.py'
spec = importlib.util.spec_from_file_location('dependency_audit', SCRIPT)
audit = importlib.util.module_from_spec(spec)
spec.loader.exec_module(audit)


class AuditoriaDependenciasTest(unittest.TestCase):
    def test_lockfiles_e_numeros_reais(self):
        info = audit.carregar_lockfiles()
        self.assertGreater(info['php_prod'], 80)
        self.assertGreater(info['php_dev'], 20)
        self.assertGreater(info['npm_packages'], 100)
        self.assertEqual(info['npm_lock_version'], 3)

    def test_parser_composer(self):
        x = {'advisories': {'foo/bar': [{'advisoryId': 'A'}, {'advisoryId': 'B'}]}, 'abandoned': {'x/y': 'replacement'}}
        self.assertEqual(audit.contagem_composer(x), (2, 1))
        self.assertEqual(audit.contagem_composer({'advisories': {}, 'abandoned': []}), (0, 0))

    def test_parser_npm(self):
        x = {'metadata': {'vulnerabilities': {'info': 0, 'low': 1, 'moderate': 2, 'high': 3, 'critical': 4}}}
        self.assertEqual(audit.contagem_npm(x)['high'], 3)
        self.assertEqual(audit.contagem_npm({'vulnerabilities': {'x': {'severity': 'critical'}}})['critical'], 1)

    def test_relatorio_sem_consulta_a_rede(self):
        with tempfile.TemporaryDirectory() as d:
            root = Path(d)
            (root/'composer.lock').write_text(json.dumps({'packages': [{'name':'a'}], 'packages-dev': []}))
            (root/'package-lock.json').write_text(json.dumps({'lockfileVersion': 3, 'packages': {'': {}, 'node_modules/a': {}}}))
            texto = audit.relatorio(audit.carregar_lockfiles(root))
            self.assertIn('1 pacotes de producao', texto)
            self.assertIn('1 entradas instalaveis', texto)
            self.assertNotIn('Critical:', texto)

    def test_sem_atualizacao_automatica_de_lock(self):
        codigo = SCRIPT.read_text(encoding='utf-8')
        self.assertNotIn("['npm', 'audit', 'fix'", codigo)
        self.assertNotIn("['composer', 'update'", codigo)
        self.assertIn('--package-lock-only', codigo)
        self.assertIn('--locked', codigo)


if __name__ == '__main__':
    unittest.main()
