<?php

namespace App\Http\Controllers;

use App\Actions\Produto\AtualizarProduto;
use App\Actions\Produto\CriarProduto;
use App\Http\Requests\Produto\StoreProdutoRequest;
use App\Http\Requests\Produto\UpdateProdutoRequest;
use App\Models\Montadora;
use App\Models\Produto;
use App\Models\ProdutoImagem;
use App\Services\Produto\ResolverDadosCatalogoProduto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProdutoController extends Controller
{
    public function index(
        Request $request,
        ResolverDadosCatalogoProduto $resolverCatalogo
    ) {
        $codigosFabricanteDisponiveis =
            Produto::query()
                ->select('codigo_fabricante')
                ->distinct()
                ->orderBy('codigo_fabricante')
                ->pluck('codigo_fabricante');

        $catalogoComImagem = function ($query) {
            $query->where(function ($query) {
                $query
                    ->where(function ($query) {
                        $query
                            ->whereNotNull('imagem_thumb_url')
                            ->where('imagem_thumb_url', '<>', '');
                    })
                    ->orWhere(function ($query) {
                        $query
                            ->whereNotNull('imagem_url')
                            ->where('imagem_url', '<>', '');
                    })
                    ->orWhereHas(
                        'imagens',
                        function ($query) {
                            $query->where(function ($query) {
                                $query
                                    ->whereNotNull('url_thumb')
                                    ->orWhereNotNull('url_original');
                            });
                        }
                    );
            });
        };

        $query = Produto::query()
            ->with([
                'veiculos',
                'marcaRelacionada',
                'imagens',
                'catalogos.fonte',
                'catalogos.estado',
                'catalogos.imagens',
                'catalogos.aplicacoes',
                'catalogos.atributos',
            ])
            ->filtro(
                $request->except('imagem')
            );

        if ($request->input('imagem') === 'com_imagem') {
            $query->where(function ($query) use (
                $catalogoComImagem
            ) {
                $query
                    ->whereHas('imagens')
                    ->orWhereHas(
                        'catalogos',
                        $catalogoComImagem
                    );
            });
        }

        if ($request->input('imagem') === 'sem_imagem') {
            $query
                ->whereDoesntHave('imagens')
                ->whereDoesntHave(
                    'catalogos',
                    $catalogoComImagem
                );
        }

        $produtos = $query
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        $dadosCatalogoProdutos = [];

        foreach (
            $produtos->getCollection()
            as $produto
        ) {
            $dadosCatalogoProdutos[
                $produto->id
            ] = $resolverCatalogo->resolver(
                $produto
            );
        }

        return view(
            'produto.listarproduto',
            compact(
                'produtos',
                'codigosFabricanteDisponiveis',
                'dadosCatalogoProdutos'
            )
        );
    }

    public function create()
    {
        $montadoras = Montadora::query()
            ->select('id', 'nome')
            ->get();

        return view(
            'produto.cadastroproduto',
            compact('montadoras')
        );
    }

    public function show(
        Produto $produto,
        ResolverDadosCatalogoProduto $resolverCatalogo
    ) {
        $produto->load([
            'marcaRelacionada',
            'fornecedor',
            'veiculos.montadora',
            'imagens',
            'anexosVinculos.anexo',
            'catalogos.fonte',
            'catalogos.estado',
            'catalogos.imagens',
            'catalogos.aplicacoes',
            'catalogos.atributos',
        ]);

        $dadosCatalogo =
            $resolverCatalogo->resolver(
                $produto
            );

        $catalogoPrincipal =
            $dadosCatalogo['catalogo_produto_id']
                ? $produto->catalogos->firstWhere(
                    'id',
                    $dadosCatalogo[
                        'catalogo_produto_id'
                    ]
                )
                : null;

        return view(
            'produto.showproduto',
            compact(
                'produto',
                'dadosCatalogo',
                'catalogoPrincipal'
            )
        );
    }

    public function store(
        StoreProdutoRequest $request,
        CriarProduto $criarProduto
    ) {
        $criarProduto->execute(
            $request->validated()
        );

        return redirect()
            ->route('produtos.index')
            ->with(
                'success',
                'Produto cadastrado com sucesso!'
            );
    }

    public function edit(Produto $produto)
    {
        $produto->load([
            'imagens',
            'veiculos.montadora',
        ]);

        $montadoras = Montadora::query()
            ->select('id', 'nome')
            ->get();

        return view(
            'produto.editarproduto',
            compact(
                'produto',
                'montadoras'
            )
        );
    }

    public function update(
        UpdateProdutoRequest $request,
        Produto $produto,
        AtualizarProduto $atualizarProduto
    ) {
        $atualizarProduto->execute(
            $produto,
            $request->validated()
        );

        return redirect()
            ->route('produtos.index')
            ->with(
                'success',
                'Produto atualizado com sucesso!'
            );
    }

    public function destroyImagem(
        Produto $produto,
        ProdutoImagem $imagem
    ): JsonResponse {
        abort_unless(
            (int) $imagem->produto_id
                === (int) $produto->id,
            404
        );

        $caminho = $imagem->caminho;

        DB::transaction(
            function () use (
                $produto,
                $imagem
            ) {
                $imagem->delete();

                $produto
                    ->imagens()
                    ->get()
                    ->values()
                    ->each(
                        function (
                            $imagemRestante,
                            $ordem
                        ) {
                            if (
                                (int) $imagemRestante->ordem
                                === $ordem
                            ) {
                                return;
                            }

                            $imagemRestante->update([
                                'ordem' => $ordem,
                            ]);
                        }
                    );
            }
        );

        if (
            $caminho
            && Storage::disk('public')
                ->exists($caminho)
        ) {
            Storage::disk('public')
                ->delete($caminho);
        }

        return response()->json([
            'success' => true,
            'message' =>
                'Imagem removida com sucesso.',
        ]);
    }

    public function destroy(Produto $produto)
    {
        $produto->load('imagens');

        $caminhos = $produto
            ->imagens
            ->pluck('caminho')
            ->filter()
            ->values();

        $produto->veiculos()->detach();

        $produto->delete();

        foreach ($caminhos as $caminho) {
            if (
                Storage::disk('public')
                    ->exists($caminho)
            ) {
                Storage::disk('public')
                    ->delete($caminho);
            }
        }

        return redirect()
            ->route('produtos.index')
            ->with(
                'success',
                'Produto removido com sucesso!'
            );
    }
}
