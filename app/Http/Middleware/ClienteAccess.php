<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClienteAccess
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        abort_unless(
            $request->user()?->isCliente() === true,
            403,
            'Acesso não autorizado.'
        );

        return $next($request);
    }
}
