<?php

namespace App\Http\Controllers;

use App\Actions\Produto\AtualizarProduto;
use App\Actions\Produto\CriarProduto;
use App\Http\Requests\Produto\StoreProdutoRequest;
use App\Http\Requests\Produto\UpdateProdutoRequest;
use App\Models\Montadora;
use App\Models\Produto;
use App\Models\ProdutoImagem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProdutoController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin:admin');
    }

    public function index(Request $request)
    {
        $codigosFabricanteDisponiveis = Produto::select('codigo_fabricante')
            ->distinct()
            ->orderBy('codigo_fabricante')
            ->pluck('codigo_fabricante');

        $produtos = Produto::with([
            'veiculos',
            'marcaRelacionada',
            'imagens',
        ])
            ->filtro($request->all())
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        return view('produto.listarproduto', compact(
            'produtos',
            'codigosFabricanteDisponiveis'
        ));
    }

    public function create()
    {
        $montadoras = Montadora::select('id', 'nome')->get();

        return view('produto.cadastroproduto', compact('montadoras'));
    }

    public function show(Produto $produto)
    {
        $produto->load([
            'marcaRelacionada',
            'fornecedor',
            'veiculos.montadora',
            'imagens',
            'anexosVinculos.anexo',
        ]);

        return view('produto.showproduto', compact('produto'));
    }

    public function store(
        StoreProdutoRequest $request,
        CriarProduto $criarProduto
    ) {
        $criarProduto->execute($request->validated());

        return redirect()
            ->route('produtos.index')
            ->with('success', 'Produto cadastrado com sucesso!');
    }

    public function edit(Produto $produto)
    {
        $produto->load([
            'imagens',
            'veiculos.montadora',
        ]);

        $montadoras = Montadora::select('id', 'nome')->get();

        return view('produto.editarproduto', compact(
            'produto',
            'montadoras'
        ));
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
            ->with('success', 'Produto atualizado com sucesso!');
    }

    public function destroyImagem(
        Produto $produto,
        ProdutoImagem $imagem
    ): JsonResponse {
        abort_unless(
            (int) $imagem->produto_id === (int) $produto->id,
            404
        );

        $caminho = $imagem->caminho;

        DB::transaction(function () use (
            $produto,
            $imagem
        ) {
            $imagem->delete();

            $produto
                ->imagens()
                ->get()
                ->values()
                ->each(function ($imagemRestante, $ordem) {
                    if ((int) $imagemRestante->ordem === $ordem) {
                        return;
                    }

                    $imagemRestante->update([
                        'ordem' => $ordem,
                    ]);
                });
        });

        if (
            $caminho
            && Storage::disk('public')->exists($caminho)
        ) {
            Storage::disk('public')->delete($caminho);
        }

        return response()->json([
            'success' => true,
            'message' => 'Imagem removida com sucesso.',
        ]);
    }

    public function destroy(Produto $produto)
    {
        $produto->load('imagens');

        $caminhos = $produto->imagens
            ->pluck('caminho')
            ->filter()
            ->values();

        $produto->veiculos()->detach();
        $produto->delete();

        foreach ($caminhos as $caminho) {
            if (Storage::disk('public')->exists($caminho)) {
                Storage::disk('public')->delete($caminho);
            }
        }

        return redirect()
            ->route('produtos.index')
            ->with('success', 'Produto removido com sucesso!');
    }
}
