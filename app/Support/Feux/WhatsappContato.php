<?php

namespace App\Support\Feux;

final class WhatsappContato
{
    /** Retorna somente URLs com DDD brasileiro + telefone numérico válido. */
    public static function url(?string $telefone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $telefone);
        if (str_starts_with($digits, '55') && in_array(strlen($digits), [12, 13], true)) {
            $nacional = substr($digits, 2);
        } elseif (in_array(strlen($digits), [10, 11], true)) {
            $nacional = $digits;
        } else {
            return null;
        }
        $ddd = (int) substr($nacional, 0, 2);
        if ($ddd < 11 || $ddd > 99 || !in_array(strlen($nacional), [10, 11], true)) {
            return null;
        }
        return 'https://wa.me/55'.$nacional;
    }
}
