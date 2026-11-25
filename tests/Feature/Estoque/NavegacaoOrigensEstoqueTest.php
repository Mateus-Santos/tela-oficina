<?php

namespace Tests\Feature\Estoque;

use App\Models\Compra;
use App\Models\CompraDevolucao;
use App\Models\CompraDevolucaoItem;
use App\Models\CompraItem;
use App\Models\MovimentacaoEstoque;
use App\Models\Nota;
use App\Models\NotasItem;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NavegacaoOrigensEstoqueTest extends TestCase
{
    use RefreshDatabase;

    private function produto(string $nome): Produto
    {
        return Produto::query()->create([
            'nome' => $nome, 'descricao' => $nome,
            'quantidade' => '10.000', 'estoque_minimo' => '1.000',
            'preco_uni' => '12.00', 'status' => true,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create([
            'perfil' => User::PERFIL_ADMIN, 'status' => true,
        ]));
    }

    public function test_link_do_estoque_filtra_id_exato_e_mostra_movimentacoes(): void
    {
        $produto = $this->produto('UX-NAVEGACAO-PRINCIPAL');
        $outro = $this->produto('UX-NAVEGACAO-OUTRO');
        $this->get(route('estoque.index', ['produto_id' => $produto->id]))
            ->assertOk()
            ->assertSee($produto->nome)
            ->assertDontSee($outro->nome)
            ->assertSee(route('estoque.movimentacoes.index', ['produto_id' => $produto->id]), false);
    }

    public function test_origem_mostra_id_documental_e_link_de_compra_nota_e_devolucao(): void
    {
        $produto = $this->produto('UX-ORIGEM-PRODUTO');
        $fornecedorId = DB::table('fornecedores')->insertGetId([
            'nome' => 'Fornecedor UX origem',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $compra = Compra::query()->create([
            'fornecedor_id' => $fornecedorId,
            'numero_nf' => 'UX-001', 'data_emissao' => now()->toDateString(),
            'data_entrada' => now()->toDateString(), 'valor_produtos' => 12,
            'desconto' => 0, 'frete' => 0, 'outras_despesas' => 0,
            'valor_total' => 12, 'status' => Compra::STATUS_PENDENTE,
        ]);
        $item = CompraItem::query()->create([
            'compra_id' => $compra->id, 'produto_id' => $produto->id,
            'descricao' => $produto->nome, 'quantidade' => 1,
            'valor_unitario' => 12, 'desconto' => 0, 'valor_total' => 12,
        ]);
        $nota = Nota::query()->create(['tipo' => 'Venda', 'status' => 'Aberto']);
        $notaItem = NotasItem::query()->create([
            'nota_id' => $nota->id,
            'itemable_type' => Produto::class, 'itemable_id' => $produto->id,
            'descricao' => $produto->nome, 'quantidade' => 1,
            'valor_unitario' => 12, 'valor_total' => 12,
        ]);
        $devolucao = CompraDevolucao::query()->create([
            'compra_id' => $compra->id, 'operacao_uuid' => (string) Str::uuid(),
            'referencia_documento' => 'DEV-UX-001',
            'data_documento' => now()->toDateString(),
            'motivo' => 'Ensaio de navegacao', 'status' => 'efetivada',
        ]);
        $devolucaoItem = CompraDevolucaoItem::query()->create([
            'compra_devolucao_id' => $devolucao->id,
            'compra_item_id' => $item->id,
            'produto_id' => $produto->id, 'quantidade' => 1,
        ]);
        foreach ([$item, $notaItem, $devolucaoItem, null] as $index => $origem) {
            MovimentacaoEstoque::query()->create([
                'produto_id' => $produto->id,
                'tipo' => $index === 0 ? 'entrada' : 'saida',
                'quantidade' => 1, 'saldo_anterior' => 10,
                'saldo_posterior' => $index === 0 ? 11 : 9,
                'origem_type' => $origem?->getMorphClass(),
                'origem_id' => $origem?->getKey(),
            ]);
        }
        $response = $this->get(route('estoque.movimentacoes.index', ['produto_id' => $produto->id]));
        $response->assertOk()
            ->assertSee('Compra #'.$compra->id)
            ->assertSee('Nota #'.$nota->id)
            ->assertSee('Devolução da Compra #'.$compra->id)
            ->assertSee('href="'.route('compras.show', $compra->id).'"', false)
            ->assertSee('href="'.route('notas.show', $nota->id).'"', false)
            ->assertSee('Manual');

        $this->get(route('compras.show', $compra->id))->assertOk()
            ->assertSee(route('estoque.index', ['produto_id' => $produto->id]), false);
    }
}
