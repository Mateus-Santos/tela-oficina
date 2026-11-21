<?php

namespace Tests\Feature\Homologacao;

use App\Actions\Compra\AprovarCompra;
use App\Actions\Compra\ConferirCompra;
use App\Actions\Compra\EfetivarConciliacaoCompra;
use App\Actions\Compra\RegistrarDevolucaoCompra;
use App\Actions\Compra\RegistrarEntradaCompra;
use App\Actions\Compra\SalvarPreConciliacao;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\ContaPagar;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * H01: testes de integracao entre D01A, D02, D03A e D03B.
 * O banco e criado exclusivamente pelo ambiente de testes (RefreshDatabase).
 * Nao executa emissao fiscal, operacao bancaria ou comandos de deploy.
 */
class H01FluxoComprasFinanceiroTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{Compra, CompraItem, Produto} */
    private function prepararCompra(int $prevista, int $estoqueInicial, int $precoUnitario = 100): array
    {
        $agora = now();
        $fornecedorId = DB::table('fornecedores')->insertGetId([
            'nome' => 'Fornecedor H01 '.Str::random(8),
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);
        $produtoId = DB::table('produtos')->insertGetId([
            'nome' => 'Produto H01 '.Str::random(8),
            'descricao' => 'Material ficticio de homologacao',
            'quantidade' => $estoqueInicial,
            'estoque_minimo' => 1,
            'preco_uni' => $precoUnitario,
            'codigo_fabricante' => 'H01-'.Str::random(12),
            'status' => true,
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);
        $valor = $prevista * $precoUnitario;
        $compra = Compra::create([
            'fornecedor_id' => $fornecedorId,
            'numero_nf' => 'H01-'.Str::random(10),
            'data_entrada' => today()->toDateString(),
            'valor_produtos' => $valor,
            'valor_total' => $valor,
            'status' => Compra::STATUS_CONFERINDO,
        ]);
        $item = CompraItem::create([
            'compra_id' => $compra->id,
            'produto_id' => $produtoId,
            'descricao' => 'Produto H01',
            'quantidade' => $prevista,
            'quantidade_conferida' => null,
            'valor_unitario' => $precoUnitario,
            'desconto' => 0,
            'valor_total' => $valor,
        ]);

        return [$compra, $item, Produto::findOrFail($produtoId)];
    }

    private function conferirEAprovar(
        Compra $compra,
        CompraItem $item,
        string $recebida,
        string $avariada = '0',
        ?string $aceita = null,
        ?string $observacao = null
    ): void {
        $linha = [
            'quantidade_conferida' => $recebida,
            'quantidade_avariada' => $avariada,
            'observacao_divergencia' => $observacao,
        ];
        if ($aceita !== null) {
            $linha['quantidade_aceita'] = $aceita;
        }
        app(ConferirCompra::class)->execute($compra, [
            'itens' => [$item->id => $linha],
        ]);
        app(AprovarCompra::class)->execute($compra);
        app(RegistrarEntradaCompra::class)->execute($compra);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'perfil' => User::PERFIL_ADMIN,
            'status' => true,
        ]);
    }

    private function proposta(Compra $compra, User $admin, string $tipo, string $valor): void
    {
        app(SalvarPreConciliacao::class)->execute($compra, [
            'tratamento_proposto' => $tipo,
            'valor_proposto' => $valor,
            'referencia_documento' => 'H01-ACORDO',
            'justificativa' => 'Acordo ficticio validado na homologacao integrada.',
        ], $admin->id);
    }

    private function dadosEfetivacao(?int $contaId = null): array
    {
        return [
            'operacao_uuid' => (string) Str::uuid(),
            'confirmacao' => '1',
            'documento_confirmacao' => 'H01-DOCUMENTO',
            'justificativa' => 'Confirmacao ficticia do ajuste de homologacao.',
            'conta_pagar_id' => $contaId,
        ];
    }

    public function test_compra_conferida_entrada_devolucao_e_credito_preservam_estoque_e_pagamentos(): void
    {
        [$compra, $item, $produto] = $this->prepararCompra(5, 10);
        $this->conferirEAprovar($compra, $item, '5');
        $this->assertSame(15.0, (float) $produto->fresh()->quantidade);
        $this->assertSame(Compra::STATUS_APROVADA, $compra->fresh()->status);

        $dadosDevolucao = [
            'operacao_uuid' => (string) Str::uuid(),
            'referencia_documento' => 'H01-DEV-001',
            'chave_nfe' => null,
            'data_documento' => today()->toDateString(),
            'motivo' => 'Devolucao parcial ficticia apos conferencia fisica.',
            'confirmacao_fisica' => '1',
            'itens' => [$item->id => '2.000'],
        ];
        $devolucao = app(RegistrarDevolucaoCompra::class)->execute($compra, $dadosDevolucao, null);
        $reenvio = app(RegistrarDevolucaoCompra::class)->execute($compra, $dadosDevolucao, null);
        $this->assertSame($devolucao->id, $reenvio->id);
        $this->assertSame(13.0, (float) $produto->fresh()->quantidade);

        $admin = $this->admin();
        $this->proposta($compra, $admin, 'credito', '100.00');
        $dados = $this->dadosEfetivacao();
        $acao = app(EfetivarConciliacaoCompra::class);
        $efeito = $acao->execute($compra, $dados, $admin->id);
        $repeticao = $acao->execute($compra, $dados, $admin->id);
        $this->assertSame($efeito->id, $repeticao->id);
        $this->assertSame('credito_registrado_nao_utilizado', $efeito->status);
        $this->assertSame(13.0, (float) $produto->fresh()->quantidade);
        $this->assertDatabaseCount('compra_devolucoes', 1);
        $this->assertDatabaseCount('compra_conciliacao_efetivacoes', 1);
        $this->assertDatabaseCount('movimentacao_estoques', 2);
        $this->assertDatabaseCount('contas_pagar', 0);
        $this->assertDatabaseCount('pagamentos_contas_pagar', 0);
    }

    public function test_avaria_e_falta_entram_apenas_com_quantidade_aceita_e_sem_ajuste_nao_refaz_estoque(): void
    {
        [$compra, $item, $produto] = $this->prepararCompra(10, 0, 10);
        $this->conferirEAprovar(
            $compra, $item, '8', '2', '6',
            'Duas unidades faltantes e outras duas avariadas.'
        );
        $this->assertSame(6.0, (float) $produto->fresh()->quantidade);
        $this->assertTrue($compra->fresh()->possuiDivergenciaConferencia());
        $this->assertDatabaseHas('compra_conferencia_historicos', [
            'compra_item_id' => $item->id,
            'quantidade_recebida' => '8.000',
            'quantidade_avariada' => '2.000',
            'quantidade_aceita' => '6.000',
        ]);

        $admin = $this->admin();
        $this->proposta($compra, $admin, 'sem_ajuste', '0.00');
        $efeito = app(EfetivarConciliacaoCompra::class)
            ->execute($compra, $this->dadosEfetivacao(), $admin->id);
        $this->assertSame('concluida_sem_ajuste', $efeito->status);
        $this->assertSame('0.00', $efeito->valor);
        $this->assertSame(6.0, (float) $produto->fresh()->quantidade);
        $this->assertDatabaseCount('movimentacao_estoques', 1);
        $this->assertDatabaseCount('pagamentos_contas_pagar', 0);
    }

    public function test_abatimento_de_compra_divergente_altera_somente_conta_individual_e_parcela(): void
    {
        [$compra, $item, $produto] = $this->prepararCompra(5, 2);
        $this->conferirEAprovar(
            $compra, $item, '4', '0', '4',
            'Uma unidade nao chegou na entrega do fornecedor.'
        );
        $this->assertSame(6.0, (float) $produto->fresh()->quantidade);

        // Fotografia de uma conta legada: o fluxo padrao bloqueia
        // criacao automatica de conta para compra com divergencia.
        $conta = ContaPagar::create([
            'compra_id' => $compra->id,
            'fornecedor_id' => $compra->fornecedor_id,
            'descricao' => 'Conta individual legada H01',
            'valor' => '500.00',
            'data_emissao' => today()->toDateString(),
            'data_vencimento' => today()->addDays(30)->toDateString(),
            'status' => 'aberta',
        ]);
        $parcela = $conta->parcelas()->create([
            'numero' => 1,
            'valor' => '500.00',
            'data_vencimento' => today()->addDays(30)->toDateString(),
        ]);
        DB::table('compra_conta_pagar')->insert([
            'compra_id' => $compra->id,
            'conta_pagar_id' => $conta->id,
            'valor_vinculado' => '500.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->admin();
        $this->proposta($compra, $admin, 'abatimento', '80.00');
        $dados = $this->dadosEfetivacao($conta->id);
        $acao = app(EfetivarConciliacaoCompra::class);
        $efeito = $acao->execute($compra, $dados, $admin->id);
        $acao->execute($compra, $dados, $admin->id);
        $this->assertSame('abatimento_aplicado', $efeito->status);
        $this->assertSame('420.00', $conta->fresh()->valor);
        $this->assertSame('420.00', $parcela->fresh()->valor);
        $this->assertDatabaseHas('compra_conta_pagar', [
            'compra_id' => $compra->id,
            'conta_pagar_id' => $conta->id,
            'valor_vinculado' => '420.00',
        ]);
        $this->assertSame(6.0, (float) $produto->fresh()->quantidade);
        $this->assertDatabaseCount('movimentacao_estoques', 1);
        $this->assertDatabaseCount('pagamentos_contas_pagar', 0);
        $this->assertDatabaseCount('compra_conciliacao_efetivacoes', 1);
    }
}
