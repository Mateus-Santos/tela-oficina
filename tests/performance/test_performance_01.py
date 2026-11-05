from pathlib import Path
import re
import unittest

ROOT = Path(__file__).resolve().parents[2]
LAYOUT = ROOT/'resources/views/layouts/layout.blade.php'
NGINX = ROOT/'docker/production/nginx.conf'

class PrimeiraEtapaPerformance(unittest.TestCase):
    def test_waypoints_incluido_uma_vez(self):
        text = LAYOUT.read_text(encoding='utf-8')
        self.assertEqual(text.count("/vendor/waypoints/noframework.waypoints.js"), 1)
        self.assertIn("@yield('content')", text)
        self.assertIn("@yield('scripts')", text)
        self.assertIn('bootstrap-modal-body-portal', text)

    def test_cache_apenas_assets_vite(self):
        text = NGINX.read_text(encoding='utf-8')
        self.assertEqual(text.count('location ^~ /build/assets/'), 1)
        block = text.split('location ^~ /build/assets/ {', 1)[1].split('}', 1)[0]
        self.assertIn('try_files $uri =404;', block)
        self.assertIn('public, max-age=31536000, immutable', block)
        self.assertIn('fastcgi_pass app:9000;', text)
        self.assertNotIn('location ^~ /build/ {', text)

    def test_scripts_inofensivos(self):
        self.assertTrue((ROOT/'scripts/performance/auditar.py').is_file())
        self.assertTrue((ROOT/'scripts/performance/medir_vps.sh').is_file())
        self.assertTrue((ROOT/'.github/workflows/deploy.yml').is_file())

if __name__ == '__main__': unittest.main()
