<?php

namespace Tests\Feature\Compras;

use App\Actions\Compra\SalvarPreConciliacao;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class PreConciliacaoFinanceiraTest extends TestCase
{
    use RefreshDatabase;

    private function compraDivergente(): Compra
    {
        $agora = now();
        $fornecedorId = DB::table('fornecedores')->insertGetId([
            'nome' => 'Fornecedor D03A', 'created_at' => $agora, 'updated_at' => $agora,
        ]);
        $produtoId = DB::table('produtos')->insertGetId([
            'nome' => 'Peça D03A', 'descricao' => 'Teste D03A', 'quantidade' => 5,
            'estoque_minimo' => 1, 'preco_uni' => 100,
            'codigo_fabricante' => 'D03-'.uniqid(), 'status' => 1,
            'created_at' => $agora, 'updated_at' => $agora,
        ]);
        $compra = Compra::create([
            'fornecedor_id' => $fornecedorId, 'numero_nf' => 'D03A-'.uniqid(),
            'data_entrada' => $agora->toDateString(), 'valor_produtos' => 500,
            'valor_total' => 500, 'status' => Compra::STATUS_APROVADA,
        ]);
        CompraItem::create([
            'compra_id' => $compra->id, 'produto_id' => $produtoId,
            'descricao' => 'Peça D03A', 'quantidade' => 5,
            'quantidade_conferida' => 4, 'quantidade_avariada' => 0,
            'quantidade_aceita' => 4, 'observacao_divergencia' => 'Uma peça ausente',
            'valor_unitario' => 100, 'desconto' => 0, 'valor_total' => 500,
        ]);
        return $compra;
    }

    private function dados(string $tipo = 'credito', string $valor = '100.00'): array
    {
        return [
            'tratamento_proposto' => $tipo,
            'valor_proposto' => $valor,
            'referencia_documento' => 'DOCUMENTO-D03A',
            'justificativa' => 'Proposta registrada para avaliação do fornecedor.',
        ];
    }

    public function test_admin_salva_proposta_sem_modificar_contas_ou_estoque(): void
    {
        $compra = $this->compraDivergente();
        $admin = User::factory()->create(['perfil' => User::PERFIL_ADMIN, 'status' => true]);
        $this->actingAs($admin)->get(route('compras.preconciliacao.show', $compra))
            ->assertOk()->assertSee('PRÉ-CONCILIAÇÃO');
        $this->actingAs($admin)->post(route('compras.preconciliacao.store', $compra), $this->dados())
            ->assertRedirect(route('compras.preconciliacao.show', $compra));
        $this->assertDatabaseHas('compra_pre_conciliacoes', [
            'compra_id' => $compra->id, 'status' => 'rascunho',
            'tratamento_proposto' => 'credito', 'valor_proposto' => '100.00',
        ]);
        $this->assertDatabaseCount('contas_pagar', 0);
        $this->assertDatabaseCount('pagamentos_contas_pagar', 0);
        $this->assertDatabaseCount('movimentacao_estoques', 0);
    }

    public function test_uma_compra_tem_um_unico_rascunho_editavel(): void
    {
        $compra = $this->compraDivergente();
        $admin = User::factory()->create(['perfil' => User::PERFIL_ADMIN, 'status' => true]);
        $acao = app(SalvarPreConciliacao::class);
        $acao->execute($compra, $this->dados(), $admin->id);
        $acao->execute($compra, $this->dados('abatimento', '80.00'), $admin->id);
        $this->assertDatabaseCount('compra_pre_conciliacoes', 1);
        $this->assertDatabaseHas('compra_pre_conciliacoes', [
            'compra_id' => $compra->id, 'tratamento_proposto' => 'abatimento',
            'valor_proposto' => '80.00',
        ]);
    }

    public function test_nao_aceita_valor_maior_que_compra(): void
    {
        $compra = $this->compraDivergente();
        $admin = User::factory()->create(['perfil' => User::PERFIL_ADMIN, 'status' => true]);
        $this->expectException(InvalidArgumentException::class);
        try {
            app(SalvarPreConciliacao::class)->execute($compra, $this->dados('credito', '501.00'), $admin->id);
        } finally {
            $this->assertDatabaseCount('compra_pre_conciliacoes', 0);
        }
    }

    public function test_sem_ajuste_exige_valor_zero(): void
    {
        $compra = $this->compraDivergente();
        $admin = User::factory()->create(['perfil' => User::PERFIL_ADMIN, 'status' => true]);
        $this->expectException(InvalidArgumentException::class);
        app(SalvarPreConciliacao::class)->execute($compra, $this->dados('sem_ajuste', '10.00'), $admin->id);
    }

    public function test_zero_aceito_e_indicado_como_sem_entrada_necessaria(): void
    {
        $compra = $this->compraDivergente();
        $compra->itens()->update([
            'quantidade_conferida' => 0,
            'quantidade_aceita' => 0,
            'observacao_divergencia' => 'Produto nao entregue',
        ]);
        $admin = User::factory()->create(['perfil' => User::PERFIL_ADMIN, 'status' => true]);
        $this->actingAs($admin)->get(route('compras.show', $compra))
            ->assertOk()->assertSee('Sem entrada (0 aceito)');
        $this->assertDatabaseCount('movimentacao_estoques', 0);
    }

    public function test_cliente_nao_acessa_e_nao_salva(): void
    {
        $compra = $this->compraDivergente();
        $cliente = User::factory()->create(['perfil' => User::PERFIL_CLIENTE, 'status' => true]);
        $this->actingAs($cliente)->get(route('compras.preconciliacao.show', $compra))->assertForbidden();
        $this->actingAs($cliente)->post(route('compras.preconciliacao.store', $compra), $this->dados())
            ->assertForbidden();
        $this->assertDatabaseCount('compra_pre_conciliacoes', 0);
    }

    public function test_compra_sem_divergencia_ou_devolucao_e_rejeitada(): void
    {
        $compra = $this->compraDivergente();
        $compra->itens()->update([
            'quantidade_conferida' => 5, 'quantidade_aceita' => 5,
            'observacao_divergencia' => null,
        ]);
        $admin = User::factory()->create(['perfil' => User::PERFIL_ADMIN, 'status' => true]);
        $this->expectException(InvalidArgumentException::class);
        app(SalvarPreConciliacao::class)->execute($compra, $this->dados(), $admin->id);
    }
}
