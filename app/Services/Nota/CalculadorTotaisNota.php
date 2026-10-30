<?php

namespace App\Services\Nota;

final class CalculadorTotaisNota
{
    public function calcularItem(
        mixed $quantidade,
        mixed $valorUnitario,
        mixed $desconto = 0
    ): array {
        $quantidadeNormalizada = max(
            0,
            (float) $quantidade
        );

        $valorUnitarioCentavos =
            $this->paraCentavos(
                $valorUnitario
            );

        $descontoCentavos = max(
            0,
            $this->paraCentavos(
                $desconto
            )
        );

        $subtotalCentavos = (int) round(
            $quantidadeNormalizada
            * $valorUnitarioCentavos
        );

        $totalCentavos = max(
            0,
            $subtotalCentavos
            - $descontoCentavos
        );

        return [
            'subtotal' =>
                $this->paraDecimal(
                    $subtotalCentavos
                ),

            'desconto' =>
                $this->paraDecimal(
                    $descontoCentavos
                ),

            'total' =>
                $this->paraDecimal(
                    $totalCentavos
                ),
        ];
    }

    public function calcularItens(
        iterable $itens
    ): array {
        $subtotalCentavos = 0;
        $descontoCentavos = 0;

        foreach ($itens as $item) {
            $dados = $this->calcularItem(
                data_get(
                    $item,
                    'quantidade',
                    0
                ),
                data_get(
                    $item,
                    'valor_unitario',
                    0
                ),
                data_get(
                    $item,
                    'desconto',
                    0
                )
            );

            $subtotalCentavos +=
                $this->paraCentavos(
                    $dados['subtotal']
                );

            $descontoCentavos +=
                $this->paraCentavos(
                    $dados['desconto']
                );
        }

        $totalCentavos = max(
            0,
            $subtotalCentavos
            - $descontoCentavos
        );

        return [
            'subtotal' =>
                $this->paraDecimal(
                    $subtotalCentavos
                ),

            'desconto' =>
                $this->paraDecimal(
                    $descontoCentavos
                ),

            'total' =>
                $this->paraDecimal(
                    $totalCentavos
                ),
        ];
    }

    public function paraCentavos(
        mixed $valor
    ): int {
        return (int) round(
            (float) $valor * 100
        );
    }

    private function paraDecimal(
        int $centavos
    ): float {
        return $centavos / 100;
    }
}
