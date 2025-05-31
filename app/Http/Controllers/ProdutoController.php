<?php

namespace App\Http\Controllers;
use App\Models\produto;
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
        $produto->descricao_produto = $request->input("descricao_produto");
        $produto->marcas = $request->input("marcas");
        $produto->departamentos = $request->input("departamento");
        $produto->produtos = $request->input("produto");
        $produto->vulvula = $request->input("vulvula");
        $produto->quantidade = $request->input("quantidade");
        $produto->ano = $request->input("ano");
        $produto->preco_uni = $request->input("valor_uni");
        $produto->codigo_fabricante = $request->input("codigo_fabricante");
        if($request->hasFile("img") && $request->file("img")->isValid()){
            // Armazenar a imagem na pasta public/produtos
            $imgPath = $request->file('img')->store('public/produtos');
            // Remover o 'public/' para armazenar o caminho.
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

    public function edit(string $id_produto)
    {
        $produto = produto::where('id_produto', $id_produto)->first();
        return view('produto.editarproduto', array('produto' => $produto));
    }

    public function update(Request $request, string $id_produto)
    {
        $produto = produto::where('id_produto', $id_produto)->first();

        $produto->update([
            'id_produto' => $id_produto,
            'montadora' => $request->montadora,
            'nome' => $request->nome,
            'veiculos' => $request->veiculos,
            'motor' => $request->motor,
            'descricao_produto' => $request->descricao_produto,
            'marcas' => $request->marcas,
            'departamentos' => $request->departamentos,
            'produtos' => $request->produtos,
            'vulvula' => $request->vulvula,
            'quantidade' => $request->quantidade,
            'ano' => $request->ano,
        ]);
        
        return redirect()->route('produtos.index');
    }

    public function destroy(string $id_produto)
    {
        $produto = produto::where('id_produto', $id_produto)->delete();
        return redirect()->route('produtos.index');
    }
}
