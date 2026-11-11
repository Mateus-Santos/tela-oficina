<?php

namespace Tests\Feature\Catalogo;

use App\Models\CatalogoFonte;
use App\Models\CatalogoProduto;
use App\Models\CatalogoProdutoEstado;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BuscaRapidaCatalogoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['perfil' => User::PERFIL_ADMIN, 'status' => 1]));
    }

    private function referencia(string $chave, string $descricao): CatalogoProduto
    {
        $fonte = CatalogoFonte::query()->firstOrCreate(
            ['codigo' => 'fornecedor_s'],
            ['nome' => 'Fornecedor S', 'tipo' => 'fornecedor', 'ativo' => true]
        );
        $referencia = CatalogoProduto::query()->create([
            'catalogo_fonte_id' => $fonte->id,
            'chave_externa' => $chave,
            'codigo_fabricante' => 'REF-'.$chave,
            'descricao' => $descricao,
            'marca_raw' => 'MARCA TESTE',
        ]);
        CatalogoProdutoEstado::query()->create([
            'catalogo_produto_id' => $referencia->id,
            'preco_venda_observado' => '22.73',
            'preco_venda_sugerido' => '25.00',
        ]);
        return $referencia;
    }

    public function test_referencia_aparece_em_compras_e_notas_sem_virar_produto_operacional(): void
    {
        $ref = $this->referencia('BUSCA1', 'FILTRO TESTE CATALOGO');
        foreach ([
            route('api.produtos.buscar', ['q' => 'FILTRO TESTE CATALOGO']),
            route('api.notas-itens.buscar', ['tipo' => 'produto', 'q' => 'FILTRO TESTE CATALOGO']),
        ] as $url) {
            $this->get($url)->assertOk()
                ->assertJsonPath('data.0.id', null)
                ->assertJsonPath('data.0.catalogo_id', $ref->id)
                ->assertJsonPath('data.0.tipo_resultado', 'referencia_catalogo')
                ->assertJsonPath('data.0.selecionavel_em_nota', false)
                ->assertJsonPath('data.0.selecionavel_em_compra', false)
                ->assertJsonPath('data.0.preco', 25);
        }
        $this->assertSame(0, Produto::query()->count());
        $this->assertSame(0, DB::table('movimentacao_estoques')->count());
    }

    public function test_habilitacao_explicita_retira_referencia_nao_operacional_da_busca(): void
    {
        $ref = $this->referencia('BUSCA2', 'CABO TESTE BUSCA');
        $ref->edicao()->create(['preco_venda' => '25.00']);
        $this->post(route('produtos.catalogo.habilitar', $ref))->assertRedirect();
        $produto = Produto::query()->sole();
        $produto->update(['preco_uni' => '45.90']);
        $ref->estado->update(['preco_venda_sugerido' => '80.00']);

        foreach ([
            route('api.produtos.buscar', ['q' => 'CABO TESTE BUSCA']),
            route('api.notas-itens.buscar', ['tipo' => 'produto', 'q' => 'CABO TESTE BUSCA']),
        ] as $url) {
            $this->get($url)->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $produto->id)
                ->assertJsonPath('data.0.preco', 45.9);
        }
        $this->assertSame(0, DB::table('movimentacao_estoques')->count());
    }

    public function test_busca_de_os_na_nota_nao_recebe_referencias_de_catalogo(): void
    {
        $this->referencia('BUSCA3', 'FILTRO TESTE CATALOGO');
        $this->get(route('api.notas-itens.buscar', ['tipo' => 'os', 'q' => 'FILTRO TESTE CATALOGO']))
            ->assertOk()->assertJsonCount(0, 'data');
    }
}
