<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Nota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotaContaReceberBuscaController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
        ]);

        $busca = trim($dados['q'] ?? '');
        $clienteId = $dados['cliente_id'] ?? null;

        if ($busca === '' && !$clienteId) {
            return response()->json(['data' => []]);
        }

        $notas = Nota::query()
            ->with('cliente.pessoa')
            ->where('status', 'Finalizado')
            ->whereNotNull('cliente_id')
            ->whereDoesntHave('contaReceber')
            ->when($clienteId, function ($query) use ($clienteId) {
                $query->where('cliente_id', $clienteId);
            })
            ->when($busca !== '', function ($query) use ($busca) {
                if (ctype_digit($busca)) {
                    $query->where('id', (int) $busca);
                } else {
                    $query->whereHas('cliente.pessoa', function ($query) use ($busca) {
                        $query->where('nome', 'like', "%{$busca}%");
                    });
                }
            })
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        return response()->json([
            'data' => $notas->map(function (Nota $nota) {
                return [
                    'id' => $nota->id,
                    'numero' => str_pad((string) $nota->id, 6, '0', STR_PAD_LEFT),
                    'cliente_id' => $nota->cliente_id,
                    'cliente_nome' => $nota->cliente?->pessoa?->nome ?? 'Cliente sem nome',
                    'total' => (float) $nota->total,
                    'tipo' => $nota->tipo,
                    'status' => $nota->status,
                ];
            })->values(),
        ]);
    }
}
