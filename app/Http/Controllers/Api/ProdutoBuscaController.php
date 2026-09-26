<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProdutoBuscaController extends Controller
{
    /**
     * Busca produtos ativos por nome, descrição,
     * código de barras ou código do fabricante.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'q' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $busca = trim($dados['q'] ?? '');

        if ($busca === '') {
            return response()->json([
                'data' => [],
            ]);
        }

        $produtos = Produto::query()
            ->select([
                'id',
                'nome',
                'descricao',
                'preco_uni',
                'codigo_fabricante',
                'codigo_barras',
                'marca_id',
            ])
            ->where('status', true)
            ->where(function ($query) use ($busca) {
                $query
                    ->where('nome', 'like', "%{$busca}%")
                    ->orWhere(
                        'descricao',
                        'like',
                        "%{$busca}%"
                    )
                    ->orWhere(
                        'codigo_fabricante',
                        'like',
                        "%{$busca}%"
                    )
                    ->orWhere(
                        'codigo_barras',
                        'like',
                        "%{$busca}%"
                    );
            })
            ->orderBy('nome')
            ->limit(15)
            ->get();

        return response()->json([
            'data' => $produtos->map(function ($produto) {
                return [
                    'id' => $produto->id,
                    'nome' => $produto->nome,
                    'descricao' => $produto->descricao,
                    'preco' => (float) $produto->preco_uni,
                    'codigo_fabricante' => $produto->codigo_fabricante,
                    'codigo_barras' => $produto->codigo_barras,
                    'marca' => $produto->marcaRelacionada?->nome,
                ];
            })->values(),
        ]);
    }
}
