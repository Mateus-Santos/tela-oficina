<?php

namespace App\Services\Marketing;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/** IP e dado pessoal: a coleta e opcional e nunca e gravada em texto claro. */
final class PrivacidadeLead
{
    public function ipCriptografado(Request $request): ?string
    {
        if (config('marketing.captura_ip_habilitada', false) !== true) {
            return null;
        }

        // Request::ip() considera somente os proxies confiaveis configurados
        // no bootstrap do aplicativo. Nao consultar geolocalizacao externa.
        $ip = $request->ip();
        if (!is_string($ip) || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        return Crypt::encryptString($ip);
    }
}
