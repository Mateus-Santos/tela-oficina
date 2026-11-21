<?php

namespace Tests\Feature\Compras;

use App\Actions\Compra\EfetivarConciliacaoCompra;
use App\Actions\Compra\SalvarPreConciliacao;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\ContaPagar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class ConciliacaoEfetivacaoTest extends TestCase
{
    use RefreshDatabase;

    private function compra(): Compra
    {
        $agora = now();
        $fornecedorId = DB::table('fornecedores')->insertGetId([
            'nome' => 'Fornecedor D03B', 'created_at' => $agora, 'updated_at' => $agora,
        ]);
        $produtoId = DB::table('produtos')->insertGetId([
            'nome' => 'Produto D03B', 'descricao' => 'Teste', 'quantidade' => 0,
            'estoque_minimo' => 1, 'preco_uni' => 100,
            'codigo_fabricante' => 'D03B-'.Str::random(10),
            'status' => 1, 'created_at' => $agora, 'updated_at' => $agora,
        ]);
        $compra = Compra::create([
            'fornecedor_id' => $fornecedorId, 'numero_nf' => 'D03B-'.Str::random(10),
            'data_entrada' => today()->toDateString(), 'valor_produtos' => 500,
            'valor_total' => 500, 'status' => Compra::STATUS_APROVADA,
        ]);
        CompraItem::create([
            'compra_id' => $compra->id, 'produto_id' => $produtoId,
            'descricao' => 'Produto D03B', 'quantidade' => 5,
            'quantidade_conferida' => 4, 'quantidade_avariada' => 0,
            'quantidade_aceita' => 4, 'observacao_divergencia' => 'Uma unidade faltando',
            'valor_unitario' => 100, 'desconto' => 0, 'valor_total' => 500,
        ]);
        return $compra;
    }

    private function proposta(Compra $compra, string $tipo, string $valor): User
    {
        $admin = User::factory()->create(['perfil' => User::PERFIL_ADMIN, 'status' => true]);
        app(SalvarPreConciliacao::class)->execute($compra, [
            'tratamento_proposto' => $tipo, 'valor_proposto' => $valor,
            'referencia_documento' => 'ACORDO-D03B',
            'justificativa' => 'Fornecedor concordou com o ajuste documentado.',
        ], $admin->id);
        return $admin;
    }

    private function dados(?int $contaId = null, ?string $uuid = null): array
    {
        return [
            'operacao_uuid' => $uuid ?? (string) Str::uuid(),
            'confirmacao' => '1',
            'documento_confirmacao' => 'CONFIRMADO-D03B',
            'justificativa' => 'Verifiquei o documento do fornecedor e confirmo o valor.',
            'conta_pagar_id' => $contaId,
        ];
    }

    private function contaUnica(Compra $compra): ContaPagar
    {
        $conta = ContaPagar::create([
            'compra_id' => $compra->id,
            'fornecedor_id' => $compra->fornecedor_id,
            'descricao' => 'Conta D03B', 'valor' => '500.00',
            'data_emissao' => today()->toDateString(),
            'data_vencimento' => today()->addDays(30)->toDateString(),
            'status' => 'aberta',
        ]);
        $conta->parcelas()->create([
            'numero' => 1, 'valor' => '500.00',
            'data_vencimento' => today()->addDays(30)->toDateString(),
        ]);
        DB::table('compra_conta_pagar')->insert([
            'compra_id' => $compra->id, 'conta_pagar_id' => $conta->id,
            'valor_vinculado' => '500.00', 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $conta;
    }

    public function test_credito_registrado_nao_movimenta_pagamentos_e_e_idempotente(): void
    {
        $compra = $this->compra();
        $admin = $this->proposta($compra, 'credito', '100.00');
        $dados = $this->dados();
        $acao = app(EfetivarConciliacaoCompra::class);
        $primeira = $acao->execute($compra, $dados, $admin->id);
        $segunda = $acao->execute($compra, $dados, $admin->id);
        $this->assertSame($primeira->id, $segunda->id);
        $this->assertSame('credito_registrado_nao_utilizado', $primeira->status);
        $this->assertDatabaseCount('compra_conciliacao_efetivacoes', 1);
        $this->assertDatabaseCount('contas_pagar', 0);
        $this->assertDatabaseCount('pagamentos_contas_pagar', 0);
        $this->assertSame('efetivada', $compra->preConciliacao()->firstOrFail()->status);
    }

    public function test_abatimento_reduz_conta_unica_parcela_e_pivo_sem_pagamentos(): void
    {
        $compra = $this->compra();
        $admin = $this->proposta($compra, 'abatimento', '80.00');
        $conta = $this->contaUnica($compra);
        $dados = $this->dados($conta->id);
        $efeito = app(EfetivarConciliacaoCompra::class)->execute($compra, $dados, $admin->id);
        $this->assertSame('abatimento_aplicado', $efeito->status);
        $this->assertSame('500.00', $efeito->valor_conta_antes);
        $this->assertSame('420.00', $efeito->valor_conta_depois);
        $this->assertSame('420.00', $conta->fresh()->valor);
        $this->assertSame('420.00', $conta->parcelas()->firstOrFail()->valor);
        $this->assertDatabaseHas('compra_conta_pagar', [
            'compra_id' => $compra->id, 'valor_vinculado' => '420.00',
        ]);
        $this->assertDatabaseCount('pagamentos_contas_pagar', 0);
        app(EfetivarConciliacaoCompra::class)->execute($compra, $dados, $admin->id);
        $this->assertSame('420.00', $conta->fresh()->valor);
    }

    public function test_abatimento_rejeita_pagamento_historico(): void
    {
        $compra = $this->compra();
        $admin = $this->proposta($compra, 'abatimento', '80.00');
        $conta = $this->contaUnica($compra);
        $conta->pagamentos()->create([
            'parcela_conta_pagar_id' => $conta->parcelas()->firstOrFail()->id,
            'valor' => '10.00', 'data_pagamento' => today()->toDateString(),
            'forma_pagamento' => 'Outro',
        ]);
        $this->expectException(InvalidArgumentException::class);
        try {
            app(EfetivarConciliacaoCompra::class)->execute($compra, $this->dados($conta->id), $admin->id);
        } finally {
            $this->assertSame('500.00', $conta->fresh()->valor);
            $this->assertDatabaseCount('compra_conciliacao_efetivacoes', 0);
        }
    }

    public function test_abatimento_rejeita_conta_com_multiplas_parcelas(): void
    {
        $compra = $this->compra();
        $admin = $this->proposta($compra, 'abatimento', '80.00');
        $conta = $this->contaUnica($compra);
        $conta->parcelas()->create([
            'numero' => 2, 'valor' => '1.00', 'data_vencimento' => today()->addDays(60)->toDateString(),
        ]);
        $this->expectException(InvalidArgumentException::class);
        app(EfetivarConciliacaoCompra::class)->execute($compra, $this->dados($conta->id), $admin->id);
    }

    public function test_abatimento_rejeita_conta_agrupada_e_preserva_valor(): void
    {
        $compra = $this->compra();
        $admin = $this->proposta($compra, 'abatimento', '80.00');
        $conta = $this->contaUnica($compra);
        $outra = Compra::create([
            'fornecedor_id' => $compra->fornecedor_id,
            'numero_nf' => 'D03B-OUTRA-'.Str::random(6),
            'data_entrada' => today()->toDateString(),
            'valor_produtos' => 50, 'valor_total' => 50,
            'status' => Compra::STATUS_APROVADA,
        ]);
        DB::table('compra_conta_pagar')->insert([
            'compra_id' => $outra->id, 'conta_pagar_id' => $conta->id,
            'valor_vinculado' => '50.00', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->expectException(InvalidArgumentException::class);
        try {
            app(EfetivarConciliacaoCompra::class)->execute($compra, $this->dados($conta->id), $admin->id);
        } finally {
            $this->assertSame('500.00', $conta->fresh()->valor);
            $this->assertDatabaseCount('compra_conciliacao_efetivacoes', 0);
        }
    }

    public function test_credito_bloqueia_conta_a_pagar_nao_liquidada(): void
    {
        $compra = $this->compra();
        $admin = $this->proposta($compra, 'credito', '100.00');
        $this->contaUnica($compra);
        $this->expectException(InvalidArgumentException::class);
        app(EfetivarConciliacaoCompra::class)->execute($compra, $this->dados(), $admin->id);
    }

    public function test_reembolso_registrado_como_a_receber_sem_pagamento(): void
    {
        $compra = $this->compra();
        $admin = $this->proposta($compra, 'reembolso', '100.00');
        $efeito = app(EfetivarConciliacaoCompra::class)->execute($compra, $this->dados(), $admin->id);
        $this->assertSame('reembolso_a_receber', $efeito->status);
        $this->assertDatabaseCount('pagamentos_contas_pagar', 0);
    }

    public function test_sem_ajuste_encerra_proposta_sem_lancar_valor(): void
    {
        $compra = $this->compra();
        $admin = $this->proposta($compra, 'sem_ajuste', '0.00');
        $efeito = app(EfetivarConciliacaoCompra::class)->execute($compra, $this->dados(), $admin->id);
        $this->assertSame('concluida_sem_ajuste', $efeito->status);
        $this->assertSame('0.00', $efeito->valor);
    }

    public function test_reenvio_divergente_nao_aplica_novamente(): void
    {
        $compra = $this->compra();
        $admin = $this->proposta($compra, 'credito', '100.00');
        $acao = app(EfetivarConciliacaoCompra::class);
        $dados = $this->dados();
        $acao->execute($compra, $dados, $admin->id);
        $dados['documento_confirmacao'] = 'DOCUMENTO-ALTERADO';
        $this->expectException(InvalidArgumentException::class);
        $acao->execute($compra, $dados, $admin->id);
    }

    public function test_usuario_cliente_nao_pode_efetivar(): void
    {
        $compra = $this->compra();
        $this->proposta($compra, 'credito', '100.00');
        $cliente = User::factory()->create(['perfil' => User::PERFIL_CLIENTE, 'status' => true]);
        $this->actingAs($cliente)->post(route('compras.conciliacao.efetivar', $compra), $this->dados())
            ->assertForbidden();
        $this->assertDatabaseCount('compra_conciliacao_efetivacoes', 0);
    }

    public function test_admin_pode_efetivar_pelo_formulario(): void
    {
        $compra = $this->compra();
        $admin = $this->proposta($compra, 'credito', '100.00');
        $this->actingAs($admin)->get(route('compras.preconciliacao.show', $compra))
            ->assertOk()->assertSee('D03B');
        $this->actingAs($admin)->post(route('compras.conciliacao.efetivar', $compra), $this->dados())
            ->assertRedirect(route('compras.preconciliacao.show', $compra));
        $this->assertDatabaseCount('compra_conciliacao_efetivacoes', 1);
    }
}
