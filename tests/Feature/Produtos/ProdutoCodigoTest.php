<?php

namespace Tests\Feature\Produtos;

use App\Models\Marca;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdutoCodigoTest extends TestCase
{
    use RefreshDatabase;

    public function test_produto_sem_codigo_recebe_codigo_automatico(): void
    {
        $marca = Marca::query()->create([
            'nome' => 'MARCA TESTE',
            'ativo' => true,
        ]);

        $produto = Produto::query()->create([
            'nome' => 'PRODUTO TESTE',
            'descricao' => 'TESTE',
            'quantidade' => 0,
            'estoque_minimo' => 0,
            'preco_uni' => 0,
            'codigo_fabricante' => 'FAB-001',
            'codigo_barras' => null,
            'status' => true,
            'marca_id' => $marca->id,
        ]);

        $produto->refresh();

        $this->assertSame(
            'PRD-'.str_pad(
                (string) $produto->id,
                8,
                '0',
                STR_PAD_LEFT
            ),
            $produto->codigo
        );
    }

    public function test_codigo_informado_manualmente_e_preservado(): void
    {
        $marca = Marca::query()->create([
            'nome' => 'MARCA TESTE',
            'ativo' => true,
        ]);

        $produto = Produto::query()->create([
            'codigo' => 'MEU-CODIGO-001',
            'nome' => 'PRODUTO TESTE',
            'descricao' => 'TESTE',
            'quantidade' => 0,
            'estoque_minimo' => 0,
            'preco_uni' => 0,
            'codigo_fabricante' => 'FAB-002',
            'codigo_barras' => null,
            'status' => true,
            'marca_id' => $marca->id,
        ]);

        $this->assertSame(
            'MEU-CODIGO-001',
            $produto->fresh()->codigo
        );
    }

    public function test_mesma_referencia_pode_existir_em_marcas_diferentes(): void
    {
        $marcaA = Marca::query()->create([
            'nome' => 'MARCA A',
            'ativo' => true,
        ]);

        $marcaB = Marca::query()->create([
            'nome' => 'MARCA B',
            'ativo' => true,
        ]);

        $produtoA = Produto::query()->create([
            'nome' => 'PRODUTO A',
            'descricao' => 'TESTE',
            'quantidade' => 0,
            'estoque_minimo' => 0,
            'preco_uni' => 0,
            'codigo_fabricante' => '6PK1795',
            'codigo_barras' => null,
            'status' => true,
            'marca_id' => $marcaA->id,
        ]);

        $produtoB = Produto::query()->create([
            'nome' => 'PRODUTO B',
            'descricao' => 'TESTE',
            'quantidade' => 0,
            'estoque_minimo' => 0,
            'preco_uni' => 0,
            'codigo_fabricante' => '6PK1795',
            'codigo_barras' => null,
            'status' => true,
            'marca_id' => $marcaB->id,
        ]);

        $this->assertNotSame(
            $produtoA->codigo,
            $produtoB->codigo
        );

        $this->assertSame(
            '6PK1795',
            $produtoA->codigo_fabricante
        );

        $this->assertSame(
            '6PK1795',
            $produtoB->codigo_fabricante
        );
    }
    public function test_codigo_do_produto_e_unico_globalmente(): void
    {
        $marcaA = Marca::query()->create([
            'nome' => 'MARCA CODIGO A',
            'ativo' => true,
        ]);

        $marcaB = Marca::query()->create([
            'nome' => 'MARCA CODIGO B',
            'ativo' => true,
        ]);

        Produto::query()->create([
            'codigo' => 'COD-UNICO-001',
            'nome' => 'PRODUTO A',
            'descricao' => 'TESTE',
            'quantidade' => 0,
            'estoque_minimo' => 0,
            'preco_uni' => 0,
            'codigo_fabricante' => 'FAB-A',
            'codigo_barras' => null,
            'status' => true,
            'marca_id' => $marcaA->id,
        ]);

        $this->expectException(
            \Illuminate\Database\QueryException::class
        );

        Produto::query()->create([
            'codigo' => 'COD-UNICO-001',
            'nome' => 'PRODUTO B',
            'descricao' => 'TESTE',
            'quantidade' => 0,
            'estoque_minimo' => 0,
            'preco_uni' => 0,
            'codigo_fabricante' => 'FAB-B',
            'codigo_barras' => null,
            'status' => true,
            'marca_id' => $marcaB->id,
        ]);
    }

}
