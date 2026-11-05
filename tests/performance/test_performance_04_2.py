"""P04.2 — testes de regressao do carregamento condicional de Inputmask."""
import importlib.util
from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[2]
AUDITOR = ROOT / 'scripts/performance/auditar_inputmask.py'
spec = importlib.util.spec_from_file_location('p04_2_audit', AUDITOR)
audit = importlib.util.module_from_spec(spec)
spec.loader.exec_module(audit)


class InputmaskPorPaginaTest(unittest.TestCase):
    def test_inputmask_nao_global(self):
        layout = (ROOT / audit.LAYOUT).read_text(encoding='utf-8')
        self.assertIn(audit.STACK, layout)
        self.assertNotIn(audit.CDN, layout)
        self.assertEqual(layout.count(audit.STACK), 1)

    def test_mascaras_preservadas_em_sete_formularios(self):
        self.assertEqual(len(audit.PAGINAS), 7)
        for page in audit.PAGINAS:
            with self.subTest(page=page):
                content = (ROOT / page).read_text(encoding='utf-8')
                self.assertEqual(content.count(audit.PUSH), 1)
                self.assertEqual(content.count(audit.CDN), 1)
                self.assertIn(audit.VITE, content)

    def test_sem_usos_esquecidos(self):
        errs, info = audit.verificar()
        self.assertEqual(errs, [], '\n'.join(errs))
        self.assertEqual(info['paginas_com_validateForm'], 7)

    def test_scripts_criticos_preservados(self):
        layout = (ROOT / audit.LAYOUT).read_text(encoding='utf-8')
        for token in ('bootstrap.bundle.min.js', 'jquery-3.7.1.js',
                      '@yield(\'scripts\')', '/vendor/aos/aos.js',
                      'bootstrap-modal-body-portal', '@vite(['):
            with self.subTest(token=token):
                self.assertIn(token, layout)
        js = (ROOT / audit.JS).read_text(encoding='utf-8')
        for token in ('Inputmask(', 'validateCpf(', 'validatePhone('):
            self.assertIn(token, js)

    def test_auditor_somente_leitura(self):
        text = AUDITOR.read_text(encoding='utf-8')
        for forbidden in ('unlink(', 'rmtree(', 'write_text(', 'git push', 'docker compose'):
            self.assertNotIn(forbidden, text)


if __name__ == '__main__':
    unittest.main()
