<?php

namespace Tests\Feature\Catalogo;

use App\Models\CatalogoFonte;
use App\Models\CatalogoMontadora;
use App\Models\CatalogoProduto;
use App\Models\CatalogoProdutoAplicacao;
use App\Models\CatalogoProdutoEstado;
use App\Models\CatalogoVeiculo;
use App\Models\Marca;
use App\Models\Montadora;
use App\Models\Produto;
use App\Models\ProdutoAplicacao;
use App\Models\User;
use App\Models\Veiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HabilitacaoInteligenteCatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_reaproveita_produto_e_enriquece_veiculos_sem_alterar_preco_estoque(): void
    {
        $this->actingAs(User::factory()->create([
            'perfil' => User::PERFIL_ADMIN,
            'status' => 1,
        ]));

        $fonte = CatalogoFonte::create([
            'codigo' => 'sky',
            'nome' => 'Sky',
            'tipo' => 'fornecedor',
            'ativo' => true,
        ]);

        $marca = Marca::create([
            'nome' => 'GATES',
            'ativo' => true,
        ]);

        $produto = Produto::create([
            'nome' => 'CORREIA GATES',
            'descricao' => 'Cadastro manual',
            'marca_id' => $marca->id,
            'codigo_fabricante' => '6PK1200SF',
            'codigo_barras' => '7894758161075',
            'preco_uni' => '75.00',
            'quantidade' => '5.000',
            'estoque_minimo' => '2.000',
            'status' => false,
        ]);

        $ref = CatalogoProduto::create([
            'catalogo_fonte_id' => $fonte->id,
            'chave_externa' => 'SKY-GATES-1',
            'marca_raw' => 'GATES',
            'codigo_fabricante' => '6PK1200SF',
            'codigo_barras' => '789475816107',
            'descricao' => 'CORREIA SKY',
        ]);

        CatalogoProdutoEstado::create([
            'catalogo_produto_id' => $ref->id,
            'preco_venda_observado' => '150.00',
            'preco_venda_sugerido' => '160.00',
            'estoque_observado' => 25,
        ]);

        $montadora = Montadora::create(['nome' => 'FIAT']);
        $manual = Veiculo::create([
            'nome' => 'VEICULO MANUAL',
            'montadora_id' => $montadora->id,
        ]);
        $sky = Veiculo::create([
            'nome' => 'VEICULO SKY',
            'montadora_id' => $montadora->id,
        ]);

        // Simula vinculo antigo existente apenas na tabela pivo.
        $legado = Veiculo::create([
            'nome' => 'VEICULO LEGADO',
            'montadora_id' => $montadora->id,
        ]);
        $produto->veiculos()->syncWithoutDetaching([$legado->id]);

        $produto->aplicacoes()->create([
            'veiculo_id' => $manual->id,
            'chave_aplicacao' => hash('sha256', 'manual'),
            'origem' => ProdutoAplicacao::ORIGEM_MANUAL,
            'status_resolucao' => ProdutoAplicacao::STATUS_RESOLVIDA,
        ]);

        $catMontadora = CatalogoMontadora::create([
            'catalogo_fonte_id' => $fonte->id,
            'montadora_id' => $montadora->id,
            'chave_externa' => 'FIAT-1',
            'nome' => 'FIAT',
        ]);

        $catVeiculo = CatalogoVeiculo::create([
            'catalogo_fonte_id' => $fonte->id,
            'catalogo_montadora_id' => $catMontadora->id,
            'veiculo_id' => $sky->id,
            'chave_externa' => 'SKY-VEICULO-1',
            'nome' => 'VEICULO SKY',
        ]);

        $aplicacao = CatalogoProdutoAplicacao::create([
            'catalogo_produto_id' => $ref->id,
            'catalogo_montadora_id' => $catMontadora->id,
            'catalogo_veiculo_id' => $catVeiculo->id,
            'chave_aplicacao' => hash('sha256', 'sky-1'),
            'montadora_raw' => 'FIAT',
            'veiculo_raw' => 'VEICULO SKY',
            'motor' => '1.6',
            'ativa' => true,
        ]);

        $this->post(route('produtos.catalogo.habilitar', $ref))
            ->assertRedirect(route('produtos.show', $produto));

        $produto->refresh();
        $this->assertSame('75.00', $produto->preco_uni);
        $this->assertSame('5.000', $produto->quantidade);
        $this->assertTrue($produto->status);
        $this->assertSame($produto->id, $ref->fresh()->produto_id);
        $this->assertSame('automatico', $ref->fresh()->vinculo_status);
        $this->assertSame(1, Produto::count());

        $this->assertDatabaseHas('produto_aplicacoes', [
            'produto_id' => $produto->id,
            'catalogo_produto_aplicacao_id' => $aplicacao->id,
            'veiculo_id' => $sky->id,
            'origem' => 'sky',
        ]);

        $this->assertEqualsCanonicalizing(
            [$manual->id, $sky->id, $legado->id],
            $produto->veiculos()->pluck('veiculos.id')->all()
        );

        $this->post(route('produtos.catalogo.habilitar', $ref))
            ->assertRedirect(route('produtos.show', $produto));

        $this->assertSame(1, Produto::count());
        $this->assertSame(
            1,
            ProdutoAplicacao::where(
                'catalogo_produto_aplicacao_id',
                $aplicacao->id
            )->count()
        );

        $this->assertSame(
            25,
            (int) $ref->estado()->first()->estoque_observado
        );
    }

    public function test_identidade_contraditoria_bloqueia_habilitacao(): void
    {
        $this->actingAs(User::factory()->create([
            'perfil' => User::PERFIL_ADMIN,
            'status' => 1,
        ]));

        $fonte = CatalogoFonte::create([
            'codigo' => 'sky',
            'nome' => 'Sky',
            'tipo' => 'fornecedor',
            'ativo' => true,
        ]);

        $marca = Marca::create(['nome' => 'GATES', 'ativo' => true]);

        Produto::create([
            'nome' => 'PRODUTO EXISTENTE',
            'descricao' => 'Produto de teste com identidade divergente',
            'marca_id' => $marca->id,
            'codigo_fabricante' => '6PK1200SF',
            'codigo_barras' => '7894758161076',
            'preco_uni' => '75.00',
            'quantidade' => '3.000',
            'status' => true,
        ]);

        $ref = CatalogoProduto::create([
            'catalogo_fonte_id' => $fonte->id,
            'chave_externa' => 'SKY-CONFLITO',
            'marca_raw' => 'GATES',
            'codigo_fabricante' => '6PK1200SF',
            'codigo_barras' => '789475816107',
        ]);

        $this->post(route('produtos.catalogo.habilitar', $ref))
            ->assertSessionHasErrors('catalogo');

        $this->assertNull($ref->fresh()->produto_id);
        $this->assertSame(1, Produto::count());
    }
}
