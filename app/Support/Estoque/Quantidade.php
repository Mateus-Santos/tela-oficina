<?php

namespace App\Support\Estoque;

use InvalidArgumentException;

/**
 * Representa quantidades DECIMAL(12,3) como inteiros de milésimos.
 * Não converte valores monetários nem arredonda quantidades inválidas.
 */
final class Quantidade
{
    private const LIMITE = 999999999999;

    public static function milessimos(int|float|string $valor): int
    {
        $texto = trim((string) $valor);

        if (!preg_match('/^\+?(\d+)(?:\.(\d{1,3}))?$/D', $texto, $partes)) {
            throw new InvalidArgumentException('Informe uma quantidade válida com até três casas decimais.');
        }

        $inteiro = ltrim($partes[1], '0');
        $inteiro = $inteiro === '' ? '0' : $inteiro;

        if (strlen($inteiro) > 9) {
            throw new InvalidArgumentException('A quantidade excede o limite permitido.');
        }

        $resultado = (int) $inteiro * 1000
            + (int) str_pad($partes[2] ?? '', 3, '0', STR_PAD_RIGHT);

        if ($resultado > self::LIMITE) {
            throw new InvalidArgumentException('A quantidade excede o limite permitido.');
        }

        return $resultado;
    }

    public static function formatar(int $milessimos): string
    {
        if ($milessimos < 0 || $milessimos > self::LIMITE) {
            throw new InvalidArgumentException('O saldo de estoque está fora dos limites permitidos.');
        }

        return intdiv($milessimos, 1000)
            .'.'.str_pad((string) ($milessimos % 1000), 3, '0', STR_PAD_LEFT);
    }
}
