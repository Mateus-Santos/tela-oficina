"""P04.3 - evitar regressao no CSS especifico das paginas."""
import importlib.util
from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[2]
AUDITOR = ROOT / 'scripts/performance/auditar_css_por_pagina.py'
spec = importlib.util.spec_from_file_location('p04_css_audit', AUDITOR)
audit = importlib.util.module_from_spec(spec)
spec.loader.exec_module(audit)


class CssPorPaginaTest(unittest.TestCase):
    def test_modulos_foram_retirados_do_bundle_global(self):
        texto = (ROOT / 'resources/scss/_app.scss').read_text(encoding='utf-8')
        self.assertNotIn("@use './_chat.scss';", texto)
        self.assertNotIn("@use './_listarproduto.scss';", texto)
        self.assertIn("@use './_filtros.scss';", texto)
        self.assertIn("@use './_style_cad.scss';", texto)

    def test_vite_conhece_novas_entradas(self):
        vite = (ROOT / 'vite.config.js').read_text(encoding='utf-8')
        for file in ('resources/scss/chat_entry.scss', 'resources/scss/produto_lista_entry.scss'):
            self.assertEqual(vite.count(f"'{file}'"), 1)
            self.assertTrue((ROOT / file).is_file())

    def test_stack_no_head_e_depois_do_css_global(self):
        l = (ROOT / 'resources/views/layouts/layout.blade.php').read_text(encoding='utf-8')
        self.assertEqual(l.count("@stack('p04-css-pagina')"), 1)
        self.assertLess(l.index("'resources/scss/_app.scss'"), l.index("@stack('p04-css-pagina')"))
        self.assertLess(l.index("@stack('p04-css-pagina')"), l.index('</head>'))
        self.assertIn("@stack('p04-inputmask')", l)

    def test_telas_corretas_ativam_css(self):
        for file, entry in audit.PAGES.items():
            with self.subTest(file=file):
                s = (ROOT / file).read_text(encoding='utf-8')
                self.assertIn("@push('p04-css-pagina')", s)
                self.assertIn(f"@vite('{entry}')", s)

    def test_auditoria_sem_erros(self):
        self.assertEqual(audit.verificar(), [])

    def test_auditoria_somente_leitura(self):
        s = AUDITOR.read_text(encoding='utf-8')
        for x in ('write_text(', 'unlink(', 'rmtree(', 'git push', 'docker compose'):
            self.assertNotIn(x, s)


if __name__ == '__main__':
    unittest.main()
