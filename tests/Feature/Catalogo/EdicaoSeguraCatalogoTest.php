<?php

namespace Tests\Feature\Catalogo;

use App\Models\CatalogoFonte;
use App\Models\CatalogoProduto;
use App\Models\CatalogoProdutoEstado;
use App\Models\Marca;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EdicaoSeguraCatalogoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['perfil' => User::PERFIL_ADMIN, 'status' => 1]));
    }

    private function referencia(string $chave = 'SKY-TESTE'): CatalogoProduto
    {
        $fonte = CatalogoFonte::query()->create([
            'codigo' => 'sky', 'nome' => 'Sky', 'tipo' => 'fornecedor', 'ativo' => true,
        ]);
        $ref = CatalogoProduto::query()->create([
            'catalogo_fonte_id' => $fonte->id,
            'chave_externa' => $chave,
            'codigo_fabricante' => 'FAB-TESTE',
            'marca_raw' => 'MARCA TESTE',
            'descricao' => 'PECA COLETADA',
        ]);
        CatalogoProdutoEstado::query()->create([
            'catalogo_produto_id' => $ref->id,
            'preco_venda_observado' => '17.38',
            'preco_venda_sugerido' => '20.00',
        ]);
        return $ref;
    }

    public function test_edicao_personaliza_sem_alterar_fonte_nem_estoque(): void
    {
        $ref = $this->referencia();
        $this->put(route('produtos.catalogo.update', $ref), [
            'nome' => 'DESCRICAO MANUAL',
            'marca' => 'OUTRA MARCA',
            'preco_venda' => '32.50',
        ])->assertRedirect(route('produtos.catalogo.show', $ref));
        $this->assertDatabaseHas('catalogo_produto_edicoes', [
            'catalogo_produto_id' => $ref->id,
            'nome' => 'DESCRICAO MANUAL',
        ]);
        $this->assertSame('PECA COLETADA', $ref->fresh()->descricao);
        $this->get(route('produtos.index'))->assertOk()->assertSee('DESCRICAO MANUAL')
            ->assertSee('32,50');
        $this->assertSame(0, Produto::query()->count());
        $this->assertSame(0, DB::table('movimentacao_estoques')->count());
    }

    public function test_habilitar_cria_operacional_com_estoque_zero_e_codigo_automatico(): void
    {
        $ref = $this->referencia();
        $this->post(route('produtos.catalogo.habilitar', $ref))
            ->assertRedirect();
        $produto = Produto::query()->sole();
        $this->assertSame('0.000', $produto->quantidade);
        $this->assertSame(20.0, (float) $produto->preco_uni);
        $this->assertStringStartsWith('PRD-', $produto->codigo);
        $this->assertSame($produto->id, $ref->fresh()->produto_id);
        $this->assertSame(0, DB::table('movimentacao_estoques')->count());
        $this->post(route('produtos.catalogo.habilitar', $ref))->assertRedirect();
        $this->assertSame(1, Produto::query()->count());
    }

    public function test_nunca_cria_produto_automaticamente_em_consulta(): void
    {
        $ref = $this->referencia();
        $this->get(route('api.produtos.catalogo.buscar', ['q' => 'PECA']))
            ->assertOk()->assertJsonPath('data.0.selecionavel_em_nota', false)
            ->assertJsonPath('data.0.produto_id', null);
        $this->assertNull($ref->fresh()->produto_id);
        $this->assertSame(0, Produto::query()->count());
    }

    public function test_sugestao_mais_alta_nao_substitui_preco_operacional_da_oficina(): void
    {
        $ref = $this->referencia();
        $this->post(route('produtos.catalogo.habilitar', $ref))->assertRedirect();
        $produto = Produto::query()->sole();
        $produto->update(['preco_uni' => '49.90']);
        $ref->estado->update(['preco_venda_sugerido' => '80.00']);
        $this->assertSame('49.90', number_format(
            (float) app(\App\Services\Produto\ResolverDadosCatalogoProduto::class)
                ->resolver($produto->fresh(['catalogos.estado', 'catalogos.imagens', 'catalogos.atributos', 'catalogos.fonte']))['preco_venda'],
            2, '.', ''
        ));
        $this->get(route('api.produtos.buscar', ['q' => 'PECA']))
            ->assertOk()->assertJsonPath('data.0.preco', 49.9);
        $this->get(route('api.notas-itens.buscar', ['tipo' => 'produto', 'q' => 'PECA']))
            ->assertOk()->assertJsonPath('data.0.preco', 49.9);
    }

    public function test_similares_exibe_referencias_sem_homologar_compatibilidade(): void
    {
        $ref = $this->referencia();
        $outra = CatalogoProduto::query()->create([
            'catalogo_fonte_id' => $ref->catalogo_fonte_id,
            'chave_externa' => 'SKY-2', 'descricao' => 'PECA SIMILAR',
            'marca_raw' => 'MARCA B',
        ]);
        DB::table('catalogo_produto_similares')->insert([
            'catalogo_produto_id' => $ref->id,
            'similar_catalogo_produto_id' => $outra->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->get(route('produtos.catalogo.similares', $ref))
            ->assertOk()->assertSee('PECA SIMILAR')
            ->assertSee('não garante compatibilidade');
    }
}
