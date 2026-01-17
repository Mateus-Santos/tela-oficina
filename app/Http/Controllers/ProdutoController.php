<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\Montadora;
use App\Models\Veiculo;
use App\Models\Departamento;
use App\Models\Valvula;
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
        // Para filtros de select (valores únicos)
        $anosDisponiveis = Produto::select('ano_modelo')->distinct()->orderBy('ano_modelo')->pluck('ano_modelo');
        $codigosFabricanteDisponiveis = Produto::select('codigo_fabricante')->distinct()->orderBy('codigo_fabricante')->pluck('codigo_fabricante');

        $produtos = Produto::with(['montadoras', 'departamentos', 'valvulas', 'veiculos'])
            ->filtro($request->all())
            ->orderBy('nome')
            ->paginate(20);

        return view('produto.listarproduto', compact(
            'produtos',
            'anosDisponiveis',
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
        $data = $request->validate([
            'codigo_barras'     => 'nullable|string|unique:produtos,codigo_barras',
            'nome'              => 'required|string|max:150',
            'ano_modelo'        => 'required|digits:4|integer',
            'descricao'         => 'nullable|string',
            'quantidade'        => 'required|integer|min:0',
            'preco_uni'         => 'required|numeric|min:0',
            'img'               => 'nullable|image|max:2048',
            'codigo_fabricante' => 'required|string|unique:produtos,codigo_fabricante',
            'estoque_minimo'    => 'nullable|integer|min:0',
            'fornecedor_id'     => 'nullable|exists:fornecedores,id',
            'status'            => 'sometimes|boolean',

            // Relacionamentos N:N
            'montadora'     => 'required|array',
            'veiculos'      => 'required|array',
            'departamentos' => 'required|array',
            'valvula'       => 'nullable|array',
        ]);

        if ($request->hasFile('img')) {
            $data['img'] = $request->file('img')->store('produtos', 'public');
        }

        $produto = Produto::create($data);

        // Relacionamentos n:n
        $produto->montadoras()->sync($request->montadora);
        $produto->veiculos()->sync($request->veiculos);
        $produto->departamentos()->sync($request->departamentos);
        $produto->valvulas()->sync($request->valvula ?? []);

        return redirect()->route('produtos.index')
                         ->with('success', 'Produto cadastrado com sucesso!');
    }

    public function edit($id)
    {
        $produto = Produto::with(['montadoras', 'veiculos', 'departamentos', 'valvulas'])->findOrFail($id);

        return view('produto.editarproduto', [
            'produto'       => $produto,
            'montadoras'    => Montadora::orderBy('nome')->get(),
            'veiculos'      => Veiculo::orderBy('placa')->get(),
            'departamentos' => Departamento::orderBy('nome')->get(),
            'valvulas'      => Valvula::orderBy('nome')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $produto = Produto::findOrFail($id);

        $data = $request->validate([
            'codigo_barras'     => "nullable|string|unique:produtos,codigo_barras,{$id}",
            'nome'              => 'required|string|max:150',
            'ano_modelo'        => 'required|digits:4|integer',
            'descricao'         => 'nullable|string',
            'quantidade'        => 'required|integer|min:0',
            'preco_uni'         => 'required|numeric|min:0',
            'img'               => 'nullable|image|max:2048',
            'codigo_fabricante' => "required|string|unique:produtos,codigo_fabricante,{$id}",
            'estoque_minimo'    => 'nullable|integer|min:0',
            'fornecedor_id'     => 'nullable|exists:fornecedores,id',
            'status'            => 'sometimes|boolean',

            // Relacionamentos
            'montadora'     => 'required|array',
            'veiculos'      => 'required|array',
            'departamentos' => 'required|array',
            'valvula'       => 'nullable|array',
        ]);

        if ($request->hasFile('img')) {
            // Remove imagem antiga
            if ($produto->img && Storage::disk('public')->exists($produto->img)) {
                Storage::disk('public')->delete($produto->img);
            }

            $data['img'] = $request->file('img')->store('produtos', 'public');
        }

        $produto->update($data);

        // Atualiza relacionamentos n:n
        $produto->montadoras()->sync($request->montadora);
        $produto->veiculos()->sync($request->veiculos);
        $produto->departamentos()->sync($request->departamentos);
        $produto->valvulas()->sync($request->valvula ?? []);

        return redirect()->route('produtos.index')
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
        $produto->montadoras()->detach();
        $produto->veiculos()->detach();
        $produto->departamentos()->detach();
        $produto->valvulas()->detach();

        $produto->delete();

        return redirect()->route('produtos.index')
                         ->with('success', 'Produto removido com sucesso!');
    }
}
