<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClienteAccess
{

    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && (auth()->user()->cliente == 1)) {
            return $next($request);
        }
        dd('Acesso Negado, você não é um cliente. Faça o seu cadastro.');
    }
}
