<?php

namespace Tests\Feature\Usabilidade;

use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\ContaPagar;
use App\Models\Fornecedor;
use App\Models\MovimentacaoEstoque;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicadoresIntegracaoComprasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create([
            'perfil' => User::PERFIL_ADMIN,
            'status' => true,
        ]));
    }

    private function compra(string $status = Compra::STATUS_APROVADA): Compra
    {
        $fornecedor = Fornecedor::create(['nome' => 'Fornecedor U02 '.uniqid()]);

        return Compra::create([
            'fornecedor_id' => $fornecedor->id,
            'numero_nf' => 'U02-'.uniqid(),
            'data_entrada' => now()->toDateString(),
            'valor_produtos' => '100.00',
            'desconto' => '0.00',
            'frete' => '0.00',
            'outras_despesas' => '0.00',
            'valor_total' => '100.00',
            'status' => $status,
        ]);
    }

    private function item(Compra $compra): CompraItem
    {
        $produto = Produto::create([
            'nome' => 'Produto U02 '.uniqid(),
            'descricao' => 'Teste dos indicadores',
            'codigo_fabricante' => 'U02-'.uniqid(),
            'quantidade' => '0.000',
            'estoque_minimo' => '0.000',
            'preco_uni' => '50.00',
            'status' => true,
        ]);

        return CompraItem::create([
            'compra_id' => $compra->id,
            'produto_id' => $produto->id,
            'descricao' => $produto->nome,
            'quantidade' => '1.000',
            'quantidade_conferida' => '1.000',
            'valor_unitario' => '50.00',
            'desconto' => '0.00',
            'valor_total' => '50.00',
        ]);
    }

    private function movimentar(CompraItem $item, string $tipo, string $observacoes): void
    {
        MovimentacaoEstoque::create([
            'produto_id' => $item->produto_id,
            'tipo' => $tipo,
            'quantidade' => '1.000',
            'saldo_anterior' => '0.000',
            'saldo_posterior' => '1.000',
            'origem_type' => CompraItem::class,
            'origem_id' => $item->id,
            'observacoes' => $observacoes,
        ]);
    }

    private function conta(Compra $compra, string $status = 'aberta', bool $legada = false): ContaPagar
    {
        return ContaPagar::create([
            'compra_id' => $legada ? $compra->id : null,
            'fornecedor_id' => $compra->fornecedor_id,
            'descricao' => 'Conta U02 '.uniqid(),
            'valor' => '100.00',
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()->addMonth()->toDateString(),
            'status' => $status,
        ]);
    }

    private function listada(Compra $compra): Compra
    {
        $response = $this->get(route('compras.index', [
            'numero_nf' => $compra->numero_nf,
            'status' => $compra->status,
        ]))->assertOk()->assertViewIs('compra.index');

        $listadas = $response->viewData('compras');
        $this->assertSame(1, $listadas->total());
        $registro = $listadas->getCollection()->first();
        $this->assertNotNull($registro);
        $this->assertSame($compra->id, $registro->id);
        // Prova que a controller NAO carrega as colecoes para calcular as badges.
        $this->assertFalse($registro->relationLoaded('itens'));
        $this->assertFalse($registro->relationLoaded('contasPagar'));
        $this->assertFalse($registro->relationLoaded('contaPagar'));

        return $registro;
    }

    public function test_compra_sem_itens_nem_contas_tem_estados_neutros(): void
    {
        $compra = $this->compra();
        $registro = $this->listada($compra);
        $this->assertSame('sem_itens', $registro->integracao_estoque_status);
        $this->assertSame('nao_vinculada', $registro->integracao_financeiro_status);
        $this->assertSame(0, (int) $registro->u02_total_itens);
    }

    public function test_estoque_pendente_parcial_e_total_sem_duplicar_contagem(): void
    {
        $compra = $this->compra();
        $primeiro = $this->item($compra);
        $segundo = $this->item($compra);

        $this->assertSame('pendente', $this->listada($compra)->integracao_estoque_status);
        $this->movimentar($primeiro, 'entrada', 'Entrada da compra');
        $this->assertSame('parcial', $this->listada($compra)->integracao_estoque_status);
        $this->movimentar($primeiro, 'entrada', 'Outra entrada historica');
        $registro = $this->listada($compra);
        $this->assertSame('parcial', $registro->integracao_estoque_status);
        $this->assertSame(1, (int) $registro->u02_itens_com_entrada);

        $this->movimentar($segundo, 'entrada', 'Entrada da compra');
        $registro = $this->listada($compra);
        $this->assertSame('lancado', $registro->integracao_estoque_status);
        $this->assertSame(2, (int) $registro->u02_itens_com_entrada);
    }

    public function test_compra_cancelada_nao_aparece_como_estoque_concluido_apos_estorno(): void
    {
        $compra = $this->compra(Compra::STATUS_CANCELADA);
        $item = $this->item($compra);
        $this->movimentar($item, 'entrada', 'Entrada da compra');

        $this->assertSame('verificar_estorno', $this->listada($compra)->integracao_estoque_status);
        $this->movimentar($item, 'saida', 'Estorno da entrada da Compra #'.$compra->id);
        $this->assertSame('estornado', $this->listada($compra)->integracao_estoque_status);
    }

    public function test_financeiro_identifica_pivo_ativa_e_conta_agrupada(): void
    {
        $compra = $this->compra();
        $conta = $this->conta($compra);
        $compra->contasPagar()->attach($conta->id, ['valor_vinculado' => '100.00']);

        $registro = $this->listada($compra);
        $this->assertSame('vinculada', $registro->integracao_financeiro_status);
        $this->assertTrue((bool) $registro->u02_pivo_conta_ativa);
        $this->assertFalse((bool) $registro->u02_legado_conta_ativa);
    }

    public function test_financeiro_identifica_vinculo_legado_sem_pivo(): void
    {
        $compra = $this->compra();
        $this->conta($compra, 'aberta', true);

        $registro = $this->listada($compra);
        $this->assertSame('vinculada', $registro->integracao_financeiro_status);
        $this->assertTrue((bool) $registro->u02_legado_conta_ativa);
        $this->assertFalse((bool) $registro->u02_pivo_conta_ativa);
    }

    public function test_conta_cancelada_nao_e_apresentada_como_vinculo_ativo(): void
    {
        $compra = $this->compra();
        $conta = $this->conta($compra, 'cancelada');
        $compra->contasPagar()->attach($conta->id, ['valor_vinculado' => '100.00']);

        $registro = $this->listada($compra);
        $this->assertSame('vinculo_cancelado', $registro->integracao_financeiro_status);
        $this->assertFalse((bool) $registro->u02_pivo_conta_ativa);
        $this->assertTrue((bool) $registro->u02_pivo_conta_cancelada);
    }
}
