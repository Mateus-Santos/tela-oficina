<?php

namespace Tests\Feature\Compras;

use App\Actions\Compra\GerarContaPagarCompra;
use App\Models\Compra;
use App\Models\Fornecedor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class GerarContaPagarCompraTest extends TestCase
{
    use RefreshDatabase;

    private function fornecedor(
        string $nome = 'Fornecedor Teste'
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
        float $valor = 300
    ): Compra {
        return Compra::create([
            'fornecedor_id' =>
                $fornecedor->id,
            'numero_nf' =>
                'NF-' . uniqid(),
            'serie_nf' => null,
            'chave_nf' => null,
            'data_emissao' =>
                now()->toDateString(),
            'data_entrada' =>
                now()->toDateString(),
            'valor_produtos' =>
                $valor,
            'desconto' => 0,
            'frete' => 0,
            'outras_despesas' => 0,
            'valor_total' =>
                $valor,
            'status' =>
                Compra::STATUS_APROVADA,
            'observacoes' => null,
        ]);
    }

    private function action(): GerarContaPagarCompra
    {
        return app(
            GerarContaPagarCompra::class
        );
    }

    public function test_gera_conta_e_registra_vinculo_na_pivo(): void
    {
        $fornecedor = $this->fornecedor();

        $compra = $this->compra(
            $fornecedor,
            300
        );

        $conta = $this->action()->execute(
            $compra,
            [
                [
                    'numero' => 1,
                    'valor' => 150,
                    'data_vencimento' =>
                        now()
                            ->addMonth()
                            ->toDateString(),
                ],
                [
                    'numero' => 2,
                    'valor' => 150,
                    'data_vencimento' =>
                        now()
                            ->addMonths(2)
                            ->toDateString(),
                ],
            ]
        );

        $this->assertSame(
            $compra->id,
            $conta->compra_id
        );

        $this->assertSame(
            $fornecedor->id,
            $conta->fornecedor_id
        );

        $this->assertCount(
            2,
            $conta->parcelas
        );

        $this->assertCount(
            1,
            $conta->compras
        );

        $this->assertTrue(
            $conta->compras
                ->first()
                ->is($compra)
        );

        $this->assertDatabaseHas(
            'compra_conta_pagar',
            [
                'conta_pagar_id' =>
                    $conta->id,
                'compra_id' =>
                    $compra->id,
                'valor_vinculado' =>
                    300,
            ]
        );
    }

    public function test_pivo_impede_gerar_segunda_conta_mesmo_sem_compra_id_legado(): void
    {
        $fornecedor = $this->fornecedor();

        $compra = $this->compra(
            $fornecedor,
            100
        );

        $conta = $this->action()->execute(
            $compra,
            [
                [
                    'numero' => 1,
                    'valor' => 100,
                    'data_vencimento' =>
                        now()
                            ->addMonth()
                            ->toDateString(),
                ],
            ]
        );

        /*
         * Simula uma conta agrupada, situação
         * em que compra_id legado fica null,
         * mas a pivô continua sendo a verdade.
         */
        $conta->update([
            'compra_id' => null,
        ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'já possui uma conta a pagar vinculada'
        );

        $this->action()->execute(
            $compra,
            [
                [
                    'numero' => 1,
                    'valor' => 100,
                    'data_vencimento' =>
                        now()
                            ->addMonth()
                            ->toDateString(),
                ],
            ]
        );
    }

    public function test_compra_encontra_conta_pela_nova_relacao(): void
    {
        $fornecedor = $this->fornecedor();

        $compra = $this->compra(
            $fornecedor,
            125
        );

        $conta = $this->action()->execute(
            $compra,
            [
                [
                    'numero' => 1,
                    'valor' => 125,
                    'data_vencimento' =>
                        now()
                            ->addMonth()
                            ->toDateString(),
                ],
            ]
        );

        $compra = $compra->fresh(
            'contasPagar'
        );

        $this->assertCount(
            1,
            $compra->contasPagar
        );

        $this->assertTrue(
            $compra->contasPagar
                ->first()
                ->is($conta)
        );
    }

    public function test_valor_invalido_faz_rollback_da_conta_e_do_vinculo(): void
    {
        $fornecedor = $this->fornecedor();

        $compra = $this->compra(
            $fornecedor,
            300
        );

        try {
            $this->action()->execute(
                $compra,
                [
                    [
                        'numero' => 1,
                        'valor' => 100,
                        'data_vencimento' =>
                            now()
                                ->addMonth()
                                ->toDateString(),
                    ],
                ]
            );

            $this->fail(
                'Era esperada uma exceção.'
            );
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString(
                'exatamente igual',
                $e->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'contas_pagar',
            0
        );

        $this->assertDatabaseCount(
            'compra_conta_pagar',
            0
        );
    }
}
