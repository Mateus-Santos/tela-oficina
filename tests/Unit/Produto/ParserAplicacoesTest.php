<?php

namespace Tests\Unit\Produto;

use App\Services\Produto\ParserAplicacoes;
use Tests\TestCase;

class ParserAplicacoesTest extends TestCase
{
    public function test_parser_preserva_campos_tecnicos_da_aplicacao(): void
    {
        $texto =
            "Montadora\tVeículo\tMês/Ano\tModelo\tMotor\tComp\tVál.\tCil.\tCombustível\tComplemento\n"
            ."PEUGEOT\t106\t7/ 1995 até ...\t\t1.4\tTU3MC\t8\t4\tGASOLINA\tVELA";

        $aplicacoes = app(
            ParserAplicacoes::class
        )->processar($texto);

        $this->assertCount(
            1,
            $aplicacoes
        );

        $aplicacao =
            $aplicacoes[0];

        $this->assertSame(
            'PEUGEOT',
            $aplicacao['montadora']
        );

        $this->assertSame(
            '106',
            $aplicacao['veiculo']
        );

        $this->assertSame(
            '1.4',
            $aplicacao['motor']
        );

        $this->assertSame(
            'TU3MC',
            $aplicacao['comp']
        );

        $this->assertSame(
            '8',
            $aplicacao['valvulas']
        );

        $this->assertSame(
            '4',
            $aplicacao['cilindros']
        );

        $this->assertArrayNotHasKey(
            'cilindrada',
            $aplicacao
        );
    }
}
