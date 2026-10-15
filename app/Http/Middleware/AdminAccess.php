<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAccess
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $usuario = $request->user();

        abort_unless(
            $usuario
            && $usuario->isAdmin(),
            403,
            'Acesso não autorizado.'
        );

        return $next($request);
    }
}
