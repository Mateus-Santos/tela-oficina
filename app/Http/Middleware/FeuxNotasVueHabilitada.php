<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class FeuxNotasVueHabilitada
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('feux_notas.central_vue3', false), 404);

        return $next($request);
    }
}
