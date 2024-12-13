<?php

namespace App\Http\Controllers;
use App\Models\Peca;
use Illuminate\Http\Request;

class PecaController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin:admin');
    }

    public function index()
    {
        $pecas = Peca::all();
        return view('peca.listarpeca', compact('pecas'));
    }

    public function create()
    {
        return view('peca.cadastropeca');
    }

    public function store(Request $request)
    {
        $peca = new Peca();
        
        $peca->montadora = $request->input("montadora");
        $peca->nome = $request->input("nome");
        $peca->veiculos = $request->input("veiculo");
        $peca->motor = $request->input("motor");
        $peca->descricao_peca = $request->input("descricao_peca");
        $peca->marcas = $request->input("marcas");
        $peca->departamentos = $request->input("departamento");
        $peca->produtos = $request->input("produto");
        $peca->vulvula = $request->input("vulvula");
        $peca->quantidade = $request->input("quantidade");
        $peca->ano = $request->input("ano");
        $peca->preco_uni = $request->input("valor_uni");
        $peca->codigo_fabricante = $request->input("codigo_fabricante");
        if($request->hasFile("img") && $request->file("img")->isValid()){
            // Armazenar a imagem na pasta public/pecas
            $imgPath = $request->file('img')->store('public/pecas');
            // Remover o 'public/' para armazenar o caminho.
            $imgPath = str_replace('public/', '', $imgPath);
            $peca->img = $imgPath;
        }
        $peca->save();
        return redirect()->route('pecas.index');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id_peca)
    {
        $peca = Peca::where('id_peca', $id_peca)->first();
        return view('peca.editarpeca', array('peca' => $peca));
    }

    public function update(Request $request, string $id_peca)
    {
        $peca = Peca::where('id_peca', $id_peca)->first();

        $peca->update([
            'id_peca' => $id_peca,
            'montadora' => $request->montadora,
            'nome' => $request->nome,
            'veiculos' => $request->veiculos,
            'motor' => $request->motor,
            'descricao_peca' => $request->descricao_peca,
            'marcas' => $request->marcas,
            'departamentos' => $request->departamentos,
            'produtos' => $request->produtos,
            'vulvula' => $request->vulvula,
            'quantidade' => $request->quantidade,
            'ano' => $request->ano,
        ]);
        
        return redirect()->route('pecas.index');
    }

    public function destroy(string $id_peca)
    {
        $peca = Peca::where('id_peca', $id_peca)->delete();
        return redirect()->route('pecas.index');
    }
}
