<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BuscarProdutosOrcamentoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'busca' => ['required', 'string', 'min:2', 'max:60'],
        ]);
        $busca = trim($dados['busca']);

        if (mb_strlen($busca) < 2) {
            return response()->json(['data' => []]);
        }

        // Endpoint público: publica somente ID, nome, marca e referência
        // do fabricante. Não expõe código interno, custo, estoque ou fornecedor.
        $produtos = Produto::query()
            ->select(['id', 'nome', 'marca_id', 'codigo_fabricante'])
            ->with('marcaRelacionada:id,nome')
            ->where('status', true)
            ->where('quantidade', '>', 0)
            ->where(function (Builder $query) use ($busca): void {
                $query->where('nome', 'like', '%'.$busca.'%')
                    ->orWhere('codigo_fabricante', 'like', '%'.$busca.'%')
                    ->orWhereHas('marcaRelacionada', function (Builder $marca) use ($busca): void {
                        $marca->where('nome', 'like', '%'.$busca.'%');
                    });
            })
            ->orderBy('nome')
            ->orderBy('id')
            ->limit(12)
            ->get();

        return response()->json([
            'data' => $produtos->map(static fn (Produto $produto): array => [
                'id' => $produto->id,
                'nome' => $produto->nome,
                'marca' => $produto->marcaRelacionada?->nome,
                'referencia' => trim((string) $produto->codigo_fabricante) ?: null,
            ])->values()->all(),
        ])->header('Cache-Control', 'no-store');
    }
}
