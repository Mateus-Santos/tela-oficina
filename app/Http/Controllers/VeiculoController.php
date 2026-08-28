<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Veiculo;
use App\Models\Montadora;
use App\Models\User;

class VeiculoController extends Controller
{
    public function index(Request $request)
    {
        $query = Veiculo::with('montadora');

        if ($request->filled('veiculo')) {
            $query->where('nome', 'like', '%' . $request->veiculo . '%');
        }

        if ($request->filled('montadora')) {
            $query->where('montadora_id', $request->montadora);
        }

        $veiculos = $query
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $montadoras = Montadora::orderBy('nome')->get();

        return view('veiculo.listarveiculo', compact(
            'veiculos',
            'montadoras'
        ));
    }

    public function porMontadora($id)
    {
        $veiculos = Veiculo::where('montadora_id', $id)
            ->select('id', 'nome')
            ->orderBy('nome')
            ->get();
        return response()->json($veiculos);
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

    public function edit(string $id)
    {

    }

    public function update(Request $request, string $id)
    {

    }

    public function destroy(string $id)
    {
        $veiculo = Veiculo::where('id_veiculo', $id)->delete();
        return redirect()->route('veiculos.index');
    }
}
