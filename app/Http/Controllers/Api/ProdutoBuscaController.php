<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Services\Produto\ResolverDadosCatalogoProduto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProdutoBuscaController extends Controller
{
    public function __invoke(
        Request $request,
        ResolverDadosCatalogoProduto $resolverCatalogo
    ): JsonResponse {
        $dados = $request->validate([
            'q' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $busca = trim(
            $dados['q'] ?? ''
        );

        if ($busca === '') {
            return response()->json([
                'data' => [],
            ]);
        }

        $produtos = Produto::query()
            ->with([
                'marcaRelacionada:id,nome',
                'imagens',
                'catalogos.fonte',
                'catalogos.estado',
                'catalogos.imagens',
                'catalogos.aplicacoes',
                'catalogos.atributos',
            ])
            ->select([
                'id',
                'nome',
                'descricao',
                'quantidade',
                'estoque_minimo',
                'preco_uni',
                'codigo',
                'codigo_fabricante',
                'codigo_barras',
                'marca_id',
            ])
            ->where(
                'status',
                true
            )
            ->where(
                function ($query) use ($busca) {
                    $query
                        ->where(
                            'nome',
                            'like',
                            "%{$busca}%"
                        )
                        ->orWhere(
                            'descricao',
                            'like',
                            "%{$busca}%"
                        )
                        ->orWhere(
                            'codigo',
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
                }
            )
            ->orderBy('nome')
            ->limit(15)
            ->get();

        return response()->json([
            'data' => $produtos
                ->map(
                    function (
                        Produto $produto
                    ) use (
                        $resolverCatalogo
                    ) {
                        $catalogo =
                            $resolverCatalogo
                                ->resolver(
                                    $produto
                                );

                        $imagemLocal =
                            $produto
                                ->imagens
                                ->first()
                                ?->caminho;

                        return [
                            'id' =>
                                $produto->id,

                            'nome' =>
                                $produto->nome,

                            'descricao' =>
                                $produto->descricao,

                            'preco' =>
                                (float) $catalogo[
                                    'preco_venda'
                                ],

                            'preco_jaime' =>
                                (float) $produto
                                    ->preco_uni,

                            'origem_preco' =>
                                $catalogo[
                                    'origem_preco'
                                ],

                            'estoque_atual' =>
                                (float) $produto
                                    ->quantidade,

                            'codigo' =>


                                $produto->codigo,


                            'codigo_fabricante' =>
                                $produto
                                    ->codigo_fabricante,

                            'codigo_barras' =>
                                $produto
                                    ->codigo_barras,

                            'marca' =>
                                $produto
                                    ->marcaRelacionada
                                    ?->nome,

                            'imagem_url' =>
                                $imagemLocal
                                    ? asset(
                                        'storage/'
                                        .$imagemLocal
                                    )
                                    : $catalogo[
                                        'imagem_url'
                                    ],

                            'catalogo' =>
                                $catalogo,
                        ];
                    }
                )
                ->values(),
        ]);
    }
}
