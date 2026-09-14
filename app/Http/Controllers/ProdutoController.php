<?php

namespace App\Http\Controllers;

use App\Actions\Produto\AtualizarProduto;
use App\Actions\Produto\CriarProduto;
use App\Http\Requests\Produto\StoreProdutoRequest;
use App\Http\Requests\Produto\UpdateProdutoRequest;
use App\Models\Montadora;
use App\Models\Produto;
use Illuminate\Http\Request;
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

        $produtos = Produto::with(['veiculos'])
            ->filtro($request->all())
            ->orderBy('nome')
            ->get();

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

    public function destroy(Produto $produto)
    {
        if (
            $produto->img
            && Storage::disk('public')->exists($produto->img)
        ) {
            Storage::disk('public')->delete($produto->img);
        }

        $produto->veiculos()->detach();
        $produto->delete();

        return redirect()
            ->route('produtos.index')
            ->with('success', 'Produto removido com sucesso!');
    }
}
