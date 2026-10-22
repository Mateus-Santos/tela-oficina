<?php

namespace Tests\Feature\Financeiro;

use App\Actions\ContasPagar\VincularComprasContaPagar;
use App\Models\Compra;
use App\Models\ContaPagar;
use App\Models\FormaPagamento;
use App\Models\Fornecedor;
use App\Models\PagamentoContaPagar;
use App\Models\ParcelaContaPagar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class VincularComprasContaPagarTest extends TestCase
{
    use RefreshDatabase;

    private FormaPagamento $formaPagamento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formaPagamento =
            FormaPagamento::create([
                'nome' => 'Dinheiro Agrupamento',
                'ativo' => true,
            ]);
    }

    private function fornecedor(
        string $nome
    ): Fornecedor {
        $id = DB::table('fornecedores')
            ->insertGetId([
                'nome' => $nome,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return Fornecedor::findOrFail($id);
    }

    private function compra(
        Fornecedor $fornecedor,
        float $valor,
        string $status = Compra::STATUS_APROVADA
    ): Compra {
        return Compra::create([
            'fornecedor_id' => $fornecedor->id,
            'numero_nf' => 'NF-' . uniqid(),
            'serie_nf' => null,
            'chave_nf' => null,
            'data_emissao' => now()->toDateString(),
            'data_entrada' => now()->toDateString(),
            'valor_produtos' => $valor,
            'desconto' => 0,
            'frete' => 0,
            'outras_despesas' => 0,
            'valor_total' => $valor,
            'status' => $status,
            'observacoes' => null,
        ]);
    }

    private function conta(
        float $valor,
        ?Fornecedor $fornecedor = null,
        string $status = 'aberta'
    ): ContaPagar {
        return ContaPagar::create([
            'compra_id' => null,
            'fornecedor_id' => $fornecedor?->id,
            'nota_id' => null,
            'categoria_financeira_id' => null,
            'forma_pagamento_id' =>
                $this->formaPagamento->id,
            'descricao' => 'Conta agrupada ' . uniqid(),
            'valor' => $valor,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()
                ->addMonth()
                ->toDateString(),
            'status' => $status,
            'observacoes' => null,
        ]);
    }

    private function action(): VincularComprasContaPagar
    {
        return app(
            VincularComprasContaPagar::class
        );
    }

    public function test_vincula_varias_compras_do_mesmo_fornecedor(): void
    {
        $fornecedor = $this->fornecedor(
            'Fornecedor A'
        );

        $compra1 = $this->compra(
            $fornecedor,
            100
        );

        $compra2 = $this->compra(
            $fornecedor,
            250
        );

        $conta = $this->conta(
            350,
            $fornecedor
        );

        $conta = $this->action()->execute(
            $conta,
            [
                $compra1->id,
                $compra2->id,
            ]
        );

        $this->assertNull(
            $conta->compra_id
        );

        $this->assertSame(
            2,
            $conta->compras->count()
        );

        $this->assertDatabaseHas(
            'compra_conta_pagar',
            [
                'conta_pagar_id' =>
                    $conta->id,
                'compra_id' =>
                    $compra1->id,
                'valor_vinculado' =>
                    100,
            ]
        );

        $this->assertDatabaseHas(
            'compra_conta_pagar',
            [
                'conta_pagar_id' =>
                    $conta->id,
                'compra_id' =>
                    $compra2->id,
                'valor_vinculado' =>
                    250,
            ]
        );
    }

    public function test_vinculo_unico_mantem_compra_id_legado(): void
    {
        $fornecedor = $this->fornecedor(
            'Fornecedor B'
        );

        $compra = $this->compra(
            $fornecedor,
            180
        );

        $conta = $this->conta(
            180,
            $fornecedor
        );

        $conta = $this->action()->execute(
            $conta,
            [$compra->id]
        );

        $this->assertSame(
            $compra->id,
            $conta->compra_id
        );

        $this->assertDatabaseHas(
            'compra_conta_pagar',
            [
                'conta_pagar_id' =>
                    $conta->id,
                'compra_id' =>
                    $compra->id,
            ]
        );
    }

    public function test_define_fornecedor_da_conta_quando_estiver_vazio(): void
    {
        $fornecedor = $this->fornecedor(
            'Fornecedor C'
        );

        $compra = $this->compra(
            $fornecedor,
            120
        );

        $conta = $this->conta(120);

        $conta = $this->action()->execute(
            $conta,
            [$compra->id]
        );

        $this->assertSame(
            $fornecedor->id,
            $conta->fornecedor_id
        );
    }

    public function test_rejeita_compras_de_fornecedores_diferentes(): void
    {
        $fornecedor1 = $this->fornecedor(
            'Fornecedor D1'
        );

        $fornecedor2 = $this->fornecedor(
            'Fornecedor D2'
        );

        $compra1 = $this->compra(
            $fornecedor1,
            100
        );

        $compra2 = $this->compra(
            $fornecedor2,
            100
        );

        $conta = $this->conta(200);

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'mesmo fornecedor'
        );

        $this->action()->execute(
            $conta,
            [
                $compra1->id,
                $compra2->id,
            ]
        );
    }

    public function test_rejeita_compra_nao_aprovada(): void
    {
        $fornecedor = $this->fornecedor(
            'Fornecedor E'
        );

        $compra = $this->compra(
            $fornecedor,
            100,
            Compra::STATUS_PENDENTE
        );

        $conta = $this->conta(
            100,
            $fornecedor
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Somente compras aprovadas'
        );

        $this->action()->execute(
            $conta,
            [$compra->id]
        );
    }

    public function test_rejeita_compra_ja_vinculada_a_outra_conta(): void
    {
        $fornecedor = $this->fornecedor(
            'Fornecedor F'
        );

        $compra = $this->compra(
            $fornecedor,
            100
        );

        $conta1 = $this->conta(
            100,
            $fornecedor
        );

        $conta2 = $this->conta(
            100,
            $fornecedor
        );

        $this->action()->execute(
            $conta1,
            [$compra->id]
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'outra conta a pagar'
        );

        $this->action()->execute(
            $conta2,
            [$compra->id]
        );
    }

    public function test_rejeita_quando_soma_das_compras_difere_da_conta(): void
    {
        $fornecedor = $this->fornecedor(
            'Fornecedor G'
        );

        $compra1 = $this->compra(
            $fornecedor,
            100
        );

        $compra2 = $this->compra(
            $fornecedor,
            150
        );

        $conta = $this->conta(
            300,
            $fornecedor
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'igual à soma das compras'
        );

        $this->action()->execute(
            $conta,
            [
                $compra1->id,
                $compra2->id,
            ]
        );
    }

    public function test_bloqueia_alteracao_de_compras_com_pagamento_ativo(): void
    {
        $fornecedor = $this->fornecedor(
            'Fornecedor H'
        );

        $compra = $this->compra(
            $fornecedor,
            100
        );

        $conta = $this->conta(
            100,
            $fornecedor,
            'parcialmente_paga'
        );

        $parcela = ParcelaContaPagar::create([
            'conta_pagar_id' => $conta->id,
            'numero' => 1,
            'valor' => 100,
            'data_vencimento' =>
                now()->addMonth()->toDateString(),
        ]);

        PagamentoContaPagar::create([
            'conta_pagar_id' => $conta->id,
            'parcela_conta_pagar_id' =>
                $parcela->id,
            'valor' => 10,
            'data_pagamento' =>
                now()->toDateString(),
            'forma_pagamento' =>
                'Dinheiro',
            'forma_pagamento_id' =>
                $this->formaPagamento->id,
            'observacoes' => null,
            'estornado_em' => null,
            'motivo_estorno' => null,
        ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'possui pagamentos ativos'
        );

        $this->action()->execute(
            $conta,
            [$compra->id]
        );
    }

    public function test_permite_desvincular_antes_de_pagamentos(): void
    {
        $fornecedor = $this->fornecedor(
            'Fornecedor I'
        );

        $compra = $this->compra(
            $fornecedor,
            100
        );

        $conta = $this->conta(
            100,
            $fornecedor
        );

        $conta = $this->action()->execute(
            $conta,
            [$compra->id]
        );

        $conta = $this->action()->execute(
            $conta,
            []
        );

        $this->assertNull(
            $conta->compra_id
        );

        $this->assertSame(
            0,
            $conta->compras->count()
        );

        $this->assertDatabaseMissing(
            'compra_conta_pagar',
            [
                'conta_pagar_id' =>
                    $conta->id,
                'compra_id' =>
                    $compra->id,
            ]
        );
    }
}
