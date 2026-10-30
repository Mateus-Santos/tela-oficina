<?php

namespace Tests\Unit\Services\Nota;

use App\Services\Nota\CalculadorTotaisNota;
use PHPUnit\Framework\TestCase;

class CalculadorTotaisNotaTest extends TestCase
{
    private CalculadorTotaisNota $calculador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculador =
            new CalculadorTotaisNota();
    }

    public function test_calcula_item_com_desconto(): void
    {
        $resultado =
            $this->calculador
                ->calcularItem(
                    1,
                    515,
                    15
                );

        $this->assertSame(
            515.0,
            $resultado['subtotal']
        );

        $this->assertSame(
            15.0,
            $resultado['desconto']
        );

        $this->assertSame(
            500.0,
            $resultado['total']
        );
    }

    public function test_calcula_total_de_varios_itens(): void
    {
        $resultado =
            $this->calculador
                ->calcularItens([
                    [
                        'quantidade' => 2,
                        'valor_unitario' => 100,
                        'desconto' => 10,
                    ],
                    [
                        'quantidade' => 1,
                        'valor_unitario' => 315,
                        'desconto' => 5,
                    ],
                ]);

        $this->assertSame(
            515.0,
            $resultado['subtotal']
        );

        $this->assertSame(
            15.0,
            $resultado['desconto']
        );

        $this->assertSame(
            500.0,
            $resultado['total']
        );
    }

    public function test_total_nunca_fica_negativo(): void
    {
        $resultado =
            $this->calculador
                ->calcularItem(
                    1,
                    10,
                    15
                );

        $this->assertSame(
            10.0,
            $resultado['subtotal']
        );

        $this->assertSame(
            15.0,
            $resultado['desconto']
        );

        $this->assertSame(
            0.0,
            $resultado['total']
        );
    }

    public function test_trabalha_com_valores_decimais(): void
    {
        $resultado =
            $this->calculador
                ->calcularItem(
                    3,
                    '33.33',
                    '9.99'
                );

        $this->assertSame(
            99.99,
            $resultado['subtotal']
        );

        $this->assertSame(
            9.99,
            $resultado['desconto']
        );

        $this->assertSame(
            90.0,
            $resultado['total']
        );
    }
}
