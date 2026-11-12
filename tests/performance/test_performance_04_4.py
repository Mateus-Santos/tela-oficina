"""P04.4 - testes estaticos de regressao da separacao de CSS."""
import importlib.util
import re
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
AUDITOR = ROOT / 'scripts/performance/auditar_css_home.py'
spec = importlib.util.spec_from_file_location('audit_p04_4', AUDITOR)
audit = importlib.util.module_from_spec(spec)
spec.loader.exec_module(audit)


class CssHomeInformativoTest(unittest.TestCase):
    def test_auditoria_sem_alertas(self):
        self.assertEqual(audit.verificar(), [])

    def test_home_somente_na_home(self):
        index = (ROOT / audit.INDEX).read_text(encoding='utf-8')
        home = (ROOT / audit.HOME).read_text(encoding='utf-8')
        for selector in audit.HOME_SELECTORS:
            with self.subTest(selector=selector):
                padrao = r'(?m)^[ \t]*' + re.escape(selector) + r'\s*\{'
                self.assertNotRegex(index, padrao)
                self.assertRegex(home, padrao)

    def test_paginas_informativas(self):
        styles = (ROOT / audit.INFO).read_text(encoding='utf-8')
        for token in ('.erro403,', '.erro404 {', '.termos-uso {'):
            self.assertIn(token, styles)
        for rel in ('resources/views/errors/403.blade.php',
                    'resources/views/errors/404.blade.php',
                    'resources/views/termos/termosdeuso.blade.php'):
            self.assertIn("@vite('resources/scss/informativo_entry.scss')",
                          (ROOT / rel).read_text(encoding='utf-8'))

    def test_responsividade_da_home_foi_transferida(self):
        css = (ROOT / audit.HOME).read_text(encoding='utf-8')
        for screen in (992, 768):
            self.assertIn(f'@media (max-width: {screen}px)', css)
        self.assertIn('.box-questions', (ROOT / audit.CHAT).read_text(encoding='utf-8'))

    def test_modais_menu_filtros_e_rodape_compartilhados(self):
        css = (ROOT / audit.INDEX).read_text(encoding='utf-8')
        for token in ('#footer {', '.footer-bottom {', '.section-title {'):
            self.assertIn(token, css)

        menu = (ROOT / 'resources/scss/_menu.scss').read_text(encoding='utf-8')
        for token in ('#header {', '.navbar-toggler {', '.dropdown-menu {'):
            self.assertIn(token, menu)

        for token in ('.navbar-mobile {', '.mobile-nav-toggle {'):
            self.assertNotIn(token, css)
            self.assertNotIn(token, menu)
        global_app = (ROOT / 'resources/scss/_app.scss').read_text(encoding='utf-8')
        for partial in ("@use './_filtros.scss';", "@use './_style_cad.scss';", "@use './_menu.scss';"):
            self.assertIn(partial, global_app)

    def test_vite_e_pilha_preservados(self):
        vite = (ROOT / 'vite.config.js').read_text(encoding='utf-8')
        for entry in (audit.HOME_ENTRY, audit.INFO_ENTRY):
            self.assertEqual(vite.count(f"'{entry}'"), 1)
        layout = (ROOT / 'resources/views/layouts/layout.blade.php').read_text(encoding='utf-8')
        self.assertLess(layout.index("'resources/scss/_app.scss'"),
                        layout.index("@stack('p04-css-pagina')"))
        self.assertIn("@stack('p04-inputmask')", layout)

    def test_auditoria_apenas_leitura(self):
        src = AUDITOR.read_text(encoding='utf-8')
        for unsafe in ('write_text(', 'unlink(', 'rmtree(', 'docker compose', 'git push'):
            self.assertNotIn(unsafe, src)


if __name__ == '__main__':
    unittest.main()
