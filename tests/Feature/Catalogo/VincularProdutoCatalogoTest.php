<?php

namespace Tests\Feature\Catalogo;

use App\Models\CatalogoFonte;
use App\Models\CatalogoProduto;
use App\Models\Marca;
use App\Models\Produto;
use App\Services\Catalogo\VincularProdutoCatalogo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VincularProdutoCatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_triangulacao_completa_vincula_com_confianca_maxima(): void
    {
        $produto = $this->criarProduto(
            'NGK',
            'BKR6ED',
            '7897707510429'
        );

        $decisao = app(
            VincularProdutoCatalogo::class
        )->avaliar(
            $this->catalogoEmMemoria(
                '7897707510429',
                'NGK',
                'BKR6ED'
            )
        );

        $this->assertSame(
            'automatico',
            $decisao['status']
        );

        $this->assertSame(
            $produto->id,
            $decisao['produto_id']
        );

        $this->assertSame(
            100.0,
            $decisao['confianca']
        );
    }

    public function test_somente_ean_pode_vincular_sem_contradicao(): void
    {
        $produto = $this->criarProduto(
            'NGK',
            'BKR6ED',
            '7897707510429'
        );

        $decisao = app(
            VincularProdutoCatalogo::class
        )->avaliar(
            $this->catalogoEmMemoria(
                '7897707510429',
                null,
                null
            )
        );

        $this->assertSame(
            'automatico',
            $decisao['status']
        );

        $this->assertSame(
            $produto->id,
            $decisao['produto_id']
        );

        $this->assertSame(
            100.0,
            $decisao['confianca']
        );
    }

    public function test_somente_marca_e_referencia_podem_vincular(): void
    {
        $produto = $this->criarProduto(
            'NGK',
            'BKR6ED',
            '7897707510429'
        );

        $decisao = app(
            VincularProdutoCatalogo::class
        )->avaliar(
            $this->catalogoEmMemoria(
                null,
                ' ngk ',
                ' bkr6ed '
            )
        );

        $this->assertSame(
            'automatico',
            $decisao['status']
        );

        $this->assertSame(
            $produto->id,
            $decisao['produto_id']
        );

        $this->assertSame(
            95.0,
            $decisao['confianca']
        );
    }

    public function test_conflito_entre_ean_e_marca_referencia_vai_para_revisao(): void
    {
        $produtoA = $this->criarProduto(
            'MARCA A',
            'REF-A',
            '7890000000001'
        );

        $produtoB = $this->criarProduto(
            'MARCA B',
            'REF-B',
            '7890000000002'
        );

        $decisao = app(
            VincularProdutoCatalogo::class
        )->avaliar(
            $this->catalogoEmMemoria(
                $produtoA->codigo_barras,
                $produtoB->marcaRelacionada->nome,
                $produtoB->codigo_fabricante
            )
        );

        $this->assertSame(
            'revisao',
            $decisao['status']
        );

        $this->assertNull(
            $decisao['produto_id']
        );

        $this->assertNull(
            $decisao['confianca']
        );
    }

    public function test_ean_com_dados_contraditorios_vai_para_revisao(): void
    {
        $produto = $this->criarProduto(
            'MARCA CORRETA',
            'REF-CORRETA',
            '7890000000010'
        );

        $decisao = app(
            VincularProdutoCatalogo::class
        )->avaliar(
            $this->catalogoEmMemoria(
                $produto->codigo_barras,
                'MARCA DIVERGENTE',
                'REF-DIVERGENTE'
            )
        );

        $this->assertSame(
            'revisao',
            $decisao['status']
        );

        $this->assertNull(
            $decisao['produto_id']
        );
    }

    public function test_sem_candidato_permanece_nao_vinculado(): void
    {
        $decisao = app(
            VincularProdutoCatalogo::class
        )->avaliar(
            $this->catalogoEmMemoria(
                '9999999999999',
                'MARCA INEXISTENTE',
                'REF-INEXISTENTE'
            )
        );

        $this->assertSame(
            'nao_vinculado',
            $decisao['status']
        );

        $this->assertNull(
            $decisao['produto_id']
        );
    }

    public function test_tentar_nao_cria_produto_nem_marca_quando_nao_encontra_candidato(): void
    {
        $fonte = CatalogoFonte::query()->create([
            'codigo' => 'teste',
            'nome' => 'Fonte Teste',
            'tipo' => 'fornecedor',
            'ativo' => true,
        ]);

        $catalogoProduto =
            CatalogoProduto::query()->create([
                'catalogo_fonte_id' =>
                    $fonte->id,

                'chave_externa' =>
                    'CAT-SEM-CANDIDATO',

                'codigo_fabricante' =>
                    'REF-SEM-CANDIDATO',

                'codigo_barras' =>
                    '9999999999998',

                'marca_raw' =>
                    'MARCA SEM CANDIDATO',
            ]);

        $produtosAntes =
            Produto::query()->count();

        $marcasAntes =
            Marca::query()->count();

        $decisao = app(
            VincularProdutoCatalogo::class
        )->tentar(
            $catalogoProduto
        );

        $this->assertSame(
            'nao_vinculado',
            $decisao['status']
        );

        $this->assertSame(
            $produtosAntes,
            Produto::query()->count()
        );

        $this->assertSame(
            $marcasAntes,
            Marca::query()->count()
        );

        $catalogoProduto->refresh();

        $this->assertNull(
            $catalogoProduto->produto_id
        );

        $this->assertSame(
            'nao_vinculado',
            $catalogoProduto->vinculo_status
        );
    }

    public function test_vinculo_existente_e_preservado_sem_reavaliacao_automatica(): void
    {
        $produto = $this->criarProduto(
            'MARCA EXISTENTE',
            'REF-EXISTENTE',
            '7890000000020'
        );

        $catalogoProduto =
            new CatalogoProduto([
                'produto_id' =>
                    $produto->id,

                'codigo_barras' =>
                    'EAN-DIVERGENTE',

                'marca_raw' =>
                    'MARCA DIVERGENTE',

                'codigo_fabricante' =>
                    'REF-DIVERGENTE',

                'vinculo_status' =>
                    'automatico',

                'vinculo_confianca' =>
                    100,

                'vinculo_motivo' =>
                    'Vínculo legado',
            ]);

        $decisao = app(
            VincularProdutoCatalogo::class
        )->avaliar(
            $catalogoProduto
        );

        $this->assertSame(
            'ja_vinculado',
            $decisao['status']
        );

        $this->assertSame(
            $produto->id,
            $decisao['produto_id']
        );
    }

    private function criarProduto(
        string $marcaNome,
        string $codigoFabricante,
        ?string $ean
    ): Produto {
        $marca =
            Marca::query()->create([
                'nome' =>
                    $marcaNome,

                'ativo' =>
                    true,
            ]);

        return Produto::query()->create([
            'nome' =>
                'PRODUTO '.$codigoFabricante,

            'descricao' =>
                'PRODUTO DE TESTE',

            'quantidade' =>
                0,

            'estoque_minimo' =>
                0,

            'preco_uni' =>
                0,

            'codigo_fabricante' =>
                $codigoFabricante,

            'codigo_barras' =>
                $ean,

            'status' =>
                true,

            'marca_id' =>
                $marca->id,
        ]);
    }

    private function catalogoEmMemoria(
        ?string $ean,
        ?string $marca,
        ?string $codigoFabricante
    ): CatalogoProduto {
        return new CatalogoProduto([
            'produto_id' =>
                null,

            'codigo_barras' =>
                $ean,

            'marca_raw' =>
                $marca,

            'codigo_fabricante' =>
                $codigoFabricante,
        ]);
    }
}
