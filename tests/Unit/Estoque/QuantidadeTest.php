<?php

namespace Tests\Unit\Estoque;

use App\Support\Estoque\Quantidade;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class QuantidadeTest extends TestCase
{
    public function test_converte_milesimos_sem_arredondamento(): void
    {
        $this->assertSame(1001, Quantidade::milessimos('1.001'));
        $this->assertSame(2000, Quantidade::milessimos('2'));
        $this->assertSame('1.001', Quantidade::formatar(1001));
        $this->assertSame('0.001', Quantidade::formatar(1));
        $this->assertSame('999999999.999', Quantidade::formatar(999999999999));
    }

    public function test_rejeita_quantidade_com_precisao_superior_a_tres_casas(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Quantidade::milessimos('1.0009');
    }

    public function test_rejeita_quantidade_acima_do_limite_do_banco(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Quantidade::milessimos('1000000000.000');
    }
}
