<?php

namespace Tests\Unit\Forge;

use App\Services\Forge\PrepararStagingForge;
use PHPUnit\Framework\TestCase;
use RuntimeException;

// Teste puramente offline: independente do autoload do outro worktree.
require_once dirname(__DIR__, 3).'/app/Services/Forge/ValidarPacoteForge.php';
require_once dirname(__DIR__, 3).'/app/Services/Forge/PrepararStagingForge.php';

final class PrepararStagingForgeTest extends TestCase
{
    private string $base;
    private string $origem;
    private string $destino;
    private string $nome = 'pronto-20261109T212058381164Z';

    protected function setUp(): void
    {
        parent::setUp();
        $this->base = sys_get_temp_dir().'/forge-staging-test-'.bin2hex(random_bytes(8));
        $this->origem = $this->base.'/transferencias';
        $this->destino = $this->base.'/recepcao/forge-v270';
        mkdir($this->origem.'/'.$this->nome, 0700, true);
        mkdir(dirname($this->destino), 0700, true);
        $this->criarPacote();
    }

    protected function tearDown(): void
    {
        $this->limpar($this->base);
        parent::tearDown();
    }

    public function test_publica_copia_validada_sem_alterar_origem(): void
    {
        $arquivo = $this->origem.'/'.$this->nome.'/produtos.csv';
        $hashAntes = hash_file('sha256', $arquivo);

        $resumo = (new PrepararStagingForge())->preparar(
            $this->origem, $this->nome, $this->destino
        );

        self::assertSame($this->nome, $resumo['pacote']);
        self::assertSame(12, $resumo['arquivos']);
        self::assertSame($hashAntes, hash_file('sha256', $arquivo));
        self::assertSame($hashAntes, hash_file('sha256', $resumo['destino'].'/produtos.csv'));
        self::assertFileExists($resumo['destino'].'/extras/produto_selos.csv');
        self::assertSame(0, fileperms($resumo['destino']) & 0222);
        self::assertSame(0, fileperms($resumo['destino'].'/produtos.csv') & 0222);
        self::assertSame(0, fileperms($resumo['destino'].'/extras') & 0222);
        self::assertSame(0, fileperms($this->destino) & 0077);
        self::assertNotEmpty($resumo['manifesto_sha256']);
        self::assertSame([], glob($this->destino.'/.recebendo-*'));
    }

    public function test_repetir_nao_sobrescreve_arquivo_previamente_publicado(): void
    {
        $preparador = new PrepararStagingForge();
        $primeiro = $preparador->preparar($this->origem, $this->nome, $this->destino);
        $hash = hash_file('sha256', $primeiro['destino'].'/produtos.csv');

        try {
            $preparador->preparar($this->origem, $this->nome, $this->destino);
            self::fail('Deveria rejeitar pacote ja publicado.');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('ja existe', $e->getMessage());
        }

        self::assertSame($hash, hash_file('sha256', $primeiro['destino'].'/produtos.csv'));
        self::assertSame([], glob($this->destino.'/.recebendo-*'));
    }

    public function test_pacote_adulterado_nao_cria_staging(): void
    {
        $produto = $this->origem.'/'.$this->nome.'/produtos.csv';
        file_put_contents(
            $produto,
            str_replace('PECA;M-1', 'PECO;M-1', (string) file_get_contents($produto))
        );
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SHA-256 divergente');
        try {
            (new PrepararStagingForge())->preparar($this->origem, $this->nome, $this->destino);
        } finally {
            self::assertDirectoryDoesNotExist($this->destino);
        }
    }

    public function test_destino_simbolico_e_rejeitado(): void
    {
        symlink($this->origem, $this->destino);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('simbolica');
        (new PrepararStagingForge())->preparar($this->origem, $this->nome, $this->destino);
    }

    public function test_destino_com_permissoes_publicas_e_bloqueado(): void
    {
        mkdir($this->destino, 0755);
        chmod($this->destino, 0755);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('privada');
        (new PrepararStagingForge())->preparar($this->origem, $this->nome, $this->destino);
    }

    private function criarPacote(): void
    {
        $dados = [
            'montadoras.csv' => "id;sky_id;nome\n1;M-1;MONTADORA\n",
            'veiculos.csv' => "id;sky_id;montadora_id;nome\n1;V-1;1;VEICULO\n",
            'produtos.csv' => "sky_id;codigo_fabricante;descricao;marca\nP-1;REF-1;PECA;M-1\nP-2;REF-2;PECA 2;M-2\n",
            'produto_estado_sky.csv' => "produto_sky_id\nP-1\n",
            'produto_precos_historico.csv' => "produto_sky_id\nP-1\n",
            'produto_departamentos.csv' => "produto_sky_id;departamento_sky_id\nP-1;D-1\n",
            'produto_tipos.csv' => "produto_sky_id;tipo_produto_sky_id\nP-1;T-1\n",
            'produto_aplicacoes.csv' => "produto_sky_id;montadora;veiculo;mes_ano;modelo;motor;comp;valvulas;cilindros;combustivel;complemento\nP-1;MONTADORA;VEICULO;2020;X;1.0;1000;8;4;FLEX;NENHUM\n",
            'produto_atributos.csv' => "produto_sky_id;chave\nP-1;DIMENSAO\n",
            'produto_imagens.csv' => "produto_sky_id\nP-1\n",
            'produto_similares.csv' => "produto_sky_id;similar_sky_id\nP-1;P-2\n",
            'extras/produto_selos.csv' => "produto_sky_id;selo\nP-1;OK\n",
        ];
        $manifesto = ['formato' => 'forge-laravel-csv-v1', 'arquivos' => []];
        foreach ($dados as $nome => $texto) {
            $arquivo = $this->origem.'/'.$this->nome.'/'.$nome;
            if (str_contains($nome, '/')) {
                mkdir(dirname($arquivo), 0700, true);
            }
            file_put_contents($arquivo, $texto);
            $manifesto['arquivos'][] = [
                'arquivo' => $nome,
                'bytes' => filesize($arquivo),
                'sha256' => hash_file('sha256', $arquivo),
                'consumido_pelo_importador_atual' => !str_starts_with($nome, 'extras/'),
            ];
        }
        file_put_contents(
            $this->origem.'/'.$this->nome.'/manifesto.json',
            json_encode($manifesto, JSON_THROW_ON_ERROR)
        );
        file_put_contents($this->origem.'/'.$this->nome.'/LEIA-ME.txt', 'Somente teste offline.');
    }

    private function limpar(string $dir): void
    {
        if (is_link($dir)) {
            unlink($dir);
            return;
        }
        if (!is_dir($dir)) {
            return;
        }
        @chmod($dir, 0700);
        foreach (scandir($dir) ?: [] as $nome) {
            if ($nome === '.' || $nome === '..') {
                continue;
            }
            $caminho = $dir.'/'.$nome;
            if (is_dir($caminho) && !is_link($caminho)) {
                $this->limpar($caminho);
            } else {
                @unlink($caminho);
            }
        }
        @rmdir($dir);
    }
}
