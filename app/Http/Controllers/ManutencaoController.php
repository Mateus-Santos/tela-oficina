<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Veiculo;
use App\Models\User;
use App\Models\Manutencao;

class ManutencaoController extends Controller
{
    
    public function index()
    {
        $manutencaoes = Manutencao::all();
        return view('manutencao.listarmanutencao', compact('manutencaoes'));
    }

    public function create()
    {
        $veiculos = Manutencao::all();
        $users = User::all();
        return view('manutencao.cadastromanutencao', compact('veiculos', 'users'));
    }

    public function store(Request $request)
    {
        $manutencao = new Manutencao();
        
        $veiculo->placa = $request->input("placa");
        $veiculo->ano = $request->input("ano");
        $veiculo->marca = $request->input("marca");
        $veiculo->cor = $request->input("cor");
        $veiculo->id_user = $request->input("id_user");
        $veiculo->save();
        return redirect()->route('veiculos.index');
    }

    public function edit(string $id_peca)
    {

    }

    public function update(Request $request, string $id_peca)
    {

    }

    public function destroy(string $id_peca)
    {

    }
}
