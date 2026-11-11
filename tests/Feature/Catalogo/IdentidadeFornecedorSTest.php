<?php

namespace Tests\Feature\Catalogo;

use App\Models\CatalogoFonte;
use App\Models\CatalogoProduto;
use App\Models\CatalogoProdutoAplicacao;
use App\Models\Marca;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class IdentidadeFornecedorSTest extends TestCase
{
    use RefreshDatabase;

    private function migracao(): object
    {
        return require database_path(
            'migrations/2026_11_09_120000_renomear_fonte_fornecedor_s.php'
        );
    }

    private function produto(): Produto
    {
        $marca = Marca::create([
            'nome' => 'MARCA TESTE MIGRACAO',
            'ativo' => true,
        ]);

        return Produto::create([
            'nome' => 'PRODUTO TESTE MIGRACAO',
            'descricao' => 'Teste de integridade',
            'marca_id' => $marca->id,
            'codigo_fabricante' => 'REF-MIGRACAO-001',
            'preco_uni' => '35.00',
            'quantidade' => '4.000',
            'status' => true,
        ]);
    }

    public function test_migracao_preserva_identidades_e_relacionamentos(): void
    {
        $fonte = CatalogoFonte::create([
            'codigo' => 'sky',
            'nome' => 'Fonte anterior',
            'tipo' => 'fornecedor',
            'ativo' => true,
        ]);

        $produto = $this->produto();

        $catalogo = CatalogoProduto::create([
            'catalogo_fonte_id' => $fonte->id,
            'produto_id' => $produto->id,
            'chave_externa' => 'REFERENCIA-001',
            'descricao' => 'Produto importado',
        ]);

        $tecnica = CatalogoProdutoAplicacao::create([
            'catalogo_produto_id' => $catalogo->id,
            'chave_aplicacao' => hash('sha256', 'tecnica-001'),
            'montadora_raw' => 'FIAT',
            'veiculo_raw' => 'VEICULO TESTE',
            'ativa' => true,
        ]);

        $aplicacao = $produto->aplicacoes()->create([
            'catalogo_produto_aplicacao_id' => $tecnica->id,
            'chave_aplicacao' => hash('sha256', 'tecnica-001'),
            'origem' => 'sky',
            'status_resolucao' => 'revisao',
        ]);

        $this->migracao()->up();

        $this->assertDatabaseHas('catalogo_fontes', [
            'id' => $fonte->id,
            'codigo' => 'fornecedor_s',
            'nome' => 'Fornecedor S',
        ]);

        $this->assertDatabaseHas('catalogo_produtos', [
            'id' => $catalogo->id,
            'catalogo_fonte_id' => $fonte->id,
            'produto_id' => $produto->id,
        ]);

        $this->assertDatabaseHas('produto_aplicacoes', [
            'id' => $aplicacao->id,
            'produto_id' => $produto->id,
            'catalogo_produto_aplicacao_id' => $tecnica->id,
            'origem' => 'fornecedor_s',
        ]);

        $this->assertSame('4.000', $produto->fresh()->quantidade);
        $this->assertSame('35.00', $produto->fresh()->preco_uni);

        // Repetir não pode criar novas fontes ou aplicações.
        $this->migracao()->up();

        $this->assertSame(1, CatalogoFonte::count());
        $this->assertSame(1, CatalogoProduto::count());
        $this->assertSame(1, $produto->aplicacoes()->count());
    }


    public function test_rollback_automatico_nao_altera_fonte_nova(): void
    {
        $fonte = CatalogoFonte::create([
            'codigo' => 'fornecedor_s',
            'nome' => 'Fornecedor S',
            'tipo' => 'fornecedor',
            'ativo' => true,
        ]);

        try {
            $this->migracao()->down();
            $this->fail('A reversão deveria ser bloqueada.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString(
                'Reversão automática bloqueada',
                $e->getMessage()
            );
        }

        $this->assertDatabaseHas('catalogo_fontes', [
            'id' => $fonte->id,
            'codigo' => 'fornecedor_s',
        ]);

        $this->assertDatabaseMissing('catalogo_fontes', [
            'codigo' => 'sky',
        ]);
    }

    public function test_migracao_bloqueia_duplicidade_de_fontes(): void
    {
        CatalogoFonte::create([
            'codigo' => 'sky',
            'nome' => 'Fonte antiga',
            'tipo' => 'fornecedor',
        ]);

        CatalogoFonte::create([
            'codigo' => 'fornecedor_s',
            'nome' => 'Fonte nova',
            'tipo' => 'fornecedor',
        ]);

        try {
            $this->migracao()->up();
            $this->fail('Deveria bloquear a colisão.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString(
                'duas identidades',
                $e->getMessage()
            );
        }

        $this->assertSame(
            2,
            DB::table('catalogo_fontes')->count()
        );
    }

    public function test_migracao_bloqueia_colisao_de_aplicacoes(): void
    {
        CatalogoFonte::create([
            'codigo' => 'sky',
            'nome' => 'Fonte antiga',
            'tipo' => 'fornecedor',
        ]);

        $produto = $this->produto();
        $chave = hash('sha256', 'mesma-aplicacao');

        foreach (['sky', 'fornecedor_s'] as $origem) {
            $produto->aplicacoes()->create([
                'chave_aplicacao' => $chave,
                'origem' => $origem,
                'status_resolucao' => 'revisao',
            ]);
        }

        try {
            $this->migracao()->up();
            $this->fail('Deveria bloquear aplicações conflitantes.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString(
                'aplicações com identidade conflitante',
                $e->getMessage()
            );
        }

        $this->assertDatabaseHas('catalogo_fontes', [
            'codigo' => 'sky',
        ]);

        $this->assertSame(2, $produto->aplicacoes()->count());
    }
}
