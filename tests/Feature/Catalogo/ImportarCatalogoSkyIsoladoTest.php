<?php

namespace Tests\Feature\Catalogo;

use App\Models\CatalogoFonte;
use App\Models\CatalogoMontadora;
use App\Models\CatalogoProduto;
use App\Models\CatalogoProdutoAplicacao;
use App\Models\CatalogoProdutoEstado;
use App\Models\CatalogoVeiculo;
use App\Models\Montadora;
use App\Models\Produto;
use App\Models\Veiculo;
use App\Services\Catalogo\SkyCsvImporter;
use App\Services\Produto\ResolverDadosCatalogoProduto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ImportarCatalogoSkyIsoladoTest extends TestCase
{
    use RefreshDatabase;

    private string $diretorio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->diretorio = sys_get_temp_dir().'/sky-test-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($this->diretorio);
        $this->csv('montadoras.csv', ['id', 'sky_id', 'nome'], [
            ['1', 'M-1', 'MONTADORA TESTE'],
        ]);
        $this->csv('veiculos.csv', ['id', 'sky_id', 'montadora_id', 'nome'], [
            ['2', 'V-1', '1', 'VEICULO TESTE'],
        ]);
        $this->csv('produtos.csv', ['id', 'sky_id', 'codigo_fabricante', 'descricao', 'marca'], [
            ['3', 'P-1', 'REF-TESTE', 'PECA TESTE', 'MARCA TESTE'],
        ]);
        $this->csv('produto_aplicacoes.csv', [
            'produto_sky_id', 'montadora_id', 'veiculo_id',
            'montadora', 'veiculo', 'modelo', 'motor',
        ], [
            ['P-1', '1', '2', 'MONTADORA TESTE', 'VEICULO TESTE', '2019', '1.6'],
            ['P-1', '1', '2', 'MONTADORA TESTE', 'VEICULO TESTE', '2020', '1.6'],
        ]);
        foreach ([
            'produto_estado_sky.csv', 'produto_precos_historico.csv',
            'produto_departamentos.csv', 'produto_tipos.csv',
            'produto_atributos.csv', 'produto_imagens.csv',
            'produto_similares.csv',
        ] as $nome) {
            $this->csv($nome, ['produto_sky_id'], []);
        }
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->diretorio);
        parent::tearDown();
    }

    public function test_isolado_importa_reimporta_sem_alterar_operacionais(): void
    {
        $primeiro = app(SkyCsvImporter::class)->importar($this->diretorio, true);
        $this->assertSame(1, $primeiro['produtos']);
        $this->assertSame(2, $primeiro['aplicacoes']);
        $this->assertSame(0, $primeiro['montadoras_operacionais_criadas']);
        $this->assertSame(0, $primeiro['veiculos_operacionais_criados']);

        $segundo = app(SkyCsvImporter::class)->importar($this->diretorio, true);
        $this->assertSame(2, $segundo['aplicacoes']);
        $this->assertSame(1, CatalogoFonte::count());
        $this->assertSame(1, CatalogoMontadora::count());
        $this->assertSame(1, CatalogoVeiculo::count());
        $this->assertSame(1, CatalogoProduto::count());
        $this->assertSame(2, CatalogoProdutoAplicacao::count());

        $this->assertSame(0, Produto::count());
        $this->assertSame(0, Montadora::count());
        $this->assertSame(0, Veiculo::count());
        $this->assertNull(CatalogoProduto::firstOrFail()->produto_id);
        $this->assertNull(CatalogoMontadora::firstOrFail()->montadora_id);
        $this->assertNull(CatalogoVeiculo::firstOrFail()->veiculo_id);
    }

    public function test_comando_aceita_modo_isolado(): void
    {
        $this->artisan('catalogo:importar-sky', [
            'diretorio' => $this->diretorio,
            '--somente-catalogo' => true,
        ])->assertExitCode(0);

        $this->assertSame(1, CatalogoProduto::count());
        $this->assertSame(0, Montadora::count());
        $this->assertSame(0, Veiculo::count());
    }

    public function test_aplicacoes_em_mais_de_um_lote_nao_duplicam(): void
    {
        $linhas = [];
        for ($ano = 2000; $ano < 2045; $ano++) {
            $linhas[] = [
                'P-1', '1', '2', 'MONTADORA TESTE',
                'VEICULO TESTE', (string) $ano, '1.6',
            ];
        }
        $this->csv('produto_aplicacoes.csv', [
            'produto_sky_id', 'montadora_id', 'veiculo_id',
            'montadora', 'veiculo', 'modelo', 'motor',
        ], $linhas);

        app(SkyCsvImporter::class)->importar($this->diretorio, true);
        $this->assertSame(45, CatalogoProdutoAplicacao::count());
        app(SkyCsvImporter::class)->importar($this->diretorio, true);
        $this->assertSame(45, CatalogoProdutoAplicacao::count());
    }

    public function test_isolado_recusa_csv_faltante_antes_de_gravar(): void
    {
        File::delete($this->diretorio.'/produto_aplicacoes.csv');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CSV obrigatório ausente');
        app(SkyCsvImporter::class)->importar($this->diretorio, true);
    }

    public function test_isolado_recusa_aplicacao_orfa_e_desfaz_transacao(): void
    {
        $this->csv('produto_aplicacoes.csv', [
            'produto_sky_id', 'montadora_id', 'veiculo_id',
            'montadora', 'veiculo', 'modelo', 'motor',
        ], [
            ['PRODUTO-INEXISTENTE', '1', '2', 'MONTADORA TESTE', 'VEICULO TESTE', '2019', '1.6'],
        ]);
        try {
            app(SkyCsvImporter::class)->importar($this->diretorio, true);
            $this->fail('Deveria rejeitar aplicação órfã');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Aplicação sem produto importado', $e->getMessage());
        }
        $this->assertSame(0, CatalogoProduto::count());
        $this->assertSame(0, CatalogoProdutoAplicacao::count());
    }

    public function test_preco_sugerido_arredondado_so_aumenta_e_ignora_ausentes(): void
    {
        $this->csv('produto_estado_sky.csv',
            ['produto_sky_id', 'preco_venda_sky_observado'], [['P-1', '12.46']]);
        app(SkyCsvImporter::class)->importar($this->diretorio, true);
        $estado = CatalogoProdutoEstado::firstOrFail();
        $this->assertSame('12.4600', $estado->preco_venda_observado);
        $this->assertSame('15.0000', $estado->preco_venda_sugerido);

        $this->csv('produto_estado_sky.csv',
            ['produto_sky_id', 'preco_venda_sky_observado'], [['P-1', '9.99']]);
        app(SkyCsvImporter::class)->importar($this->diretorio, true);
        $estado->refresh();
        $this->assertSame('9.9900', $estado->preco_venda_observado);
        $this->assertSame('15.0000', $estado->preco_venda_sugerido);

        $this->csv('produto_estado_sky.csv',
            ['produto_sky_id', 'preco_venda_sky_observado'], [['P-1', '21.34']]);
        app(SkyCsvImporter::class)->importar($this->diretorio, true);
        $estado->refresh();
        $this->assertSame('25.0000', $estado->preco_venda_sugerido);

        foreach (['', '0', 'invalido'] as $valor) {
            $this->csv('produto_estado_sky.csv',
                ['produto_sky_id', 'preco_venda_sky_observado'], [['P-1', $valor]]);
            app(SkyCsvImporter::class)->importar($this->diretorio, true);
            $estado->refresh();
            $this->assertSame('21.3400', $estado->preco_venda_observado);
            $this->assertSame('25.0000', $estado->preco_venda_sugerido);
        }
        $this->assertSame(0, Produto::count());

        // O valor mostrado para um cadastro vinculado nunca deve baixar
        // do preco local nem mostrar o bruto sem arredondamento.
        $produto = new Produto();
        $produto->preco_uni = '12.46';
        $produto->setRelation('catalogos', collect([
            CatalogoProduto::firstOrFail()->load('estado', 'imagens', 'atributos', 'aplicacoes', 'fonte'),
        ]));
        $dados = app(ResolverDadosCatalogoProduto::class)->resolver($produto);
        $this->assertSame(12.46, $dados['preco_venda']);
        $this->assertSame('25.00', $dados['preco_sugerido']);

        $produto->preco_uni = '31.99';
        $dados = app(ResolverDadosCatalogoProduto::class)->resolver($produto);
        $this->assertSame(31.99, $dados['preco_venda']);
    }

    private function csv(string $nome, array $cabecalho, array $linhas): void
    {
        $arquivo = fopen($this->diretorio.'/'.$nome, 'wb');
        if ($arquivo === false) {
            throw new \RuntimeException('Não foi possível criar o CSV de teste');
        }
        fputcsv($arquivo, $cabecalho, ';');
        foreach ($linhas as $linha) {
            fputcsv($arquivo, $linha, ';');
        }
        fclose($arquivo);
    }
}
