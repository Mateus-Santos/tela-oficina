<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClienteContaReceberBuscaController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'id' => ['nullable', 'integer', 'exists:clientes,id'],
        ]);

        $busca = trim($dados['q'] ?? '');
        $id = $dados['id'] ?? null;

        if ($busca === '' && !$id) {
            return response()->json(['data' => []]);
        }

        $clientes = Cliente::query()
            ->with('pessoa')
            ->whereHas('pessoa')
            ->when($id, fn ($query) => $query->where('id', $id))
            ->when($busca !== '', function ($query) use ($busca) {
                $query->whereHas('pessoa', fn ($query) => $query->where('nome', 'like', "%{$busca}%"));
            })
            ->limit(15)
            ->get();

        return response()->json([
            'data' => $clientes->map(fn (Cliente $cliente) => [
                'id' => $cliente->id,
                'nome' => $cliente->pessoa?->nome ?? 'Cliente sem nome',
            ])->values(),
        ]);
    }
}
