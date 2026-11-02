<?php

namespace App\Support\Catalogo;

final class PrecoVendaForge
{
    /**
     * Mesmo criterio de pricing.arredondar_preco_venda_interno do Forge:
     * descarta centavos e sobe a parte inteira ao proximo multiplo de 5.
     * Nao use round() nem ceil() diretamente sobre o valor decimal.
     */
    public static function arredondar(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = preg_replace('/\s+/u', '', str_replace('R$', '', (string) $valor));
        if ($texto === null || $texto === '') {
            return null;
        }

        // Convencao do Forge: 1.234,56 -> 1234.56; 12.46 -> 12.46.
        if (str_contains($texto, ',')) {
            $texto = str_replace('.', '', $texto);
            $texto = str_replace(',', '.', $texto);
        }

        if (!preg_match('/^([0-9]+)(?:\.([0-9]+))?$/D', $texto, $partes)) {
            return null;
        }

        // Guardrail contra overflow de inteiro no PHP.
        if (strlen(ltrim($partes[1], '0')) > 14) {
            return null;
        }

        $inteiro = (int) $partes[1];
        $centavosPositivos = isset($partes[2]) && strpbrk($partes[2], '123456789') !== false;
        if ($inteiro === 0) {
            $ajustado = $centavosPositivos ? 5 : 0;
        } elseif ($inteiro % 5 === 0) {
            $ajustado = $inteiro;
        } else {
            $ajustado = (int) (intdiv($inteiro, 5) + 1) * 5;
        }

        return $ajustado.'.00';
    }

    /** Compara apenas os valores ja arredondados em degraus inteiros. */
    public static function maior(?string $anterior, ?string $novo): ?string
    {
        if ($novo === null || (float) $novo <= 0) {
            return $anterior;
        }
        if ($anterior === null || (float) $novo > (float) $anterior) {
            return $novo;
        }
        return $anterior;
    }
}
