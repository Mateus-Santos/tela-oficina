"""P04.1: impedir regressao de includes globais obsoletos."""
import importlib.util
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
AUDITOR = ROOT / 'scripts/performance/auditar_vendor_global.py'
spec = importlib.util.spec_from_file_location('p04_audit', AUDITOR)
audit = importlib.util.module_from_spec(spec)
spec.loader.exec_module(audit)


class BibliotecasGlobaisTest(unittest.TestCase):
    def test_quatro_scripts_legados_nao_sao_carregados_globalmente(self):
        html = (ROOT / audit.LAYOUT).read_text(encoding='utf-8')
        for vendor, info in audit.VENDORS.items():
            with self.subTest(vendor=vendor):
                self.assertNotIn(info['script'], html)
                self.assertNotIn('/vendor/' + (vendor if vendor != 'php-email-form' else vendor) + '/', html)

    def test_dependencias_em_uso_permancem(self):
        html = (ROOT / audit.LAYOUT).read_text(encoding='utf-8')
        for needed in ('/vendor/aos/aos.js', '/vendor/waypoints/noframework.waypoints.js',
                       'jquery-3.7.1.js', 'bootstrap.bundle.min.js', 'inputmask.min.js',
                       "@yield('scripts')", 'bootstrap-modal-body-portal'):
            with self.subTest(needed=needed):
                self.assertIn(needed, html)

    def test_sem_referencias_de_codigo_a_scripts_retirados(self):
        hits = audit.referencias()
        erros = audit.formatar({k: v for k, v in hits.items() if v})
        self.assertFalse(any(hits.values()), erros)

    def test_auditoria_somente_leitura(self):
        s = AUDITOR.read_text(encoding='utf-8')
        self.assertIn("'git', 'ls-files'", s)
        self.assertNotIn('unlink(', s)
        self.assertNotIn('shutil.rmtree', s)


if __name__ == '__main__':
    unittest.main()
