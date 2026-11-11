<?php

namespace Tests\Feature\Catalogo;

use App\Models\CatalogoFonte;
use App\Models\CatalogoProduto;
use App\Models\CatalogoProdutoEstado;
use App\Models\Marca;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnriquecimentoCatalogoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create([
            'perfil' => User::PERFIL_ADMIN,
            'status' => 1,
        ]));
    }

    public function test_catalogo_exibe_dados_ricos_sem_confundir_preco(): void
    {
        $catalogo = $this->catalogo();

        CatalogoProdutoEstado::create([
            'catalogo_produto_id' => $catalogo->id,
            'preco_venda_sugerido' => '75.00',
        ]);

        $this->get(route('produtos.catalogo.show', $catalogo))
            ->assertOk()
            ->assertSee('Preco sugerido pelo fornecedor')
            ->assertSee('NCM informado')
            ->assertSee('87089990')
            ->assertSee('Unidade do fornecedor')
            ->assertSee('JG')
            ->assertSee('Quantidade por jogo')
            ->assertSee('Quantidade por caixa')
            ->assertSee('Codigo auxiliar')
            ->assertSee('AUX-169');

        $this->assertSame(0, Produto::count());
    }

    public function test_produto_novo_exige_preco_da_oficina(): void
    {
        $catalogo = $this->catalogo();

        CatalogoProdutoEstado::create([
            'catalogo_produto_id' => $catalogo->id,
            'preco_venda_sugerido' => '150.00',
        ]);

        $this->post(route('produtos.catalogo.habilitar', $catalogo))
            ->assertSessionHasErrors('catalogo');

        $this->assertSame(0, Produto::count());
        $this->assertNull($catalogo->fresh()->produto_id);
    }

    public function test_produto_novo_usa_preco_manual_e_dados_validos(): void
    {
        $catalogo = $this->catalogo();

        $catalogo->edicao()->create([
            'preco_venda' => '89.75',
        ]);

        CatalogoProdutoEstado::create([
            'catalogo_produto_id' => $catalogo->id,
            'preco_venda_sugerido' => '150.00',
        ]);

        $this->post(route('produtos.catalogo.habilitar', $catalogo))
            ->assertSessionHasNoErrors();

        $produto = Produto::query()
            ->where('codigo_fabricante', 'FAB-169')
            ->sole();

        $this->assertSame(
            '89.75',
            number_format((float) $produto->preco_uni, 2, '.', '')
        );
        $this->assertSame('0.000', $produto->quantidade);
        $this->assertSame('87089990', $produto->ncm);
        $this->assertSame('JG', $produto->unidade_comercial);
        $this->assertNull($produto->unidade_tributavel);
        $this->assertSame($produto->id, $catalogo->fresh()->produto_id);
    }

    public function test_produto_vinculado_preserva_dados_locais(): void
    {
        $produto = $this->produtoLocal([
            'ncm' => '12345678',
            'unidade_comercial' => 'CX',
            'unidade_tributavel' => 'UN',
        ]);

        $catalogo = $this->catalogo($produto->id);

        $this->post(route('produtos.catalogo.habilitar', $catalogo))
            ->assertSessionHasNoErrors();

        $produto->refresh();

        $this->assertSame(
            '42.90',
            number_format((float) $produto->preco_uni, 2, '.', '')
        );
        $this->assertSame('7.000', $produto->quantidade);
        $this->assertSame('12345678', $produto->ncm);
        $this->assertSame('CX', $produto->unidade_comercial);
        $this->assertSame('UN', $produto->unidade_tributavel);
        $this->assertSame('PRODUTO LOCAL', $produto->nome);
        $this->assertSame('LOCAL-169', $produto->codigo_fabricante);

        $this->get(route('produtos.show', $produto))
            ->assertOk()
            ->assertSee('Informacoes complementares do Forge')
            ->assertSee('87089990')
            ->assertSee('Quantidade por jogo');
    }

    public function test_produto_vinculado_preenche_apenas_campos_vazios(): void
    {
        $produto = $this->produtoLocal();
        $catalogo = $this->catalogo($produto->id);

        $this->post(route('produtos.catalogo.habilitar', $catalogo))
            ->assertSessionHasNoErrors();

        $produto->refresh();

        $this->assertSame('87089990', $produto->ncm);
        $this->assertSame('JG', $produto->unidade_comercial);
        $this->assertNull($produto->unidade_tributavel);
        $this->assertSame('7.000', $produto->quantidade);
        $this->assertSame(
            '42.90',
            number_format((float) $produto->preco_uni, 2, '.', '')
        );
    }

    private function catalogo(?int $produtoId = null): CatalogoProduto
    {
        $fonte = CatalogoFonte::query()->firstOrCreate(
            ['codigo' => 'fornecedor_s'],
            [
                'nome' => 'Fornecedor S',
                'tipo' => 'fornecedor',
                'ativo' => true,
            ]
        );

        return CatalogoProduto::create([
            'catalogo_fonte_id' => $fonte->id,
            'produto_id' => $produtoId,
            'chave_externa' => 'FNS-169',
            'marca_raw' => 'MARCA FORGE 169',
            'codigo_fabricante' => 'FAB-169',
            'codigo_barras' => null,
            'descricao' => 'PECA DE TESTE FORGE',
            'ncm' => '87089990',
            'unidade' => 'JG',
            'quantidade_jogo' => '4',
            'quantidade_caixa' => '24',
            'codigo_auxiliar' => 'AUX-169',
            'origem_raw' => 'FORNECEDOR',
        ]);
    }

    private function produtoLocal(array $campos = []): Produto
    {
        $marca = Marca::create([
            'nome' => 'MARCA LOCAL 169',
            'ativo' => true,
        ]);

        return Produto::create(array_merge([
            'codigo' => 'PRD-LOCAL-169',
            'nome' => 'PRODUTO LOCAL',
            'descricao' => 'Descricao definida pela oficina',
            'marca_id' => $marca->id,
            'codigo_fabricante' => 'LOCAL-169',
            'preco_uni' => '42.90',
            'quantidade' => '7.000',
            'estoque_minimo' => '1.000',
            'status' => true,
            'ncm' => null,
            'unidade_comercial' => null,
            'unidade_tributavel' => null,
        ], $campos));
    }
}
