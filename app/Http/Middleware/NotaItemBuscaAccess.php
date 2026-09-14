<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotaItemBuscaAccess
{
    /**
     * Permite acesso à busca de itens somente para
     * usuários autenticados e autorizados a trabalhar
     * com notas.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Não autenticado.',
            ], 401);
        }

        $usuario = auth()->user();

        if ($usuario->permitions == 2) {
            return response()->json([
                'message' => 'Você não possui permissão para acessar esta busca.',
            ], 403);
        }

        return $next($request);
    }
}
