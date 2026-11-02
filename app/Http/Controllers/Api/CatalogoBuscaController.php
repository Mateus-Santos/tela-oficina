<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Produto\BuscarReferenciasCatalogo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Retorna exclusivamente referencias sem cadastro operacional. */
class CatalogoBuscaController extends Controller
{
    public function __invoke(Request $request, BuscarReferenciasCatalogo $buscaCatalogo): JsonResponse
    {
        $dados = $request->validate(['q' => ['required', 'string', 'max:100']]);
        return response()->json([
            'data' => $buscaCatalogo->buscar((string) $dados['q'], 15),
        ]);
    }
}
