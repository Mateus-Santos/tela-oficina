<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProdutoController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin:admin');
    }

    public function index()
    {
        // Lista produtos ativos e ordena por nome
        $produtos = Produto::orderBy('status', 'desc')->orderBy('nome')->paginate(20);
        return view('produto.listarproduto', compact('produtos'));
    }

    public function create()
    {
        return view('produto.cadastroproduto');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo_barras'     => 'nullable|string|unique:produtos,codigo_barras',
            'montadora'         => 'required|array',
            'nome'              => 'required|string|max:150',
            'ano_modelo'        => 'required|digits:4|integer',
            'veiculos'          => 'required|array',
            'motor'             => 'nullable|string|max:50',
            'descricao'         => 'nullable|string',
            'marcas'            => 'required|array',
            'departamentos'     => 'required|array',
            'valvula'           => 'nullable|array',
            'quantidade'        => 'required|integer|min:0',
            'preco_uni'         => 'required|numeric|min:0',
            'img'               => 'nullable|image|max:2048',
            'codigo_fabricante' => 'required|string|unique:produtos,codigo_fabricante',
            'localizacao'       => 'nullable|string|max:255',
            'unidade_medida'    => 'nullable|string|max:10',
            'estoque_minimo'    => 'nullable|integer|min:0',
            'fornecedor_id'     => 'nullable|exists:fornecedores,id',
            'status'            => 'sometimes|boolean',
        ]);

        if ($request->hasFile('img')) {
            $data['img'] = $request->file('img')->store('produtos', 'public');
        }

        Produto::create($data);

        return redirect()->route('produtos.index')
                         ->with('success', 'Produto cadastrado com sucesso!');
    }

    public function edit($id)
    {
        $produto = Produto::findOrFail($id);
        return view('produto.editarproduto', compact('produto'));
    }

    public function update(Request $request, $id)
    {
        $produto = Produto::findOrFail($id);

        $data = $request->validate([
            'codigo_barras'     => "nullable|string|unique:produtos,codigo_barras,{$id}",
            'montadora'         => 'required|array',
            'nome'              => 'required|string|max:150',
            'ano_modelo'        => 'required|digits:4|integer',
            'veiculos'          => 'required|array',
            'motor'             => 'nullable|string|max:50',
            'descricao'         => 'nullable|string',
            'marcas'            => 'required|array',
            'departamentos'     => 'required|array',
            'valvula'           => 'nullable|array',
            'quantidade'        => 'required|integer|min:0',
            'preco_uni'         => 'required|numeric|min:0',
            'img'               => 'nullable|image|max:2048',
            'codigo_fabricante' => "required|string|unique:produtos,codigo_fabricante,{$id}",
            'localizacao'       => 'nullable|string|max:255',
            'unidade_medida'    => 'nullable|string|max:10',
            'estoque_minimo'    => 'nullable|integer|min:0',
            'fornecedor_id'     => 'nullable|exists:fornecedores,id',
            'status'            => 'sometimes|boolean',
        ]);

        if ($request->hasFile('img')) {
            // Remove imagem antiga
            if ($produto->img && Storage::disk('public')->exists($produto->img)) {
                Storage::disk('public')->delete($produto->img);
            }

            $data['img'] = $request->file('img')->store('produtos', 'public');
        }

        $produto->update($data);

        return redirect()->route('produtos.index')
                         ->with('success', 'Produto atualizado com sucesso!');
    }

    public function destroy($id)
    {
        $produto = Produto::findOrFail($id);

        // Remove imagem do storage se existir
        if ($produto->img && Storage::disk('public')->exists($produto->img)) {
            Storage::disk('public')->delete($produto->img);
        }

        $produto->delete();

        return redirect()->route('produtos.index')
                         ->with('success', 'Produto removido com sucesso!');
    }
}
