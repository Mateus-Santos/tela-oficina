<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckIfUserIsBlocked
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && ! (bool) $usuario->status) {
            // API com Sanctum nao depende de sessao web.
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Conta bloqueada.',
                ], 403);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Conta bloqueada.',
                ], 403);
            }

            return redirect()
                ->route('login')
                ->with(
                    'status',
                    'Sua conta está bloqueada. Entre em contato com Administrador para solucionar seu problema.'
                );
        }

        return $next($request);
    }
}
