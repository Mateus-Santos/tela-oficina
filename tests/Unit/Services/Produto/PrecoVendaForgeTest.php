<?php

namespace Tests\Unit\Services\Produto;

use App\Support\Catalogo\PrecoVendaForge;
use PHPUnit\Framework\TestCase;

class PrecoVendaForgeTest extends TestCase
{
    public function test_reproduz_degraus_de_cinco_reais_do_forge(): void
    {
        foreach ([
            '10.98' => '10.00',
            '12.46' => '15.00',
            '20.00' => '20.00',
            '21.34' => '25.00',
            '99.99' => '100.00',
            '0.01' => '5.00',
            '5.99' => '5.00',
            '1.234,56' => '1235.00',
            '12,46' => '15.00',
        ] as $bruto => $esperado) {
            $this->assertSame($esperado, PrecoVendaForge::arredondar($bruto), $bruto);
        }
    }

    public function test_valores_ausentes_ou_invalidos_nao_geram_preco(): void
    {
        foreach ([null, '', 'invalido', '-15', 'R$ texto'] as $valor) {
            $this->assertNull(PrecoVendaForge::arredondar($valor));
        }
        $this->assertSame('0.00', PrecoVendaForge::arredondar('0.00'));
    }

    public function test_importacao_monotonica_nao_abaixa_preco(): void
    {
        $this->assertSame('25.00', PrecoVendaForge::maior('25.00', '20.00'));
        $this->assertSame('25.00', PrecoVendaForge::maior('25.00', '25.00'));
        $this->assertSame('30.00', PrecoVendaForge::maior('25.00', '30.00'));
        $this->assertSame('25.00', PrecoVendaForge::maior('25.00', null));
        $this->assertSame('25.00', PrecoVendaForge::maior('25.00', '0.00'));
    }
}
