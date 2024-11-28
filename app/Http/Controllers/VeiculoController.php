<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Veiculo;
use App\Models\User;

class VeiculoController extends Controller
{
    public function index()
    {
        if(auth()->user()->permitions == 2){
            $veiculos = Veiculo::where('id_user', auth()->user()->id)->get();
            return view('veiculo.listarveiculo', compact('veiculos'));
        }
        else{
            $veiculos = Veiculo::all();
            return view('veiculo.listarveiculo', compact('veiculos'));
        }
    }

    public function create()
    {
        $veiculos = Veiculo::all();
        $users = User::all();
        return view('veiculo.cadastroveiculo', compact('veiculos', 'users'));
    }

    public function store(Request $request)
    {
        $veiculo = new Veiculo();

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
