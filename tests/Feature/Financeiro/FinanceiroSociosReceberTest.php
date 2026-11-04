<?php

namespace Tests\Feature\Financeiro;

use App\Actions\FinanceiroSocios\CriarContaReceberSocio;
use App\Actions\FinanceiroSocios\EstornarRecebimentoSocio;
use App\Actions\FinanceiroSocios\RegistrarRecebimentoSocio;
use App\Models\FormaPagamento;
use App\Models\Socio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class FinanceiroSociosReceberTest extends TestCase
{
    use RefreshDatabase;

    private function conta(Socio $socio, string $valor = '100.01', int $parcelas = 3, string $origem = 'dinheiro')
    {
        return app(CriarContaReceberSocio::class)->execute($socio, [
            'devedor' => 'Pessoa de Teste',
            'descricao' => 'Empréstimo pessoal',
            'origem' => $origem,
            'identificacao_cartao' => $origem === 'cartao' ? 'Cartão final 1234' : null,
            'valor' => $valor,
            'data_operacao' => '2026-11-04',
            'primeiro_vencimento' => '2026-11-30',
            'quantidade_parcelas' => $parcelas,
        ]);
    }

    private function receber($conta, string $valor, ?int $parcelaId = null, string $meio = 'dinheiro')
    {
        $forma = FormaPagamento::query()->firstOrCreate(['nome' => $meio], ['ativo' => true]);
        return app(RegistrarRecebimentoSocio::class)->execute($conta, [
            'parcela_id' => $parcelaId ?? $conta->parcelas()->orderBy('numero')->firstOrFail()->id,
            'valor' => $valor,
            'forma_pagamento_id' => $forma->id,
            'data_recebimento' => '2026-12-01',
        ]);
    }

    public function test_divisao_em_centavos_e_vencimentos_mensais(): void
    {
        $conta = $this->conta(Socio::create(['nome' => 'Sócio A']));
        $this->assertSame(['33.34', '33.34', '33.33'], $conta->parcelas->pluck('valor')->all());
        $this->assertSame(['2026-11-30', '2026-12-30', '2027-01-30'],
            $conta->parcelas->map(fn ($p) => $p->data_vencimento->toDateString())->all());
        $this->assertDatabaseCount('contas_receber', 0);
        $this->assertDatabaseCount('recebimentos', 0);
    }

    public function test_recebimento_parcial_em_dinheiro_nao_altera_financeiro_da_empresa(): void
    {
        $conta = $this->conta(Socio::create(['nome' => 'Sócio A']), '200.00', 2);
        $r = $this->receber($conta, '40.00');
        $this->assertDatabaseHas('recebimentos_contas_receber_socios', [
            'id' => $r->id, 'meio_recebimento' => 'dinheiro', 'valor' => '40.00',
        ]);
        $this->assertSame('parcial', $conta->fresh()->status);
        $this->assertSame(160.0, $conta->fresh()->saldo);
        $this->assertDatabaseCount('recebimentos', 0);
        $this->assertDatabaseCount('retiradas_socios', 0);
    }

    public function test_quitacao_com_dois_recebimentos_e_estorno_reabre_parcela(): void
    {
        $conta = $this->conta(Socio::create(['nome' => 'Sócio A']), '100.00', 1, 'cartao');
        $this->receber($conta, '40.00', null, 'pix');
        $r = $this->receber($conta, '60.00', null, 'dinheiro');
        $this->assertSame('quitada', $conta->fresh()->status);
        $this->assertSame(0.0, $conta->fresh()->saldo);
        app(EstornarRecebimentoSocio::class)->execute($conta, $r, 'Recebimento duplicado');
        $this->assertNotNull($r->fresh()->estornado_em);
        $this->assertSame('parcial', $conta->fresh()->status);
        $this->assertSame(60.0, $conta->fresh()->saldo);
        $this->assertNull($conta->fresh()->data_quitacao);
    }

    public function test_bloqueia_valor_superior_ao_saldo(): void
    {
        $conta = $this->conta(Socio::create(['nome' => 'Sócio A']), '100.00', 2);
        $this->expectException(InvalidArgumentException::class);
        try {
            $this->receber($conta, '50.01');
        } finally {
            $this->assertDatabaseCount('recebimentos_contas_receber_socios', 0);
        }
    }

    public function test_bloqueia_parcela_de_outra_conta(): void
    {
        $socio = Socio::create(['nome' => 'Sócio A']);
        $conta = $this->conta($socio, '100.00', 1);
        $outra = $this->conta($socio, '200.00', 1);
        $this->expectException(InvalidArgumentException::class);
        $this->receber($conta, '10.00', $outra->parcelas()->firstOrFail()->id);
    }

    public function test_nao_estorna_duas_vezes(): void
    {
        $conta = $this->conta(Socio::create(['nome' => 'Sócio A']), '100.00', 1);
        $r = $this->receber($conta, '20.00');
        app(EstornarRecebimentoSocio::class)->execute($conta, $r, 'Registro incorreto');
        $this->expectException(InvalidArgumentException::class);
        app(EstornarRecebimentoSocio::class)->execute($conta, $r, 'Outra tentativa');
    }

    public function test_nao_permite_parcela_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->conta(Socio::create(['nome' => 'Sócio A']), '0.02', 3);
    }
}
