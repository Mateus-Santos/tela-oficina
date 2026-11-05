"""P04.5: teste do CSS Tailwind e carregamento dedicado de Bootstrap Icons no login."""
import importlib.util
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
AUDIT = ROOT / 'scripts/performance/auditar_login_css.py'
spec = importlib.util.spec_from_file_location('p04_5_login', AUDIT)
mod = importlib.util.module_from_spec(spec)
spec.loader.exec_module(mod)


class LoginCssTest(unittest.TestCase):
    def test_css_tailwind_preservado(self):
        s = (ROOT / 'resources/css/app.css').read_text(encoding='utf-8')
        for item in ('tailwindcss/base', 'tailwindcss/components', 'tailwindcss/utilities', '[x-cloak]'):
            with self.subTest(item=item):
                self.assertIn(item, s)

    def test_bootstrap_icons_nao_global_no_tailwind(self):
        s = (ROOT / 'resources/css/app.css').read_text(encoding='utf-8')
        self.assertNotIn('bootstrap-icons', s)
        self.assertNotIn('.icons_login', s)

    def test_login_carrega_icones_e_css_exclusivos(self):
        s = (ROOT / mod.LOGIN).read_text(encoding='utf-8')
        self.assertEqual(s.count(mod.CDN), 1)
        self.assertEqual(s.count(mod.PUSH), 1)
        self.assertIn("@vite('resources/css/login.css')", s)
        self.assertLess(s.index(mod.PUSH), s.index('<x-guest-layout>'))

    def test_styles_antes_do_fim_do_head(self):
        s = (ROOT / mod.GUEST).read_text(encoding='utf-8')
        self.assertEqual(s.count(mod.STACK), 1)
        self.assertLess(s.index("@vite(['resources/css/app.css', 'resources/js/app.js'])"), s.index(mod.STACK))
        self.assertLess(s.index(mod.STACK), s.index('</head>'))

    def test_estilos_dos_icones_preservados(self):
        s = (ROOT / mod.LOGIN_CSS).read_text(encoding='utf-8')
        for sel in ('bi-google', 'bi-facebook', 'bi-apple'):
            self.assertIn('.icons_login a .' + sel, s)
        self.assertIn('2.2rem', s)

    def test_jetstream_e_layout_principal_preservados(self):
        s = (ROOT / 'resources/views/layouts/app.blade.php').read_text(encoding='utf-8')
        layout = (ROOT / 'resources/views/layouts/layout.blade.php').read_text(encoding='utf-8')
        self.assertIn("@vite(['resources/css/app.css', 'resources/js/app.js'])", s)
        self.assertIn('bootstrap-icons@1.13.1', layout)
        self.assertEqual((ROOT / 'vite.config.js').read_text(encoding='utf-8').count("'resources/css/login.css'"), 1)

    def test_auditoria_segura_sem_alertas(self):
        self.assertEqual(mod.verificar(), [])
        s = AUDIT.read_text(encoding='utf-8')
        for forbidden in ('write_text(', 'unlink(', 'docker compose', 'git push'):
            self.assertNotIn(forbidden, s)


if __name__ == '__main__':
    unittest.main()
