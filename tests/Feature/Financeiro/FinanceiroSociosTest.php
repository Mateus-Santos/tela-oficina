<?php

namespace Tests\Feature\Financeiro;

use App\Actions\FinanceiroSocios\CriarContaPagarSocio;
use App\Actions\FinanceiroSocios\EstornarPagamentoSocio;
use App\Actions\FinanceiroSocios\EstornarRetiradaSocio;
use App\Actions\FinanceiroSocios\RegistrarPagamentoSocio;
use App\Actions\FinanceiroSocios\RegistrarRetiradaSocio;
use App\Models\Socio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class FinanceiroSociosTest extends TestCase
{
    use RefreshDatabase;

    private function conta(Socio $socio, string $valor = '300.00', int $qtd = 3)
    {
        return app(CriarContaPagarSocio::class)->execute($socio, [
            'descricao' => 'Financiamento pessoal',
            'credor' => 'Banco de teste',
            'categoria' => 'Financiamento',
            'valor' => $valor,
            'data_emissao' => '2026-11-01',
            'primeiro_vencimento' => '2026-11-30',
            'quantidade_parcelas' => $qtd,
        ]);
    }

    private function pagamento($conta, string $valor, string $origem, ?int $parcelaId = null)
    {
        return app(RegistrarPagamentoSocio::class)->execute($conta, [
            'parcela_id' => $parcelaId ?? $conta->parcelas()->orderBy('numero')->firstOrFail()->id,
            'valor' => $valor,
            'data_pagamento' => '2026-11-04',
            'origem' => $origem,
        ]);
    }

    public function test_parcelas_somam_exatamente_o_valor_original_em_centavos(): void
    {
        $socio = Socio::create(['nome' => 'Socio A']);
        $conta = $this->conta($socio, '100.01', 3);
        $this->assertSame('100.01', $conta->valor);
        $this->assertSame(['33.34', '33.34', '33.33'], $conta->parcelas->pluck('valor')->all());
        $this->assertSame(['2026-11-30', '2026-12-30', '2027-01-30'],
            $conta->parcelas->map(fn ($p) => $p->data_vencimento->toDateString())->all());
    }

    public function test_pagamento_pessoal_nao_gera_retirada_empresa(): void
    {
        $socio = Socio::create(['nome' => 'Socio A']);
        $conta = $this->conta($socio);
        $pagamento = $this->pagamento($conta, '40.00', 'pessoal');

        $this->assertDatabaseHas('pagamentos_contas_pagar_socios', ['id' => $pagamento->id, 'origem' => 'pessoal']);
        $this->assertDatabaseCount('retiradas_socios', 0);
        $this->assertSame('parcialmente_paga', $conta->fresh()->status);
    }

    public function test_pagamento_empresa_gera_uma_unica_retirada_vinculada(): void
    {
        $socio = Socio::create(['nome' => 'Socio A']);
        $conta = $this->conta($socio);
        $pagamento = $this->pagamento($conta, '100.00', 'empresa');

        $this->assertDatabaseHas('retiradas_socios', [
            'socio_id' => $socio->id,
            'pagamento_conta_pagar_socio_id' => $pagamento->id,
            'tipo' => 'pagamento_divida',
            'valor' => '100.00',
        ]);
        $this->assertDatabaseCount('retiradas_socios', 1);
        $this->assertSame('parcialmente_paga', $conta->fresh()->status);
    }

    public function test_estorno_empresa_reverte_retirada_e_saldo_na_mesma_transacao(): void
    {
        $socio = Socio::create(['nome' => 'Socio A']);
        $conta = $this->conta($socio);
        $pagamento = $this->pagamento($conta, '100.00', 'empresa');

        app(EstornarPagamentoSocio::class)->execute($conta, $pagamento, 'Lancamento incorreto');

        $this->assertNotNull($pagamento->fresh()->estornado_em);
        $this->assertNotNull($pagamento->fresh()->retirada->estornado_em);
        $this->assertSame('aberta', $conta->fresh()->status);
        $this->assertSame(0, $socio->retiradasAtivas()->count());
        $this->assertSame(300.0, $conta->fresh()->saldo);
    }

    public function test_nao_aceita_pagamento_superior_ao_saldo_da_parcela(): void
    {
        $socio = Socio::create(['nome' => 'Socio A']);
        $conta = $this->conta($socio);
        $this->expectException(InvalidArgumentException::class);
        try {
            $this->pagamento($conta, '100.01', 'empresa');
        } finally {
            $this->assertDatabaseCount('pagamentos_contas_pagar_socios', 0);
            $this->assertDatabaseCount('retiradas_socios', 0);
        }
    }

    public function test_nao_aceita_parcela_de_outra_conta(): void
    {
        $socio = Socio::create(['nome' => 'Socio A']);
        $conta = $this->conta($socio);
        $outra = $this->conta($socio);
        $this->expectException(InvalidArgumentException::class);
        $this->pagamento($conta, '10.00', 'pessoal', $outra->parcelas()->firstOrFail()->id);
    }

    public function test_retirada_livre_e_estorno_manual_sem_divida(): void
    {
        $socio = Socio::create(['nome' => 'Socio A']);
        $retirada = app(RegistrarRetiradaSocio::class)->execute($socio, [
            'tipo' => 'retirada_livre',
            'valor' => '125.50',
            'data_retirada' => '2026-11-04',
        ]);
        $this->assertSame('125.50', $retirada->valor);
        app(EstornarRetiradaSocio::class)->execute($socio, $retirada, 'Duplicidade do cadastro');
        $this->assertNotNull($retirada->fresh()->estornado_em);
    }

    public function test_nao_estorna_retirada_vinculada_sem_estornar_pagamento(): void
    {
        $socio = Socio::create(['nome' => 'Socio A']);
        $conta = $this->conta($socio);
        $pagamento = $this->pagamento($conta, '100.00', 'empresa');
        $this->expectException(InvalidArgumentException::class);
        app(EstornarRetiradaSocio::class)->execute($socio, $pagamento->retirada, 'Teste de integridade');
    }
}
