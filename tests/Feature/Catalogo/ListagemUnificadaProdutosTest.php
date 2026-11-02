<?php

namespace Tests\Feature\Catalogo;

use App\Models\CatalogoFonte;
use App\Models\CatalogoProduto;
use App\Models\CatalogoProdutoAplicacao;
use App\Models\CatalogoProdutoEstado;
use App\Models\Marca;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListagemUnificadaProdutosTest extends TestCase
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

    public function test_lista_referencias_sem_duplicar_produtos_operacionais_vinculados(): void
    {
        $produto = $this->produtoOperacional();
        $this->catalogo('SKY-VINCULADO', 'ITEM VINCULADO SKY', $produto->id);
        $externo = $this->catalogo('SKY-SOLTO', 'ITEM EXCLUSIVO CATALOGO');

        $this->get(route('produtos.index'))
            ->assertOk()
            ->assertSee('PRODUTO OPERACIONAL TESTE')
            ->assertSee('ITEM EXCLUSIVO CATALOGO')
            ->assertDontSee('ITEM VINCULADO SKY');

        $this->assertSame(1, Produto::count());
        $this->assertSame(2, CatalogoProduto::count());
        $this->assertSame('4.000', $produto->fresh()->quantidade);
        $this->assertSame('34.90', number_format((float) $produto->fresh()->preco_uni, 2, '.', ''));
        $this->assertNull($externo->fresh()->produto_id);
    }

    public function test_busca_de_catalogo_e_filtro_de_estoque_respeitam_fronteira(): void
    {
        $this->produtoOperacional();
        $this->catalogo('SKY-123', 'SONDA LAMBDA TECNICA');

        $this->get(route('produtos.index', ['nome' => 'SONDA LAMBDA']))
            ->assertOk()->assertSee('SONDA LAMBDA TECNICA')
            ->assertDontSee('PRODUTO OPERACIONAL TESTE');

        $this->get(route('produtos.index', ['estoque' => 'com_estoque']))
            ->assertOk()->assertSee('PRODUTO OPERACIONAL TESTE')
            ->assertDontSee('SONDA LAMBDA TECNICA');
    }

    public function test_detalhes_do_catalogo_mostram_preco_arredondado_sem_acao_de_estoque(): void
    {
        $externo = $this->catalogo('SKY-SOLTO', 'FILTRO CABINE ESPECIAL');
        CatalogoProdutoEstado::query()->create([
            'catalogo_produto_id' => $externo->id,
            'preco_venda_observado' => '12.46',
            'preco_venda_sugerido' => '15.00',
        ]);
        CatalogoProdutoAplicacao::query()->create([
            'catalogo_produto_id' => $externo->id,
            'chave_aplicacao' => hash('sha256', 'teste-aplicacao-2018'),
            'montadora_raw' => 'CHEVROLET',
            'veiculo_raw' => 'ONIX',
            'mes_ano' => '2018',
            'ativa' => true,
        ]);

        $this->get(route('produtos.catalogo.show', $externo))
            ->assertOk()
            ->assertSee('FILTRO CABINE ESPECIAL')
            ->assertSee('15,00')
            ->assertSee('CHEVROLET')
            ->assertSee('ONIX')
            ->assertDontSee('Registrar saída')
            ->assertDontSee('Ajustar estoque');

        $this->assertSame(0, Produto::count());
    }

    public function test_referencia_vinculada_abre_produto_operacional(): void
    {
        $produto = $this->produtoOperacional();
        $externo = $this->catalogo('SKY-VINCULADO', 'REFERENCIA VINCULADA', $produto->id);

        $this->get(route('produtos.catalogo.show', $externo))
            ->assertRedirect(route('produtos.show', $produto));
    }

    private function produtoOperacional(): Produto
    {
        $marca = Marca::query()->create(['nome' => 'MARCA OFICINA', 'ativo' => true]);

        return Produto::query()->create([
            'codigo' => 'PRD-TESTE-001',
            'nome' => 'PRODUTO OPERACIONAL TESTE',
            'descricao' => 'Cadastro manual preservado',
            'codigo_fabricante' => 'MANUAL-001',
            'quantidade' => '4.000',
            'estoque_minimo' => '1.000',
            'preco_uni' => '34.90',
            'status' => true,
            'marca_id' => $marca->id,
        ]);
    }

    private function catalogo(string $chave, string $descricao, ?int $produtoId = null): CatalogoProduto
    {
        $fonte = CatalogoFonte::query()->firstOrCreate(
            ['codigo' => 'sky'],
            ['nome' => 'Sky Pecas', 'tipo' => 'fornecedor', 'ativo' => true]
        );

        return CatalogoProduto::query()->create([
            'catalogo_fonte_id' => $fonte->id,
            'produto_id' => $produtoId,
            'chave_externa' => $chave,
            'descricao' => $descricao,
            'marca_raw' => 'MARCA TECNICA',
            'codigo_fabricante' => $chave,
        ]);
    }
}
