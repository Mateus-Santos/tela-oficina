"""Protecoes para a alteracao conservadora de workers PHP-FPM no P02."""
import configparser
import subprocess
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
FPM = ROOT / 'docker/production/www.conf'
COMPOSE = ROOT / 'compose.prod.yml'
PHP = ROOT / 'docker/production/php.ini'


class PerformanceWorkersTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.config = configparser.ConfigParser(interpolation=None)
        with FPM.open(encoding='utf-8') as source:
            cls.config.read_file(source)

    def test_dynamic_com_apenas_um_worker_inicial(self):
        pool = self.config['www']
        self.assertEqual(pool['pm'], 'dynamic')
        self.assertEqual(int(pool['pm.max_children']), 4)
        self.assertEqual(int(pool['pm.start_servers']), 1)
        self.assertEqual(int(pool['pm.min_spare_servers']), 1)
        self.assertEqual(int(pool['pm.max_spare_servers']), 1)
        self.assertEqual(int(pool['pm.max_requests']), 500)
        self.assertNotIn('pm.process_idle_timeout', pool)

    def test_nao_mudar_limites_php_e_healthcheck(self):
        php = PHP.read_text(encoding='utf-8')
        compose = COMPOSE.read_text(encoding='utf-8')
        self.assertIn('memory_limit=256M', php)
        self.assertIn('opcache.memory_consumption=128', php)
        self.assertIn('http://127.0.0.1:8080/up', compose)
        self.assertIn('interval: 30s', compose)

    def test_benchmark_leitura_e_sintaxe(self):
        script = ROOT / 'scripts/performance/benchmark_http.sh'
        self.assertTrue(script.is_file())
        subprocess.run(['bash', '-n', str(script)], check=True)
        s = script.read_text(encoding='utf-8')
        self.assertIn('--resolve', s)
        self.assertIn('--max-time', s)
        self.assertIn('https://', s)
        self.assertNotIn('docker compose up', s)
        self.assertNotIn('migrate:', s)
        self.assertNotIn('curl -X POST', s)

    def test_ci_testes_e_sintaxe_real(self):
        workflow = (ROOT/'.github/workflows/deploy.yml').read_text(encoding='utf-8')
        self.assertIn("'docker/production/www.conf'", workflow)
        self.assertIn('unittest discover', workflow)
        self.assertIn('php:8.5-fpm-alpine', workflow)
        self.assertIn('php-fpm -tt', workflow)


if __name__ == '__main__':
    unittest.main()
