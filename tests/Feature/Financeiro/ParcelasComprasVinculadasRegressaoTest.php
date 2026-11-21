<?php

namespace Tests\Feature\Financeiro;

use App\Actions\ContasPagar\AtualizarParcelasContaPagar;
use App\Actions\ContasPagar\VincularComprasContaPagar;
use App\Models\Compra;
use App\Models\ContaPagar;
use App\Models\FormaPagamento;
use App\Models\Fornecedor;
use App\Models\ParcelaContaPagar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class ParcelasComprasVinculadasRegressaoTest extends TestCase
{
    use RefreshDatabase;

    private function preparar(bool $vincular = true): array
    {
        $id = DB::table('fornecedores')->insertGetId([
            'nome' => 'Fornecedor teste de parcelas',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $fornecedor = Fornecedor::findOrFail($id);
        $forma = FormaPagamento::create([
            'nome' => 'Dinheiro teste de parcelas',
            'ativo' => true,
        ]);
        $compras = [];
        foreach ([100, 150] as $index => $valor) {
            $compras[] = Compra::create([
                'fornecedor_id' => $fornecedor->id,
                'numero_nf' => 'CP-REG-' . $index,
                'data_emissao' => now()->toDateString(),
                'data_entrada' => now()->toDateString(),
                'valor_produtos' => $valor,
                'desconto' => 0,
                'frete' => 0,
                'outras_despesas' => 0,
                'valor_total' => $valor,
                'status' => Compra::STATUS_APROVADA,
            ]);
        }
        $conta = ContaPagar::create([
            'fornecedor_id' => $fornecedor->id,
            'forma_pagamento_id' => $forma->id,
            'descricao' => 'Conta para teste da reparticao de parcelas',
            'valor' => 250,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()->addMonth()->toDateString(),
            'status' => 'aberta',
        ]);
        $parcela = ParcelaContaPagar::create([
            'conta_pagar_id' => $conta->id,
            'numero' => 1,
            'valor' => 250,
            'data_vencimento' => now()->addMonth()->toDateString(),
        ]);
        if ($vincular) {
            app(VincularComprasContaPagar::class)->execute(
                $conta, array_map(fn ($compra) => $compra->id, $compras)
            );
        }
        return [$conta, $parcela, $compras];
    }

    private function linhas(ParcelaContaPagar $parcela, float $primeira, float $segunda): array
    {
        return [
            [
                'id' => $parcela->id,
                'valor' => $primeira,
                'data_vencimento' => now()->addMonth()->toDateString(),
            ],
            [
                'valor' => $segunda,
                'data_vencimento' => now()->addMonths(2)->toDateString(),
            ],
        ];
    }

    public function test_pode_acrescentar_parcela_preservando_total_das_compras(): void
    {
        [$conta, $parcela] = $this->preparar();
        app(AtualizarParcelasContaPagar::class)->execute(
            $conta, $this->linhas($parcela, 100, 150)
        );
        $conta->refresh();
        $this->assertEqualsWithDelta(250, (float) $conta->valor, 0.001);
        $this->assertCount(2, $conta->parcelas);
        $this->assertEqualsWithDelta(250, (float) $conta->parcelas()->sum('valor'), 0.001);
        $this->assertEqualsWithDelta(250, (float) $conta->compras()->sum('compra_conta_pagar.valor_vinculado'), 0.001);
    }

    public function test_rejeita_aumento_de_total_e_nao_grava_alteracao_parcial(): void
    {
        [$conta, $parcela] = $this->preparar();
        try {
            app(AtualizarParcelasContaPagar::class)->execute(
                $conta, $this->linhas($parcela, 100, 175)
            );
            $this->fail('Deveria bloquear divergencia entre parcelas e compras.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('total das parcelas', $e->getMessage());
        }
        $conta->refresh();
        $this->assertEqualsWithDelta(250, (float) $conta->valor, 0.001);
        $this->assertCount(1, $conta->parcelas);
        $this->assertEqualsWithDelta(250, (float) $conta->parcelas->first()->valor, 0.001);
    }

    public function test_preserva_possibilidade_de_ajustar_total_sem_compras(): void
    {
        [$conta, $parcela] = $this->preparar(false);
        app(AtualizarParcelasContaPagar::class)->execute(
            $conta, $this->linhas($parcela, 100, 175)
        );
        $this->assertEqualsWithDelta(275, (float) $conta->fresh()->valor, 0.001);
    }

    public function test_vinculo_legado_tambem_impede_alterar_total(): void
    {
        [$conta, $parcela, $compras] = $this->preparar(false);
        $conta->update(['compra_id' => $compras[0]->id]);
        try {
            app(AtualizarParcelasContaPagar::class)->execute(
                $conta, $this->linhas($parcela, 100, 175)
            );
            $this->fail('Vinculo legado deveria bloquear mudanca do total.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('total das parcelas', $e->getMessage());
        }
        $this->assertEqualsWithDelta(250, (float) $conta->fresh()->valor, 0.001);
    }
}
