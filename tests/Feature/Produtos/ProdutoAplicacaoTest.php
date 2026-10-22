<?php

namespace Tests\Feature\Produtos;

use App\Actions\Produto\CriarProduto;
use App\Models\Marca;
use App\Models\Montadora;
use App\Models\Produto;
use App\Models\ProdutoAplicacao;
use App\Models\Veiculo;
use App\Services\Produto\GerarChaveAplicacao;
use App\Services\Produto\PersistirAplicacoesProduto;
use App\Services\Produto\ResolverAplicacoes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdutoAplicacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_aplicacoes_manuais_nao_removem_aplicacoes_de_outras_origens(): void
    {
        $marca = Marca::query()->create([
            'nome' => 'MARCA TESTE',
            'ativo' => true,
        ]);

        $montadora =
            Montadora::query()->create([
                'nome' => 'MONTADORA TESTE',
            ]);

        $veiculoManual =
            Veiculo::query()->create([
                'nome' => 'VEICULO MANUAL',
                'montadora_id' =>
                    $montadora->id,
            ]);

        $veiculoSky =
            Veiculo::query()->create([
                'nome' => 'VEICULO SKY',
                'montadora_id' =>
                    $montadora->id,
            ]);

        $produto =
            Produto::query()->create([
                'nome' => 'PRODUTO TESTE',
                'descricao' => 'TESTE',
                'quantidade' => 0,
                'estoque_minimo' => 0,
                'preco_uni' => 0,
                'codigo_fabricante' =>
                    'FAB-APLICACAO',
                'codigo_barras' => null,
                'status' => true,
                'marca_id' => $marca->id,
            ]);

        $produto
            ->aplicacoes()
            ->create([
                'veiculo_id' =>
                    $veiculoSky->id,

                'chave_aplicacao' =>
                    hash(
                        'sha256',
                        'sky-aplicacao'
                    ),

                'origem' => 'sky',

                'status_resolucao' =>
                    ProdutoAplicacao::
                        STATUS_RESOLVIDA,
            ]);

        app(
            PersistirAplicacoesProduto::class
        )->sincronizarManuais(
            $produto,
            [
                [
                    'veiculo_id' =>
                        $veiculoManual->id,

                    'mes_ano' =>
                        '2020 até ...',

                    'motor' => '1.6',

                    'valvulas' => '16',

                    'cilindros' => '4',

                    'combustivel' =>
                        'FLEX',
                ],
            ]
        );

        $this->assertDatabaseHas(
            'produto_aplicacoes',
            [
                'produto_id' =>
                    $produto->id,

                'veiculo_id' =>
                    $veiculoManual->id,

                'origem' =>
                    ProdutoAplicacao::
                        ORIGEM_MANUAL,
            ]
        );

        $this->assertDatabaseHas(
            'produto_aplicacoes',
            [
                'produto_id' =>
                    $produto->id,

                'veiculo_id' =>
                    $veiculoSky->id,

                'origem' => 'sky',
            ]
        );

        $this->assertDatabaseHas(
            'produtos_veiculos',
            [
                'produto_id' =>
                    $produto->id,

                'veiculo_id' =>
                    $veiculoManual->id,
            ]
        );

        $this->assertDatabaseHas(
            'produtos_veiculos',
            [
                'produto_id' =>
                    $produto->id,

                'veiculo_id' =>
                    $veiculoSky->id,
            ]
        );

        app(
            PersistirAplicacoesProduto::class
        )->sincronizarManuais(
            $produto,
            []
        );

        $this->assertDatabaseMissing(
            'produto_aplicacoes',
            [
                'produto_id' =>
                    $produto->id,

                'origem' =>
                    ProdutoAplicacao::
                        ORIGEM_MANUAL,
            ]
        );

        $this->assertDatabaseHas(
            'produto_aplicacoes',
            [
                'produto_id' =>
                    $produto->id,

                'veiculo_id' =>
                    $veiculoSky->id,

                'origem' => 'sky',
            ]
        );

        $this->assertDatabaseMissing(
            'produtos_veiculos',
            [
                'produto_id' =>
                    $produto->id,

                'veiculo_id' =>
                    $veiculoManual->id,
            ]
        );

        $this->assertDatabaseHas(
            'produtos_veiculos',
            [
                'produto_id' =>
                    $produto->id,

                'veiculo_id' =>
                    $veiculoSky->id,
            ]
        );
    }

    public function test_chave_manual_e_estavel_com_normalizacao(): void
    {
        $gerador =
            app(
                GerarChaveAplicacao::class
            );

        $a = $gerador->gerar(
            10,
            [
                'motor' => ' 1.6 ',
                'comp' => ' dohc ',
                'combustivel' =>
                    'Flex',
            ]
        );

        $b = $gerador->gerar(
            10,
            [
                'motor' => '1.6',
                'comp' => 'DOHC',
                'combustivel' =>
                    'FLEX',
            ]
        );

        $this->assertSame(
            $a,
            $b
        );

        $this->assertNotSame(
            $a,
            $gerador->gerar(
                11,
                [
                    'motor' => '1.6',
                    'comp' => 'DOHC',
                    'combustivel' =>
                        'FLEX',
                ]
            )
        );
    }

    public function test_criar_produto_persiste_aplicacao_rica_e_projeca_veiculo(): void
    {
        $marca = Marca::query()->create([
            'nome' => 'MARCA APLICACAO RICA',
            'ativo' => true,
        ]);

        $montadora =
            Montadora::query()->create([
                'nome' => 'MONTADORA APLICACAO RICA',
            ]);

        $veiculo =
            Veiculo::query()->create([
                'nome' => 'VEICULO APLICACAO RICA',
                'montadora_id' =>
                    $montadora->id,
            ]);

        $produto =
            app(
                CriarProduto::class
            )->execute([
                'codigo' =>
                    'PRD-TESTE-APLICACAO-RICA',

                'nome' =>
                    'PRODUTO APLICACAO RICA',

                'marca_id' =>
                    $marca->id,

                'descricao' =>
                    'PRODUTO DE TESTE',

                'preco_uni' =>
                    10,

                'quantidade' =>
                    0,

                'estoque_minimo' =>
                    0,

                'status' =>
                    true,

                'codigo_fabricante' =>
                    'FAB-RICO',

                'codigo_barras' =>
                    null,

                'veiculos' => [
                    $veiculo->id,
                ],

                'aplicacoes' => [
                    [
                        'veiculo_id' =>
                            $veiculo->id,

                        'mes_ano' =>
                            '2018 até 2020',

                        'modelo' =>
                            'ESSENCE',

                        'motor' =>
                            '1.6',

                        'comp' =>
                            'E.TORQ',

                        'valvulas' =>
                            '16',

                        'cilindros' =>
                            '4',

                        'combustivel' =>
                            'FLEX',

                        'complemento' =>
                            'APLICACAO TESTE',
                    ],
                ],
            ]);

        $this->assertDatabaseHas(
            'produto_aplicacoes',
            [
                'produto_id' =>
                    $produto->id,

                'veiculo_id' =>
                    $veiculo->id,

                'mes_ano' =>
                    '2018 até 2020',

                'modelo' =>
                    'ESSENCE',

                'motor' =>
                    '1.6',

                'comp' =>
                    'E.TORQ',

                'valvulas' =>
                    '16',

                'cilindros' =>
                    '4',

                'combustivel' =>
                    'FLEX',

                'complemento' =>
                    'APLICACAO TESTE',

                'origem' =>
                    ProdutoAplicacao::
                        ORIGEM_MANUAL,

                'status_resolucao' =>
                    ProdutoAplicacao::
                        STATUS_RESOLVIDA,
            ]
        );

        $this->assertDatabaseHas(
            'produtos_veiculos',
            [
                'produto_id' =>
                    $produto->id,

                'veiculo_id' =>
                    $veiculo->id,
            ]
        );
    }

    public function test_criar_produto_mantem_compatibilidade_com_veiculos_legados(): void
    {
        $marca = Marca::query()->create([
            'nome' => 'MARCA FLUXO LEGADO',
            'ativo' => true,
        ]);

        $montadora =
            Montadora::query()->create([
                'nome' => 'MONTADORA FLUXO LEGADO',
            ]);

        $veiculo =
            Veiculo::query()->create([
                'nome' => 'VEICULO FLUXO LEGADO',
                'montadora_id' =>
                    $montadora->id,
            ]);

        $produto =
            app(
                CriarProduto::class
            )->execute([
                'codigo' =>
                    'PRD-TESTE-LEGADO',

                'nome' =>
                    'PRODUTO FLUXO LEGADO',

                'marca_id' =>
                    $marca->id,

                'descricao' =>
                    'PRODUTO DE TESTE',

                'preco_uni' =>
                    10,

                'quantidade' =>
                    0,

                'estoque_minimo' =>
                    0,

                'status' =>
                    true,

                'codigo_fabricante' =>
                    'FAB-LEGADO',

                'codigo_barras' =>
                    null,

                'veiculos' => [
                    $veiculo->id,
                ],
            ]);

        $this->assertDatabaseHas(
            'produto_aplicacoes',
            [
                'produto_id' =>
                    $produto->id,

                'veiculo_id' =>
                    $veiculo->id,

                'mes_ano' =>
                    null,

                'modelo' =>
                    null,

                'motor' =>
                    null,

                'comp' =>
                    null,

                'valvulas' =>
                    null,

                'cilindros' =>
                    null,

                'combustivel' =>
                    null,

                'complemento' =>
                    null,

                'origem' =>
                    ProdutoAplicacao::
                        ORIGEM_MANUAL,
            ]
        );

        $this->assertDatabaseHas(
            'produtos_veiculos',
            [
                'produto_id' =>
                    $produto->id,

                'veiculo_id' =>
                    $veiculo->id,
            ]
        );
    }


    public function test_resolver_aplicacao_reutiliza_montadora_e_veiculo_existentes(): void
    {
        $montadora =
            Montadora::query()->create([
                'nome' => 'PEUGEOT',
            ]);

        $veiculo =
            Veiculo::query()->create([
                'nome' => '106',
                'montadora_id' =>
                    $montadora->id,
            ]);

        $resolver =
            app(
                ResolverAplicacoes::class
            );

        $resolvido =
            $resolver
                ->resolverOuCriarVeiculo([
                    'montadora' =>
                        '  peugeot  ',

                    'veiculo' =>
                        '  106  ',
                ]);

        $this->assertSame(
            $veiculo->id,
            $resolvido->id
        );

        $this->assertSame(
            1,
            Montadora::query()
                ->whereRaw(
                    'LOWER(TRIM(nome)) = ?',
                    ['peugeot']
                )
                ->count()
        );

        $this->assertSame(
            1,
            Veiculo::query()
                ->where(
                    'montadora_id',
                    $montadora->id
                )
                ->whereRaw(
                    'LOWER(TRIM(nome)) = ?',
                    ['106']
                )
                ->count()
        );
    }

}
