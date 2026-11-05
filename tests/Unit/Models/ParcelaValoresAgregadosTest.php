<?php

namespace Tests\Unit\Models;

use App\Models\ParcelaContaPagar;
use App\Models\ParcelaContaReceber;
use PHPUnit\Framework\TestCase;

class ParcelaValoresAgregadosTest extends TestCase
{
    public function test_parcela_pagar_usa_soma_do_with_sum_sem_consulta_extra(): void
    {
        $parcela = new ParcelaContaPagar();
        $parcela->setRawAttributes(['valor' => '100.00', 'valor_pago' => '35.45']);

        $this->assertSame(35.45, $parcela->valor_pago);
        $this->assertSame(64.55, $parcela->saldo);
    }

    public function test_parcela_receber_usa_soma_do_with_sum_sem_consulta_extra(): void
    {
        $parcela = new ParcelaContaReceber();
        $parcela->setRawAttributes(['valor' => '100.00', 'valor_recebido' => '40.15']);

        $this->assertSame(40.15, $parcela->valor_recebido);
        $this->assertSame(59.85, $parcela->saldo);
    }

    public function test_soma_nula_do_with_sum_e_tratada_como_zero(): void
    {
        $pagar = new ParcelaContaPagar();
        $pagar->setRawAttributes(['valor' => '50.00', 'valor_pago' => null]);
        $receber = new ParcelaContaReceber();
        $receber->setRawAttributes(['valor' => '50.00', 'valor_recebido' => null]);

        $this->assertSame(0.0, $pagar->valor_pago);
        $this->assertSame(0.0, $receber->valor_recebido);
        $this->assertSame(50.0, $pagar->saldo);
        $this->assertSame(50.0, $receber->saldo);
    }

    public function test_relacao_precarregada_preserva_precedencia_atual(): void
    {
        $pagar = new ParcelaContaPagar();
        $pagar->setRawAttributes(['valor_pago' => '99.00']);
        $pagar->setRelation('pagamentosAtivos', collect([['valor' => '12.50']]));

        $receber = new ParcelaContaReceber();
        $receber->setRawAttributes(['valor_recebido' => '99.00']);
        $receber->setRelation('recebimentosAtivos', collect([['valor' => '25.75']]));

        $this->assertSame(12.5, $pagar->valor_pago);
        $this->assertSame(25.75, $receber->valor_recebido);
    }
}
