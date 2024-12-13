<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && (auth()->user()->colaborador == 1)) {
            return $next($request);
        }
        abort(403, 'Acesso não autorizado.');
    }
}
