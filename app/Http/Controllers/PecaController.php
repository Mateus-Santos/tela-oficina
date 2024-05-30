<?php

namespace App\Http\Controllers;
use App\Models\Peca;
use Illuminate\Http\Request;

class PecaController extends Controller
{

    public function index()
    {
        $pecas = Peca::all();
        return view('listarpeca', compact('pecas'));
    }

    public function create()
    {
        return view('cadastropeca');
    }

    public function store(Request $request)
    {
        $peca = new Peca();
        $peca->montadora = $request->input("montadora");
        $peca->nome = $request->input("nome");
        $peca->veiculos = $request->input("veiculos");
        $peca->motor = $request->input("motor");
        $peca->descricao_peca = $request->input("descricao_peca");
        $peca->marcas = $request->input("marcas");
        $peca->departamentos = $request->input("departamentos");
        $peca->produtos = $request->input("produtos");
        $peca->vulvula = $request->input("vulvula");
        $peca->quantidade = $request->input("quantidade");
        $peca->save();
        return redirect()->route('pecas.index');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }


    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
