<?php

namespace Tests\Feature\Compras;

use App\Actions\Compra\AprovarCompra;
use App\Actions\Compra\ConferirCompra;
use App\Actions\Compra\GerarContaPagarCompra;
use App\Actions\Compra\RegistrarEntradaCompra;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class ConferenciaDivergenciasTest extends TestCase
{
    use RefreshDatabase;

    private function preparar(int $prevista = 10, int $estoqueInicial = 0): array
    {
        $agora = now();
        $fornecedorId = DB::table('fornecedores')->insertGetId([
            'nome' => 'Fornecedor D02', 'created_at' => $agora, 'updated_at' => $agora,
        ]);
        $produtoId = DB::table('produtos')->insertGetId([
            'nome' => 'Produto D02', 'descricao' => 'Teste de divergência',
            'quantidade' => $estoqueInicial, 'estoque_minimo' => 1,
            'preco_uni' => 10, 'codigo_fabricante' => 'D02-'.uniqid(),
            'status' => true, 'created_at' => $agora, 'updated_at' => $agora,
        ]);
        $compra = Compra::create([
            'fornecedor_id' => $fornecedorId, 'numero_nf' => 'D02-'.uniqid(),
            'data_entrada' => $agora->toDateString(), 'valor_produtos' => $prevista * 10,
            'valor_total' => $prevista * 10, 'status' => Compra::STATUS_CONFERINDO,
        ]);
        $item = CompraItem::create([
            'compra_id' => $compra->id, 'produto_id' => $produtoId,
            'descricao' => 'Produto D02', 'quantidade' => $prevista,
            'quantidade_conferida' => null, 'valor_unitario' => 10,
            'desconto' => 0, 'valor_total' => $prevista * 10,
        ]);
        return [$compra, $item, Produto::findOrFail($produtoId)];
    }

    private function conferir(Compra $compra, CompraItem $item, string $recebida, string $avariada = '0', ?string $aceita = null, ?string $observacao = null): Compra
    {
        $linha = ['quantidade_conferida' => $recebida,
            'quantidade_avariada' => $avariada,
            'observacao_divergencia' => $observacao];
        if ($aceita !== null) $linha['quantidade_aceita'] = $aceita;
        return app(ConferirCompra::class)->execute($compra, ['itens' => [$item->id => $linha]]);
    }

    public function test_zero_recebido_com_justificativa_e_registrado_mas_nao_lancado(): void
    {
        [$compra, $item, $produto] = $this->preparar();
        $this->conferir($compra, $item, '0', '0', null, 'Produto não entregue pelo fornecedor.');
        $this->assertSame('0.000', $item->fresh()->quantidade_aceita);
        $this->assertDatabaseHas('compra_conferencia_historicos', [
            'compra_item_id' => $item->id, 'classificacao' => 'falta',
            'quantidade_recebida' => '0.000', 'quantidade_aceita' => '0.000',
        ]);
        app(AprovarCompra::class)->execute($compra);
        try {
            app(RegistrarEntradaCompra::class)->execute($compra);
            $this->fail('Não pode criar entrada para zero aceito.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Nenhuma mercadoria aceita', $e->getMessage());
        }
        $this->assertSame('0.000', $produto->fresh()->quantidade);
        $this->assertDatabaseCount('movimentacao_estoques', 0);
    }

    public function test_excesso_fica_fora_do_estoque_ate_regularizacao(): void
    {
        [$compra, $item, $produto] = $this->preparar();
        $this->conferir($compra, $item, '12', '0', '10', 'Excesso de duas unidades segregadas.');
        app(AprovarCompra::class)->execute($compra);
        app(RegistrarEntradaCompra::class)->execute($compra);
        $this->assertSame('10.000', $produto->fresh()->quantidade);
        $this->assertDatabaseHas('compra_conferencia_historicos', [
            'compra_item_id' => $item->id, 'classificacao' => 'excesso',
            'quantidade_recebida' => '12.000', 'quantidade_aceita' => '10.000',
        ]);
        $this->assertTrue($compra->fresh()->possuiDivergenciaConferencia());
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('D03');
        app(GerarContaPagarCompra::class)->execute($compra, [[
            'numero' => 1, 'valor' => 100,
            'data_vencimento' => now()->addDays(30)->toDateString(),
        ]]);
    }

    public function test_avaria_gera_entrada_somente_para_quantidade_aceita(): void
    {
        [$compra, $item, $produto] = $this->preparar();
        $this->conferir($compra, $item, '10', '3', '7', 'Três unidades avariadas e separadas.');
        app(AprovarCompra::class)->execute($compra);
        app(RegistrarEntradaCompra::class)->execute($compra);
        $this->assertSame('7.000', $produto->fresh()->quantidade);
        $this->assertDatabaseHas('compra_conferencia_historicos', [
            'compra_item_id' => $item->id, 'classificacao' => 'avaria',
            'quantidade_avariada' => '3.000', 'quantidade_aceita' => '7.000',
        ]);
    }

    public function test_quantidade_aceita_maior_que_disponivel_reverte_operacao(): void
    {
        [$compra, $item] = $this->preparar();
        try {
            $this->conferir($compra, $item, '8', '2', '7', 'Foi identificada uma avaria.');
            $this->fail('Deveria rejeitar 7 aceitas para 6 boas.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('excede', $e->getMessage());
        }
        $this->assertNull($item->fresh()->quantidade_conferida);
        $this->assertDatabaseCount('compra_conferencia_historicos', 0);
    }

    public function test_zero_sem_justificativa_permanece_rejeitado(): void
    {
        [$compra, $item] = $this->preparar();
        $this->expectException(InvalidArgumentException::class);
        $this->conferir($compra, $item, '0');
    }

    public function test_conferencia_normal_preserva_geracao_de_conta(): void
    {
        [$compra, $item] = $this->preparar();
        $this->conferir($compra, $item, '10');
        app(AprovarCompra::class)->execute($compra);
        $this->assertFalse($compra->fresh()->possuiDivergenciaConferencia());
        $conta = app(GerarContaPagarCompra::class)->execute($compra, [[
            'numero' => 1, 'valor' => 100,
            'data_vencimento' => now()->addDays(30)->toDateString(),
        ]]);
        $this->assertSame('100.00', $conta->valor);
    }

    public function test_historico_guarda_todas_as_conferencias_antes_da_aprovacao(): void
    {
        [$compra, $item] = $this->preparar();
        $this->conferir($compra, $item, '7', '0', '7', 'Primeira conferência parcial.');
        $this->conferir($compra, $item, '10');
        $this->assertDatabaseCount('compra_conferencia_historicos', 2);
        $this->assertSame('10.000', $item->fresh()->quantidade_aceita);
    }
}
