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
            'codigo' => 'fornecedor_s', 'nome' => 'Fornecedor S', 'tipo' => 'fornecedor', 'ativo' => true,
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


    public function test_produto_sky_sem_aplicacoes_pode_ser_editado_sem_veiculos(): void
    {
        $referencia = $this->referencia('SKY-SEM-APLICACOES');

        $this->post(route('produtos.catalogo.habilitar', $referencia))
            ->assertRedirect();

        $produto = Produto::query()->sole();

        $this->assertSame(0, $produto->aplicacoes()->count());
        $this->assertSame(0, $produto->veiculos()->count());

        $this->get(route('produtos.show', $produto))
            ->assertOk()
            ->assertSee('Nenhuma aplicação veicular informada pelo Fornecedor S');

        $this->get(route('produtos.edit', $produto))
            ->assertOk()
            ->assertSee('Aplicações manuais opcionais');

        $dados = [
            'codigo' => $produto->codigo,
            'nome' => $produto->nome,
            'marca_id' => $produto->marca_id,
            'descricao' => $produto->descricao,
            'preco_uni' => '20.00',
            'quantidade' => '0.000',
            'estoque_minimo' => '0.000',
            'status' => '1',
            'codigo_fabricante' => $produto->codigo_fabricante,
            'codigo_barras' => $produto->codigo_barras,
        ];

        $this->put(route('produtos.update', $produto), $dados)
            ->assertRedirect(route('produtos.index'));

        $this->assertSame(1, Produto::count());
        $this->assertSame('0.000', $produto->fresh()->quantidade);
        $this->assertSame('20.00', $produto->fresh()->preco_uni);
        $this->assertSame(0, $produto->aplicacoes()->count());

        // Verifica que uma edição sem payload veicular também
        // preserva registros manuais e vínculos legados existentes.
        $montadora = \App\Models\Montadora::create([
            'nome' => 'MONTADORA TESTE SEM APLICACOES SKY',
        ]);

        $manual = \App\Models\Veiculo::create([
            'nome' => 'VEICULO MANUAL PRESERVADO',
            'montadora_id' => $montadora->id,
        ]);

        $legado = \App\Models\Veiculo::create([
            'nome' => 'VEICULO LEGADO PRESERVADO',
            'montadora_id' => $montadora->id,
        ]);

        $produto->aplicacoes()->create([
            'veiculo_id' => $manual->id,
            'chave_aplicacao' => hash('sha256', 'manual-preservado'),
            'origem' => 'manual',
            'status_resolucao' => 'resolvida',
        ]);

        $produto->veiculos()->syncWithoutDetaching([
            $manual->id,
            $legado->id,
        ]);

        $this->put(route('produtos.update', $produto), $dados)
            ->assertRedirect(route('produtos.index'));

        $this->assertDatabaseHas('produto_aplicacoes', [
            'produto_id' => $produto->id,
            'veiculo_id' => $manual->id,
            'origem' => 'manual',
        ]);

        $this->assertDatabaseHas('produtos_veiculos', [
            'produto_id' => $produto->id,
            'veiculo_id' => $legado->id,
        ]);
    }

    public function test_produto_manual_continua_exigindo_veiculos_na_edicao(): void
    {
        $marca = Marca::create([
            'nome' => 'MARCA TESTE MANUAL',
            'ativo' => true,
        ]);

        $produto = Produto::create([
            'nome' => 'PRODUTO MANUAL TESTE',
            'descricao' => 'Cadastro independente do Fornecedor S',
            'marca_id' => $marca->id,
            'codigo_fabricante' => 'REF-MANUAL-SEM-VEICULO',
            'preco_uni' => '25.00',
            'quantidade' => '2.000',
            'estoque_minimo' => '0.000',
            'status' => true,
        ]);

        $this->put(route('produtos.update', $produto), [
            'codigo' => $produto->codigo,
            'nome' => $produto->nome,
            'marca_id' => $marca->id,
            'descricao' => $produto->descricao,
            'preco_uni' => '25.00',
            'quantidade' => '2.000',
            'estoque_minimo' => '0.000',
            'status' => '1',
            'codigo_fabricante' => $produto->codigo_fabricante,
        ])->assertSessionHasErrors('veiculos');

        $this->assertSame('2.000', $produto->fresh()->quantidade);
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
