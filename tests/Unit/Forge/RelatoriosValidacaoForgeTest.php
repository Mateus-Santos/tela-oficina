<?php

namespace Tests\Unit\Forge;

use App\Services\Forge\RelatoriosValidacaoForge;
use PHPUnit\Framework\TestCase;
use RuntimeException;

require_once dirname(__DIR__, 3).'/app/Services/Forge/RelatoriosValidacaoForge.php';

final class RelatoriosValidacaoForgeTest extends TestCase
{
    private string $raiz;
    private string $nome = 'pronto-20261110T120224837022Z';

    protected function setUp(): void
    {
        parent::setUp();
        $this->raiz = sys_get_temp_dir().'/forge-relatorio-'.bin2hex(random_bytes(8));
        mkdir($this->raiz, 0700);
        mkdir($this->raiz.'/'.$this->nome, 0700);
        file_put_contents($this->raiz.'/'.$this->nome.'/manifesto.json', '{}');
    }

    protected function tearDown(): void
    {
        $this->apagar($this->raiz);
        parent::tearDown();
    }

    public function test_registra_aprovacao_em_arquivo_privado_e_le_o_ultimo(): void
    {
        $hash = hash_file('sha256', $this->raiz.'/'.$this->nome.'/manifesto.json');
        $servico = new RelatoriosValidacaoForge();
        $servico->registrar($this->raiz, $this->nome, [
            'manifesto_sha256' => $hash,
            'arquivos' => ['produtos.csv' => ['linhas' => 2, 'bytes' => 20, 'sha256' => str_repeat('a', 64)]],
            'relacoes' => ['produtos_unicos' => 2],
            'alertas' => ['Origem nao assinada'],
        ]);
        $relatorio = $servico->ultimo($this->raiz, $this->nome);
        self::assertSame('aprovado', $relatorio['status']);
        self::assertFalse($relatorio['manifesto_alterado']);
        self::assertSame(2, $relatorio['relacoes']['produtos_unicos']);
        $arquivos = glob($this->raiz.'/.validacoes/'.$this->nome.'/*.json');
        self::assertCount(1, $arquivos);
        self::assertSame(0, fileperms($arquivos[0]) & 0077);
    }

    public function test_detecta_manifesto_substituido_sem_afirmar_validacao_atual(): void
    {
        $servico = new RelatoriosValidacaoForge();
        $servico->registrar($this->raiz, $this->nome, [
            'manifesto_sha256' => hash_file('sha256', $this->raiz.'/'.$this->nome.'/manifesto.json'),
        ]);
        file_put_contents($this->raiz.'/'.$this->nome.'/manifesto.json', '{"alterado":true}');
        self::assertTrue($servico->ultimo($this->raiz, $this->nome)['manifesto_alterado']);
    }

    public function test_nome_malicioso_e_rejeitado(): void
    {
        $this->expectException(RuntimeException::class);
        (new RelatoriosValidacaoForge())->registrar($this->raiz, '../segredo', null);
    }

    public function test_staging_com_permissoes_publicas_e_rejeitado(): void
    {
        chmod($this->raiz, 0755);
        $this->expectException(RuntimeException::class);
        (new RelatoriosValidacaoForge())->registrar($this->raiz, $this->nome, null);
    }

    public function test_sem_relatorio_retorna_nulo(): void
    {
        self::assertNull((new RelatoriosValidacaoForge())->ultimo($this->raiz, $this->nome));
    }


    public function test_rejeita_aprovacao_sem_sha256_valido(): void
    {
        $this->expectException(RuntimeException::class);

        (new RelatoriosValidacaoForge())->registrar(
            $this->raiz,
            $this->nome,
            ['manifesto_sha256' => 'invalido']
        );
    }

    public function test_rejeita_aprovacao_com_manifesto_divergente(): void
    {
        $manifesto = $this->raiz.'/'.$this->nome.'/manifesto.json';
        $hash = hash_file('sha256', $manifesto);

        file_put_contents($manifesto, '{"alterado":true}');

        $this->expectException(RuntimeException::class);

        (new RelatoriosValidacaoForge())->registrar(
            $this->raiz,
            $this->nome,
            ['manifesto_sha256' => $hash]
        );
    }

    public function test_relatorio_recente_corrompido_nao_reutiliza_aprovacao(): void
    {
        $manifesto = $this->raiz.'/'.$this->nome.'/manifesto.json';

        $servico = new RelatoriosValidacaoForge();

        $servico->registrar($this->raiz, $this->nome, [
            'manifesto_sha256' => hash_file('sha256', $manifesto),
        ]);

        self::assertSame(
            'aprovado',
            $servico->ultimo($this->raiz, $this->nome)['status']
        );

        $diretorio = $this->raiz.'/.validacoes/'.$this->nome;
        $arquivo = $diretorio.'/99991231T235959-999999-ffffffffffffffff.json';

        file_put_contents($arquivo, '{"status":');
        chmod($arquivo, 0600);

        self::assertNull(
            $servico->ultimo($this->raiz, $this->nome)
        );
    }

    private function apagar(string $diretorio): void
    {
        if (is_link($diretorio)) {
            unlink($diretorio);
            return;
        }
        if (!is_dir($diretorio)) {
            return;
        }
        @chmod($diretorio, 0700);
        foreach (scandir($diretorio) ?: [] as $nome) {
            if ($nome === '.' || $nome === '..') {
                continue;
            }
            $filho = $diretorio.'/'.$nome;
            if (is_dir($filho) && !is_link($filho)) {
                $this->apagar($filho);
            } else {
                @unlink($filho);
            }
        }
        @rmdir($diretorio);
    }
}
