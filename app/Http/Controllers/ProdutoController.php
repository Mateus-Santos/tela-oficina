<?php

namespace App\Http\Controllers;

use App\Actions\Produto\AtualizarProduto;
use App\Actions\Produto\CriarProduto;
use App\Http\Requests\Produto\StoreProdutoRequest;
use App\Http\Requests\Produto\UpdateProdutoRequest;
use App\Models\CatalogoProduto;
use App\Models\Montadora;
use App\Models\Produto;
use App\Models\ProdutoImagem;
use App\Services\Produto\ResolverDadosCatalogoProduto;
use App\Services\Produto\ListarProdutosUnificados;
use App\Support\Catalogo\PrecoVendaForge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProdutoController extends Controller
{
    public function index(Request $request, ListarProdutosUnificados $listagem)
    {
        $produtos = $listagem->paginar($request);

        return view('produto.listarproduto_unificado', compact('produtos'));
    }

    /** Produto ainda nao cadastrado na oficina: somente leitura, sem estoque presumido. */
    public function showCatalogo(CatalogoProduto $catalogo)
    {
        if ($catalogo->produto_id !== null) {
            return redirect()->route('produtos.show', $catalogo->produto_id);
        }

        $catalogo->load(['estado', 'imagens', 'atributos', 'edicao']);
        $aplicacoesTecnicas = $catalogo->aplicacoes()
            ->where('ativa', true)
            ->orderBy('id')
            ->paginate(40, ['*'], 'aplicacoes')
            ->withQueryString();
        $dadosExibicao = app(\App\Services\Produto\ExibirCatalogoProduto::class)->dados($catalogo);
        $precoSugerido = $dadosExibicao['sugerido'];

        return view('produto.showcatalogo', compact(
            'catalogo', 'aplicacoesTecnicas', 'precoSugerido', 'dadosExibicao'
        ));
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

        $aplicacoesTecnicas = $catalogoPrincipal
            ? $catalogoPrincipal->aplicacoes()->where('ativa', true)
                ->with('produtoAplicacao')
                ->orderBy('id')->paginate(40, ['*'], 'aplicacoes')->withQueryString()
            : null;

        $aplicacoesPendentes = $catalogoPrincipal
            ? $catalogoPrincipal->aplicacoes()
                ->where('ativa', true)
                ->whereDoesntHave('produtoAplicacao', function ($query) use ($produto) {
                    $query->where('produto_id', $produto->id)
                        ->where('status_resolucao', 'resolvida')
                        ->whereNotNull('veiculo_id');
                })->count()
            : 0;

        return view('produto.showproduto', compact(
            'produto', 'dadosCatalogo', 'catalogoPrincipal',
            'aplicacoesTecnicas', 'aplicacoesPendentes'
        ));
    }

    public function store(
        StoreProdutoRequest $request,
        CriarProduto $criarProduto
    ) {
        $produto = $criarProduto->execute(
            $request->validated()
        );

        return redirect()
            ->route('produtos.index')
            ->with(
                'success',
                'Produto cadastrado com sucesso! Código interno: '.$produto->codigo
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
