<?php

namespace Tests\Feature\Compras;

use App\Actions\Compra\RegistrarDevolucaoDocumentalCompra;
use App\Actions\Compra\RegistrarEntradaCompra;
use App\Actions\ContasPagar\VincularComprasContaPagar;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\ContaPagar;
use App\Models\ParcelaContaPagar;
use App\Models\Produto;
use App\Models\User;
use App\Services\Compra\ValorDevidoCompra;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class D04DevolucaoDocumentalTest extends TestCase
{
    use RefreshDatabase;

    private function fornecedor(): int
    {
        return DB::table('fornecedores')->insertGetId([
            'nome' => 'Fornecedor D04', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function item(Compra $compra, int $qtd, int $aceita, int $preco): array
    {
        $id = DB::table('produtos')->insertGetId([
            'nome' => 'Peca D04 '.Str::random(8),
            'descricao' => 'Teste de devolucao documentada',
            'quantidade' => 10, 'estoque_minimo' => 1,
            'preco_uni' => $preco, 'codigo_fabricante' => 'D04-'.Str::random(12),
            'status' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $item = CompraItem::create([
            'compra_id' => $compra->id, 'produto_id' => $id,
            'descricao' => 'Peca de teste', 'quantidade' => $qtd,
            'quantidade_conferida' => $aceita, 'quantidade_aceita' => $aceita,
            'valor_unitario' => $preco, 'valor_total' => $qtd * $preco,
            'desconto' => 0,
        ]);
        return [$item, Produto::findOrFail($id)];
    }

    private function compra(int $fornecedor, int $valor): Compra
    {
        return Compra::create([
            'fornecedor_id' => $fornecedor,
            'numero_nf' => 'D04-'.Str::random(12),
            'data_entrada' => today()->toDateString(),
            'valor_produtos' => $valor,
            'valor_total' => $valor,
            'status' => Compra::STATUS_APROVADA,
        ]);
    }

    private function dados(array $linhas, ?string $uuid = null): array
    {
        return [
            'operacao_uuid' => $uuid ?? (string) Str::uuid(),
            'referencia_documento' => 'DEV-D04-123',
            'chave_nfe' => null,
            'data_documento' => today()->toDateString(),
            'motivo' => 'Devolucao acordada e documentada com fornecedor.',
            'confirmacao_fisica' => '1',
            'confirmacao_financeira' => '1',
            'itens' => $linhas,
        ];
    }

    public function test_bomba_recusada_sem_estoque_abate_financeiro_e_preserva_nf_original(): void
    {
        $compra = $this->compra($this->fornecedor(), 250);
        [$oleo, $produtoOleo] = $this->item($compra, 1, 1, 100);
        [$bomba, $produtoBomba] = $this->item($compra, 1, 0, 150);
        app(RegistrarEntradaCompra::class)->execute($compra);
        $estoqueBomba = $produtoBomba->fresh()->quantidade;
        $res = app(RegistrarDevolucaoDocumentalCompra::class)->execute($compra, $this->dados([
            $bomba->id => ['quantidade' => '1.000', 'modalidade' => 'recusado'],
        ]), null);
        $this->assertSame('aplicada', $res->situacao_financeira);
        $this->assertSame((float) $estoqueBomba, (float) $produtoBomba->fresh()->quantidade);
        $this->assertSame(10000, app(ValorDevidoCompra::class)->valorLiquidoCentavos($compra));
        $this->assertSame(250.0, (float) $compra->fresh()->valor_total);
        $this->assertNull($res->itens->first()->movimentacao_estoque_id);
        $this->assertSame(11.0, (float) $produtoOleo->fresh()->quantidade);
    }

    public function test_conta_agrupada_e_duas_parcelas_sincronizam_com_devolucao(): void
    {
        $fornecedor = $this->fornecedor();
        $primeira = $this->compra($fornecedor, 200);
        [$item1, $produto] = $this->item($primeira, 2, 2, 100);
        app(RegistrarEntradaCompra::class)->execute($primeira);
        $segunda = $this->compra($fornecedor, 150);
        $this->item($segunda, 1, 1, 150);
        $conta = ContaPagar::create([
            'fornecedor_id' => $fornecedor,
            'descricao' => 'D04 Agrupada', 'valor' => '350.00',
            'data_emissao' => today()->toDateString(),
            'data_vencimento' => today()->addMonth()->toDateString(),
            'status' => 'aberta',
        ]);
        foreach ([200, 150] as $indice => $valor) {
            ParcelaContaPagar::create([
                'conta_pagar_id' => $conta->id,
                'numero' => $indice + 1, 'valor' => $valor,
                'data_vencimento' => today()->addMonths($indice + 1)->toDateString(),
            ]);
        }
        app(VincularComprasContaPagar::class)->execute($conta, [$primeira->id, $segunda->id]);
        $dados = $this->dados([$item1->id => [
            'quantidade' => '1.000', 'modalidade' => 'estoque',
        ]]);
        $acao = app(RegistrarDevolucaoDocumentalCompra::class);
        $ret = $acao->execute($primeira, $dados, null);
        $this->assertSame('aplicada', $ret->situacao_financeira);
        $this->assertSame(250.0, (float) $conta->fresh()->valor);
        $this->assertSame(100.0, (float) DB::table('compra_conta_pagar')
            ->where('compra_id', $primeira->id)->value('valor_vinculado'));
        $this->assertSame(250.0, (float) $conta->parcelas()->sum('valor'));
        $this->assertSame(11.0, (float) $produto->fresh()->quantidade);
        $this->assertSame($ret->id, $acao->execute($primeira, $dados, null)->id);
        $this->assertDatabaseCount('compra_devolucoes', 1);
        $this->assertSame(250.0, (float) $conta->fresh()->valor);
    }

    public function test_conta_nao_aberta_documenta_mas_nao_modifica_saldo_financeiro(): void
    {
        $fornecedor = $this->fornecedor();
        $compra = $this->compra($fornecedor, 100);
        [$item] = $this->item($compra, 1, 1, 100);
        app(RegistrarEntradaCompra::class)->execute($compra);
        $conta = ContaPagar::create([
            'fornecedor_id' => $fornecedor,
            'descricao' => 'Conta encerrada D04', 'valor' => 100,
            'data_emissao' => today()->toDateString(),
            'data_vencimento' => today()->addMonth()->toDateString(),
            'status' => 'paga',
        ]);
        $conta->compras()->attach($compra, ['valor_vinculado' => 100]);
        $conta->parcelas()->create([
            'numero' => 1, 'valor' => 100,
            'data_vencimento' => today()->addMonth()->toDateString(),
        ]);
        $ret = app(RegistrarDevolucaoDocumentalCompra::class)->execute($compra, $this->dados([
            $item->id => ['quantidade' => '0.500', 'modalidade' => 'estoque'],
        ]), null);
        $this->assertSame('pendente', $ret->situacao_financeira);
        $this->assertSame(100.0, (float) $conta->fresh()->valor);
        $this->assertSame(100.0, (float) $conta->parcelas()->sum('valor'));
        $this->assertSame(10000, app(ValorDevidoCompra::class)->valorLiquidoCentavos($compra));
    }

    public function test_rejeita_devolver_mais_que_saldo_sem_criar_documento(): void
    {
        $compra = $this->compra($this->fornecedor(), 100);
        [$item] = $this->item($compra, 1, 1, 100);
        app(RegistrarEntradaCompra::class)->execute($compra);
        $this->expectException(InvalidArgumentException::class);
        try {
            app(RegistrarDevolucaoDocumentalCompra::class)->execute($compra, $this->dados([
                $item->id => ['quantidade' => '2.000', 'modalidade' => 'estoque'],
            ]), null);
        } finally {
            $this->assertDatabaseCount('compra_devolucoes', 0);
        }
    }

    public function test_cliente_nao_consegue_abrir_ou_registrar_devolucao(): void
    {
        $compra = $this->compra($this->fornecedor(), 100);
        [$item] = $this->item($compra, 1, 1, 100);
        $cliente = User::factory()->create(['perfil' => User::PERFIL_CLIENTE, 'status' => true]);
        $this->actingAs($cliente)
            ->get(route('compras.devolucoes.documental.create', $compra))
            ->assertForbidden();
        $this->actingAs($cliente)
            ->post(route('compras.devolucoes.documental.store', $compra), $this->dados([
                $item->id => ['quantidade' => '1.000', 'modalidade' => 'estoque'],
            ]))->assertForbidden();
    }
}
