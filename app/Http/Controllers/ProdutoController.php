<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\Montadora;
use App\Models\Veiculo;
use Illuminate\Validation\Rule;
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

    public function store(Request $request)
    {
        if ($request->filled('preco_uni')) {
            $preco = str_replace(['.', ','], ['', '.'], $request->preco_uni);
            $request->merge(['preco_uni' => $preco]);
        }
        
        $request->validate([
            'nome' => 'required|string|max:150',
            'descricao' => 'required|string',
            'preco_uni' => 'required|numeric|min:0',
            'codigo_fabricante' => 'required|string|unique:produtos,codigo_fabricante',
            'codigo_barras' => 'nullable|string|unique:produtos,codigo_barras',
            'quantidade' => 'nullable|integer|min:0',
            'estoque_minimo' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
            'fornecedor_id' => 'nullable|exists:fornecedores,id',
            'img' => 'nullable|image|max:2048',

            // relacionamento N:N
            'veiculos' => 'required|array',
            'veiculos.*' => 'exists:veiculos,id',
        ]);

        $data = [
            'nome' => $request->nome,
            'descricao' => $request->descricao,
            'preco_uni' => $request->preco_uni,
            'codigo_fabricante' => $request->codigo_fabricante,
            'codigo_barras' => $request->codigo_barras,
            'quantidade' => $request->quantidade ?? 0,
            'estoque_minimo' => $request->estoque_minimo ?? 0,
            'status' => $request->status ?? true,
            'fornecedor_id' => $request->fornecedor_id,
        ];

        if ($request->hasFile('img')) {
            $data['img'] = $request->file('img')
                ->store('produtos', 'public');
        }

        $produto = Produto::create($data);

        // relacionamento N:N
        $produto->veiculos()->sync($request->veiculos);

        return redirect()
            ->route('produtos.index')
            ->with('success', 'Produto cadastrado com sucesso!');
    }



    public function edit($id)
    {
        $produto = Produto::findOrFail($id);
        $montadoras = Montadora::select('id', 'nome')->get();
        return view('produto.editarproduto', compact('produto', 'montadoras'));
    }

    public function update(Request $request, $id)
    {
        $produto = Produto::findOrFail($id);

        $request->validate([
            'nome' => 'required|string|max:255',
            'codigo_fabricante' => [
                'required',
                Rule::unique('produtos', 'codigo_fabricante')->ignore($id),
            ],
            'codigo_barras' => [
                'nullable',
                Rule::unique('produtos', 'codigo_barras')->ignore($id),
            ],
            'preco_uni' => 'required',
            'quantidade' => 'required|integer',
            'veiculos' => 'required|array',
            'veiculos.*' => 'exists:veiculos,id',
        ]);

        $data = $request->only([
            'nome',
            'codigo_fabricante',
            'codigo_barras',
            'preco_uni',
            'quantidade',
            'descricao',
        ]);

        // Upload de imagem
        if ($request->hasFile('img')) {

            if ($produto->img && Storage::disk('public')->exists($produto->img)) {
                Storage::disk('public')->delete($produto->img);
            }

            $data['img'] = $request->file('img')->store('produtos', 'public');
        }

        // Atualiza produto
        $produto->update($data);

        // Atualiza relação N:N
        $produto->veiculos()->sync($request->veiculos);

        return redirect()
            ->route('produtos.index')
            ->with('success', 'Produto atualizado com sucesso!');
    }

    public function destroy($id)
    {
        $produto = Produto::findOrFail($id);

        // Remove imagem do storage
        if ($produto->img && Storage::disk('public')->exists($produto->img)) {
            Storage::disk('public')->delete($produto->img);
        }

        // Remove relacionamentos n:n
        $produto->veiculos()->detach();

        $produto->delete();

        return redirect()->route('produtos.index')
                         ->with('success', 'Produto removido com sucesso!');
    }
}
