<?php

namespace Tests\Feature\Catalogo;

use App\Models\CatalogoFonte;
use App\Models\CatalogoMontadora;
use App\Models\CatalogoProduto;
use App\Models\CatalogoProdutoAplicacao;
use App\Models\CatalogoVeiculo;
use App\Models\Marca;
use App\Models\Montadora;
use App\Models\Produto;
use App\Models\ProdutoAplicacao;
use App\Models\Veiculo;
use App\Services\Produto\SincronizarAplicacoesCatalogo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SincronizarAplicacoesCatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sincroniza_aplicacoes_sem_criar_cadastros_operacionais(): void
    {
        $fonte =
            CatalogoFonte::query()->create([
                'codigo' => 'sky',
                'nome' => 'Sky Teste',
                'tipo' => 'fornecedor',
                'ativo' => true,
            ]);

        $marca =
            Marca::query()->create([
                'nome' => 'MARCA TESTE',
                'ativo' => true,
            ]);

        $produto =
            Produto::query()->create([
                'nome' => 'PRODUTO TESTE',
                'descricao' => 'TESTE',
                'quantidade' => 0,
                'estoque_minimo' => 0,
                'preco_uni' => 0,
                'codigo_fabricante' =>
                    'REF-TESTE',
                'codigo_barras' =>
                    '7890000000100',
                'status' => true,
                'marca_id' => $marca->id,
            ]);

        $montadora =
            Montadora::query()->create([
                'nome' =>
                    'MONTADORA TESTE',
            ]);

        $veiculoManual =
            Veiculo::query()->create([
                'nome' =>
                    'VEICULO MANUAL',

                'montadora_id' =>
                    $montadora->id,
            ]);

        $veiculoCatalogo =
            Veiculo::query()->create([
                'nome' =>
                    'VEICULO CATALOGO',

                'montadora_id' =>
                    $montadora->id,
            ]);

        $catalogoMontadora =
            CatalogoMontadora::query()->create([
                'catalogo_fonte_id' =>
                    $fonte->id,

                'montadora_id' =>
                    $montadora->id,

                'chave_externa' =>
                    'MONT-001',

                'nome' =>
                    'MONTADORA TESTE',

                'ativo_fonte' =>
                    true,
            ]);

        $catalogoVeiculo =
            CatalogoVeiculo::query()->create([
                'catalogo_fonte_id' =>
                    $fonte->id,

                'catalogo_montadora_id' =>
                    $catalogoMontadora->id,

                'veiculo_id' =>
                    $veiculoCatalogo->id,

                'chave_externa' =>
                    'VEIC-001',

                'nome' =>
                    'VEICULO CATALOGO',

                'ativo_fonte' =>
                    true,
            ]);

        $catalogoProduto =
            CatalogoProduto::query()->create([
                'catalogo_fonte_id' =>
                    $fonte->id,

                'produto_id' =>
                    $produto->id,

                'chave_externa' =>
                    'PROD-001',

                'codigo_fabricante' =>
                    'REF-TESTE',

                'codigo_barras' =>
                    '7890000000100',

                'marca_raw' =>
                    'MARCA TESTE',

                'vinculo_status' =>
                    'automatico',

                'vinculo_confianca' =>
                    100,
            ]);

        $aplicacaoResolvida =
            CatalogoProdutoAplicacao::query()
                ->create([
                    'catalogo_produto_id' =>
                        $catalogoProduto->id,

                    'catalogo_montadora_id' =>
                        $catalogoMontadora->id,

                    'catalogo_veiculo_id' =>
                        $catalogoVeiculo->id,

                    'chave_aplicacao' =>
                        hash(
                            'sha256',
                            'aplicacao-resolvida'
                        ),

                    'montadora_raw' =>
                        'MONTADORA TESTE',

                    'veiculo_raw' =>
                        'VEICULO CATALOGO',

                    'mes_ano' =>
                        '2018 até 2020',

                    'modelo' =>
                        'MODELO TESTE',

                    'motor' =>
                        '1.6',

                    'comp' =>
                        'DOHC',

                    'valvulas' =>
                        '16',

                    'cilindros' =>
                        '4',

                    'combustivel' =>
                        'FLEX',

                    'complemento' =>
                        'APLICACAO RESOLVIDA',

                    'ativa' =>
                        true,
                ]);

        $aplicacaoRevisao =
            CatalogoProdutoAplicacao::query()
                ->create([
                    'catalogo_produto_id' =>
                        $catalogoProduto->id,

                    'catalogo_montadora_id' =>
                        null,

                    'catalogo_veiculo_id' =>
                        null,

                    'chave_aplicacao' =>
                        hash(
                            'sha256',
                            'aplicacao-revisao'
                        ),

                    'montadora_raw' =>
                        'OUTRA MONTADORA',

                    'veiculo_raw' =>
                        'VEICULO NAO RESOLVIDO',

                    'mes_ano' =>
                        '2021 até ...',

                    'motor' =>
                        '2.0',

                    'cilindros' =>
                        '4',

                    'combustivel' =>
                        'FLEX',

                    'complemento' =>
                        'APLICACAO REVISAO',

                    'ativa' =>
                        true,
                ]);

        $catalogoSemProduto =
            CatalogoProduto::query()->create([
                'catalogo_fonte_id' =>
                    $fonte->id,

                'produto_id' =>
                    null,

                'chave_externa' =>
                    'PROD-SEM-OPERACIONAL',

                'codigo_fabricante' =>
                    'REF-SEM-OPERACIONAL',

                'marca_raw' =>
                    'MARCA TESTE',

                'vinculo_status' =>
                    'nao_vinculado',
            ]);

        $aplicacaoSemProduto =
            CatalogoProdutoAplicacao::query()
                ->create([
                    'catalogo_produto_id' =>
                        $catalogoSemProduto->id,

                    'catalogo_montadora_id' =>
                        null,

                    'catalogo_veiculo_id' =>
                        null,

                    'chave_aplicacao' =>
                        hash(
                            'sha256',
                            'aplicacao-sem-produto'
                        ),

                    'montadora_raw' =>
                        'MONTADORA SEM PRODUTO',

                    'veiculo_raw' =>
                        'VEICULO SEM PRODUTO',

                    'ativa' =>
                        true,
                ]);

        $produto
            ->aplicacoes()
            ->create([
                'veiculo_id' =>
                    $veiculoManual->id,

                'chave_aplicacao' =>
                    hash(
                        'sha256',
                        'aplicacao-manual'
                    ),

                'origem' =>
                    ProdutoAplicacao::
                        ORIGEM_MANUAL,

                'status_resolucao' =>
                    ProdutoAplicacao::
                        STATUS_RESOLVIDA,
            ]);

        $antes = [
            'produtos' =>
                Produto::query()->count(),

            'montadoras' =>
                Montadora::query()->count(),

            'veiculos' =>
                Veiculo::query()->count(),
        ];

        $servico =
            app(
                SincronizarAplicacoesCatalogo::class
            );

        $diagnostico =
            $servico->diagnosticar(
                'sky'
            );

        $this->assertSame(
            3,
            $diagnostico[
                'total_catalogo'
            ]
        );

        $this->assertSame(
            2,
            $diagnostico[
                'com_produto_operacional'
            ]
        );

        $this->assertSame(
            1,
            $diagnostico[
                'sem_produto_operacional'
            ]
        );

        $this->assertSame(
            1,
            $diagnostico[
                'resolvidas'
            ]
        );

        $this->assertSame(
            1,
            $diagnostico[
                'revisao'
            ]
        );

        $this->assertSame(
            0,
            $diagnostico[
                'colisoes'
            ]
        );

        $resultado =
            $servico->sincronizar(
                'sky',
                1
            );

        $this->assertSame(
            2,
            $resultado[
                'sincronizadas'
            ]
        );

        $this->assertSame(
            1,
            $resultado[
                'resolvidas'
            ]
        );

        $this->assertSame(
            1,
            $resultado[
                'revisao'
            ]
        );

        $this->assertSame(
            1,
            $resultado[
                'produtos_reconstruidos'
            ]
        );

        $this->assertSame(
            2,
            $resultado[
                'materializadas_apos'
            ]
        );

        $this->assertDatabaseHas(
            'produto_aplicacoes',
            [
                'produto_id' =>
                    $produto->id,

                'catalogo_produto_aplicacao_id' =>
                    $aplicacaoResolvida->id,

                'veiculo_id' =>
                    $veiculoCatalogo->id,

                'origem' =>
                    'sky',

                'status_resolucao' =>
                    ProdutoAplicacao::
                        STATUS_RESOLVIDA,

                'motor' =>
                    '1.6',

                'cilindros' =>
                    '4',
            ]
        );

        $this->assertDatabaseHas(
            'produto_aplicacoes',
            [
                'produto_id' =>
                    $produto->id,

                'catalogo_produto_aplicacao_id' =>
                    $aplicacaoRevisao->id,

                'veiculo_id' =>
                    null,

                'origem' =>
                    'sky',

                'status_resolucao' =>
                    ProdutoAplicacao::
                        STATUS_REVISAO,
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
                    $veiculoCatalogo->id,
            ]
        );

        $this->assertSame(
            2,
            DB::table(
                'produtos_veiculos'
            )
                ->where(
                    'produto_id',
                    $produto->id
                )
                ->count()
        );

        $this->assertDatabaseMissing(
            'produto_aplicacoes',
            [
                'catalogo_produto_aplicacao_id' =>
                    $aplicacaoSemProduto->id,
            ]
        );

        $depois = [
            'produtos' =>
                Produto::query()->count(),

            'montadoras' =>
                Montadora::query()->count(),

            'veiculos' =>
                Veiculo::query()->count(),
        ];

        $this->assertSame(
            $antes,
            $depois
        );

        $servico->sincronizar(
            'sky',
            1
        );

        $this->assertSame(
            2,
            ProdutoAplicacao::query()
                ->whereNotNull(
                    'catalogo_produto_aplicacao_id'
                )
                ->count()
        );

        $this->assertSame(
            3,
            ProdutoAplicacao::query()
                ->where(
                    'produto_id',
                    $produto->id
                )
                ->count()
        );
    }
}
