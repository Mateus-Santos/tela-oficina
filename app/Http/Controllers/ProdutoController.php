<?php

namespace App\Http\Controllers;
use App\Models\Produto;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin:admin');
    }

    public function index()
    {
        $produtos = produto::all();
        return view('produto.listarproduto', compact('produtos'));
    }

    public function create()
    {
        return view('produto.cadastroproduto');
    }

    public function store(Request $request)
    {
        $produto = new produto();
        
        $produto->montadora = $request->input("montadora");
        $produto->nome = $request->input("nome");
        $produto->veiculos = $request->input("veiculo");
        $produto->motor = $request->input("motor");
        $produto->descricao = $request->input("descricao");
        $produto->marcas = $request->input("marcas");
        $produto->departamentos = $request->input("departamento");
        $produto->valvula = $request->input("valvula");
        $produto->quantidade = $request->input("quantidade");
        $produto->ano = $request->input("ano");
        $produto->preco_uni = $request->input("preco_uni");
        $produto->codigo_fabricante = $request->input("codigo_fabricante");
        if($request->hasFile("img") && $request->file("img")->isValid()){
            $imgPath = $request->file('img')->store('public/produtos');
            $imgPath = str_replace('public/', '', $imgPath);
            $produto->img = $imgPath;
        }
        $produto->save();
        return redirect()->route('produtos.index');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        $produto = Produto::findOrFail($id);
        return view('produto.editarproduto', array('produto' => $produto));
    }

    public function update(Request $request, string $id)
    {
        $produto = Produto::findOrFail($id);

        $produto->update([
            'id_produto' => $id_produto,
            'montadora' => $request->montadora,
            'nome' => $request->nome,
            'veiculos' => $request->veiculos,
            'motor' => $request->motor,
            'descricao' => $request->descricao,
            'marcas' => $request->marcas,
            'departamentos' => $request->departamentos,
            'valvula' => $request->valvula,
            'quantidade' => $request->quantidade,
            'ano' => $request->ano,
        ]);
        
        return redirect()->route('produtos.index');
    }

    public function destroy(string $id_produto)
    {
        $produto = Produto::findOrFail($id);
        return redirect()->route('produtos.index');
    }
}
