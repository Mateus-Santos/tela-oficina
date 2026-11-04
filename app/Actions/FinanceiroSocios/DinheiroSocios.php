<?php

namespace App\Actions\FinanceiroSocios;

use InvalidArgumentException;

final class DinheiroSocios
{
    public static function centavos(string|int|float $valor): int
    {
        $texto = trim((string) $valor);

        if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $texto)) {
            throw new InvalidArgumentException('Valor monetario invalido. Use ate duas casas decimais.');
        }

        [$inteiro, $decimal] = array_pad(explode('.', $texto, 2), 2, '');
        return (int) $inteiro * 100 + (int) str_pad($decimal, 2, '0');
    }

    public static function decimal(int $centavos): string
    {
        return intdiv($centavos, 100) . '.' . str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }
}
