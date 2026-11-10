<?php

namespace Tests\Unit\Forge;

use App\Services\Forge\ValidarPacoteForge;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ValidarPacoteForgeTest extends TestCase
{
    private string $raiz;
    private string $pacote = 'pronto-20261109T212058381164Z';

    protected function setUp(): void
    {
        parent::setUp();
        $this->raiz = sys_get_temp_dir().'/forge-validacao-'.bin2hex(random_bytes(8));
        mkdir($this->raiz, 0700, true);
        mkdir($this->raiz.'/'.$this->pacote, 0700, true);

        $dados = [
            'montadoras.csv' => "id;sky_id;nome\n1;M-1;MARCA\n2;M-2;OUTRA\n",
            'veiculos.csv' => "id;sky_id;montadora_id;nome\n1;V-1;1;CARRO A\n2;V-1;2;CARRO B\n3;;2;SEM ID\n",
            'produtos.csv' => "sky_id;codigo_fabricante;descricao;marca\nP-1;R-1;PECA;MARCA\nP-2;R-2;OUTRA PECA;MARCA\n",
            'produto_estado_sky.csv' => "produto_sky_id\nP-1\n",
            'produto_precos_historico.csv' => "produto_sky_id\nP-1\n",
            'produto_departamentos.csv' => "produto_sky_id;departamento_sky_id\nP-1;D-1\n",
            'produto_tipos.csv' => "produto_sky_id;tipo_produto_sky_id\nP-1;T-1\n",
            'produto_aplicacoes.csv' => "produto_sky_id;montadora;veiculo;mes_ano;modelo;motor;comp;valvulas;cilindros;combustivel;complemento\nP-1;MARCA;CARRO A;2020;A;1.0;1000;8;4;FLEX;TESTE\n",
            'produto_atributos.csv' => "produto_sky_id;chave\nP-1;MEDIDA\n",
            'produto_imagens.csv' => "produto_sky_id\nP-1\n",
            'produto_similares.csv' => "produto_sky_id;similar_sky_id\nP-1;P-2\n",
        ];
        $manifesto = ['formato' => 'forge-laravel-csv-v1', 'arquivos' => []];
        foreach ($dados as $nome => $texto) {
            $arquivo = $this->raiz.'/'.$this->pacote.'/'.$nome;
            file_put_contents($arquivo, $texto);
            $manifesto['arquivos'][] = [
                'arquivo' => $nome,
                'bytes' => filesize($arquivo),
                'sha256' => hash_file('sha256', $arquivo),
                'consumido_pelo_importador_atual' => true,
            ];
        }
        file_put_contents($this->manifesto(), json_encode($manifesto, JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        $this->apagarDiretorio($this->raiz);
        parent::tearDown();
    }

    public function test_pacote_valido_preserva_id_composto_de_veiculos(): void
    {
        $resultado = (new ValidarPacoteForge())->validar($this->raiz, $this->pacote);
        self::assertSame(2, $resultado['relacoes']['produtos_unicos']);
        self::assertSame(1, $resultado['relacoes']['veiculos_sem_sky_id']);
        self::assertSame(1, $resultado['relacoes']['ids_veiculo_repetidos_entre_montadoras']);
    }

    public function test_csv_adulterado_reprova_sha256(): void
    {
        $arquivo = $this->raiz.'/'.$this->pacote.'/produtos.csv';
        file_put_contents($arquivo, str_replace('PECA', 'PECO', file_get_contents($arquivo)));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SHA-256 divergente');
        (new ValidarPacoteForge())->validar($this->raiz, $this->pacote);
    }

    public function test_nome_de_pacote_malicioso_e_rejeitado(): void
    {
        $this->expectException(RuntimeException::class);
        (new ValidarPacoteForge())->validar($this->raiz, '../fora');
    }

    public function test_aplicacao_com_produto_ausente_e_rejeitada(): void
    {
        $arquivo = $this->raiz.'/'.$this->pacote.'/produto_aplicacoes.csv';
        file_put_contents($arquivo, str_replace('P-1;MARCA', 'P-999;MARCA', file_get_contents($arquivo)));
        $this->atualizarHash('produto_aplicacoes.csv');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Aplicacao referencia produto ausente');
        (new ValidarPacoteForge())->validar($this->raiz, $this->pacote);
    }

    public function test_csv_simbolico_e_rejeitado(): void
    {
        $arquivo = $this->raiz.'/'.$this->pacote.'/produtos.csv';
        $copia = $this->raiz.'/copia.csv';
        copy($arquivo, $copia);
        unlink($arquivo);
        symlink($copia, $arquivo);
        $this->expectException(RuntimeException::class);
        (new ValidarPacoteForge())->validar($this->raiz, $this->pacote);
    }

    private function manifesto(): string
    {
        return $this->raiz.'/'.$this->pacote.'/manifesto.json';
    }

    private function atualizarHash(string $nome): void
    {
        $dados = json_decode(file_get_contents($this->manifesto()), true, 512, JSON_THROW_ON_ERROR);
        foreach ($dados['arquivos'] as &$item) {
            if ($item['arquivo'] === $nome) {
                $arquivo = $this->raiz.'/'.$this->pacote.'/'.$nome;
                $item['bytes'] = filesize($arquivo);
                $item['sha256'] = hash_file('sha256', $arquivo);
            }
        }
        unset($item);
        file_put_contents($this->manifesto(), json_encode($dados, JSON_THROW_ON_ERROR));
    }

    private function apagarDiretorio(string $diretorio): void
    {
        if (!is_dir($diretorio) || is_link($diretorio)) {
            return;
        }
        foreach (scandir($diretorio) ?: [] as $nome) {
            if ($nome === '.' || $nome === '..') {
                continue;
            }
            $caminho = $diretorio.'/'.$nome;
            if (is_dir($caminho) && !is_link($caminho)) {
                $this->apagarDiretorio($caminho);
            } else {
                unlink($caminho);
            }
        }
        rmdir($diretorio);
    }
}
