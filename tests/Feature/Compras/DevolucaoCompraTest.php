<?php

namespace Tests\Feature\Compras;

use App\Actions\Compra\EstornarCompra;
use App\Actions\Compra\RegistrarDevolucaoCompra;
use App\Actions\Compra\RegistrarEntradaCompra;
use App\Actions\Compra\GerarContaPagarCompra;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\Fornecedor;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class DevolucaoCompraTest extends TestCase
{
    use RefreshDatabase;

    private function compraComEstoque(int $quantidade = 5, int $estoqueAnterior = 10): array
    {
        $fornecedorId = DB::table('fornecedores')->insertGetId([
            'nome' => 'Fornecedor D01', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $produtoId = DB::table('produtos')->insertGetId([
            'nome' => 'Produto D01',
            'descricao' => 'Teste de devolucao',
            'quantidade' => $estoqueAnterior,
            'estoque_minimo' => 1,
            'preco_uni' => 100,
            'codigo_fabricante' => 'D01-'.Str::random(12),
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $compra = Compra::create([
            'fornecedor_id' => $fornecedorId,
            'numero_nf' => 'D01-'.Str::random(10),
            'data_entrada' => now()->toDateString(),
            'valor_produtos' => 100 * $quantidade,
            'valor_total' => 100 * $quantidade,
            'status' => Compra::STATUS_APROVADA,
        ]);
        $item = CompraItem::create([
            'compra_id' => $compra->id,
            'produto_id' => $produtoId,
            'descricao' => 'Produto D01',
            'quantidade' => $quantidade,
            'quantidade_conferida' => $quantidade,
            'valor_unitario' => 100,
            'desconto' => 0,
            'valor_total' => 100 * $quantidade,
        ]);
        $produto = Produto::findOrFail($produtoId);
        app(RegistrarEntradaCompra::class)->execute($compra);
        return [$compra, $item, $produto];
    }

    private function dados(CompraItem $item, string $qtd = '2.000', ?string $uuid = null): array
    {
        return [
            'operacao_uuid' => $uuid ?? (string) Str::uuid(),
            'referencia_documento' => 'D01-RET-01',
            'chave_nfe' => null,
            'data_documento' => now()->toDateString(),
            'motivo' => 'Devolucao parcial por defeito verificado na conferencia.',
            'confirmacao_fisica' => '1',
            'itens' => [$item->id => $qtd],
        ];
    }

    public function test_devolucao_parcial_atualiza_estoque_e_preserva_compra(): void
    {
        [$compra, $item, $produto] = $this->compraComEstoque();
        $devolucao = app(RegistrarDevolucaoCompra::class)->execute($compra, $this->dados($item), null);
        $this->assertSame(13.0, (float) $produto->fresh()->quantidade);
        $this->assertSame(Compra::STATUS_APROVADA, $compra->fresh()->status);
        $this->assertDatabaseHas('compra_devolucao_itens', [
            'compra_devolucao_id' => $devolucao->id,
            'compra_item_id' => $item->id,
            'quantidade' => '2.000',
        ]);
        $this->assertDatabaseHas('movimentacao_estoques', [
            'tipo' => 'saida', 'origem_type' => 'App\\Models\\CompraDevolucaoItem',
            'quantidade' => '2.000',
        ]);
    }

    public function test_pode_devolver_em_duas_etapas_sem_ultrapassar_recebido(): void
    {
        [$compra, $item, $produto] = $this->compraComEstoque();
        $action = app(RegistrarDevolucaoCompra::class);
        $action->execute($compra, $this->dados($item, '2.000'), null);
        $action->execute($compra, $this->dados($item, '3.000'), null);
        $this->assertSame(10.0, (float) $produto->fresh()->quantidade);
        $this->assertDatabaseCount('compra_devolucoes', 2);
        $this->assertDatabaseCount('compra_devolucao_itens', 2);
        $this->expectException(InvalidArgumentException::class);
        $action->execute($compra, $this->dados($item, '0.001'), null);
    }

    public function test_repeticao_da_mesma_operacao_nao_baixa_estoque_duas_vezes(): void
    {
        [$compra, $item, $produto] = $this->compraComEstoque();
        $dados = $this->dados($item);
        $action = app(RegistrarDevolucaoCompra::class);
        $primeira = $action->execute($compra, $dados, null);
        $segunda = $action->execute($compra, $dados, null);
        $this->assertSame($primeira->id, $segunda->id);
        $this->assertSame(13.0, (float) $produto->fresh()->quantidade);
        $this->assertDatabaseCount('compra_devolucoes', 1);
        $this->assertDatabaseCount('movimentacao_estoques', 2);
    }

    public function test_quantidade_excessiva_nao_grava_nada(): void
    {
        [$compra, $item, $produto] = $this->compraComEstoque();
        try {
            app(RegistrarDevolucaoCompra::class)->execute($compra, $this->dados($item, '6.000'), null);
            $this->fail('A devolucao excessiva deveria ser rejeitada.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('excede', $e->getMessage());
        }
        $this->assertSame(15.0, (float) $produto->fresh()->quantidade);
        $this->assertDatabaseCount('compra_devolucoes', 0);
        $this->assertDatabaseCount('movimentacao_estoques', 1);
    }

    public function test_falta_de_estoque_reverte_toda_a_operacao(): void
    {
        [$compra, $item, $produto] = $this->compraComEstoque();
        $produto->update(['quantidade' => '1.000']);
        try {
            app(RegistrarDevolucaoCompra::class)->execute($compra, $this->dados($item, '2.000'), null);
            $this->fail('Deveria rejeitar estoque insuficiente.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Estoque insuficiente', $e->getMessage());
        }
        $this->assertSame(1.0, (float) $produto->fresh()->quantidade);
        $this->assertDatabaseCount('compra_devolucoes', 0);
        $this->assertDatabaseCount('compra_devolucao_itens', 0);
    }

    public function test_conta_pagar_ativa_bloqueia_devolucao(): void
    {
        [$compra, $item, $produto] = $this->compraComEstoque();
        app(GerarContaPagarCompra::class)->execute($compra, [[
            'numero' => 1,
            'valor' => 500,
            'data_vencimento' => now()->addMonth()->toDateString(),
        ]]);
        try {
            app(RegistrarDevolucaoCompra::class)->execute($compra, $this->dados($item), null);
            $this->fail('Conta ativa deveria impedir devolucao.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('D03', $e->getMessage());
        }
        $this->assertSame(15.0, (float) $produto->fresh()->quantidade);
        $this->assertDatabaseCount('compra_devolucoes', 0);
    }

    public function test_estorno_integral_e_financeiro_bloqueados_apos_devolucao(): void
    {
        [$compra, $item] = $this->compraComEstoque();
        app(RegistrarDevolucaoCompra::class)->execute($compra, $this->dados($item), null);
        try {
            app(EstornarCompra::class)->execute($compra);
            $this->fail('Estorno integral deveria ser bloqueado.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('devolucao', $e->getMessage());
        }
        $this->expectException(InvalidArgumentException::class);
        app(GerarContaPagarCompra::class)->execute($compra, [[
            'numero' => 1, 'valor' => 500,
            'data_vencimento' => now()->addMonth()->toDateString(),
        ]]);
    }

    public function test_usuario_cliente_nao_acessa_formulario_nem_registra_devolucao(): void
    {
        [$compra, $item] = $this->compraComEstoque();
        $cliente = User::factory()->create(['perfil' => User::PERFIL_CLIENTE, 'status' => true]);
        $this->actingAs($cliente)->get(route('compras.devolucoes.create', $compra))
            ->assertForbidden();
        $this->actingAs($cliente)->post(route('compras.devolucoes.store', $compra), $this->dados($item))
            ->assertForbidden();
        $this->assertDatabaseCount('compra_devolucoes', 0);
    }

    public function test_admin_registra_pelo_formulario_sem_expor_dados_de_outros_itens(): void
    {
        [$compra, $item, $produto] = $this->compraComEstoque();
        $admin = User::factory()->create(['perfil' => User::PERFIL_ADMIN, 'status' => true]);
        $this->actingAs($admin)->get(route('compras.devolucoes.create', $compra))
            ->assertOk()->assertSee('CONTROLE INTERNO');
        $resposta = $this->actingAs($admin)->post(route('compras.devolucoes.store', $compra), $this->dados($item));
        $resposta->assertRedirect(route('compras.show', $compra));
        $resposta->assertSessionHasNoErrors();
        $this->assertSame(13.0, (float) $produto->fresh()->quantidade);
    }

    public function test_item_de_outra_compra_e_rejeitado(): void
    {
        [$compra, , $produto] = $this->compraComEstoque();
        [, $itemOutro] = $this->compraComEstoque();
        try {
            app(RegistrarDevolucaoCompra::class)->execute($compra, $this->dados($itemOutro), null);
            $this->fail('Item externo deveria ser rejeitado.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('nao pertence', $e->getMessage());
        }
        $this->assertSame(15.0, (float) $produto->fresh()->quantidade);
        $this->assertDatabaseCount('compra_devolucoes', 0);
    }
}
