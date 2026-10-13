<?php

namespace App\Http\Controllers;

use App\Models\MovimentacaoEstoque;
use App\Models\NotasItem;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;

class MovimentacaoEstoqueController extends Controller
{
    public function index(Request $request)
    {
        $query = MovimentacaoEstoque::query()
            ->with([
                'produto.marcaRelacionada',
                'produto.imagens',
                'usuario:id,pessoa_id',
                'usuario.pessoa:id,nome',
                'origem' => function (MorphTo $morphTo) {
                    $morphTo->morphWith([
                        NotasItem::class => [
                            'nota:id,status',
                        ],
                    ]);
                },
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($request->filled('produto')) {
            $produto = trim(
                (string) $request->input('produto')
            );

            $query->whereHas(
                'produto',
                function ($produtoQuery) use ($produto) {
                    $produtoQuery->where(
                        function ($busca) use ($produto) {
                            $busca
                                ->where(
                                    'nome',
                                    'like',
                                    "%{$produto}%"
                                )
                                ->orWhere(
                                    'codigo_fabricante',
                                    'like',
                                    "%{$produto}%"
                                )
                                ->orWhere(
                                    'codigo_barras',
                                    'like',
                                    "%{$produto}%"
                                );
                        }
                    );
                }
            );
        }

        /*
         * Mantém compatibilidade com links antigos
         * que eventualmente utilizem produto_id.
         */
        if ($request->filled('produto_id')) {
            $query->where(
                'produto_id',
                $request->integer('produto_id')
            );
        }

        if ($request->filled('tipo')) {
            $query->where(
                'tipo',
                $request->input('tipo')
            );
        }

        if ($request->filled('data_inicio')) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->input('data_inicio')
            );
        }

        if ($request->filled('data_fim')) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->input('data_fim')
            );
        }

        $movimentacoes = $query
            ->paginate(20)
            ->withQueryString();

        return view(
            'estoque.movimentacoes',
            compact('movimentacoes')
        );
    }
}
