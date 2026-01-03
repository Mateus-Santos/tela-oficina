<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VeiculosClientes;
use App\Models\Veiculo;
use App\Models\User;

class VeiculosClientesController extends Controller
{
    public function index()
    {
        if(auth()->user()->permitions == 2){
            $veiculosclientes = VeiculosClientes::where('cliente_id', auth()->user()->cliente->id)->get();
            return view('veiculosclientes.listarveiculosclientes', compact('veiculosclientes'));
        }
        else{
            $veiculosclientes = VeiculosClientes::all();
            return view('veiculosclientes.listarveiculosclientes', compact('veiculosclientes'));
        }
    }

    public function create()
    {
        $veiculosclientes = VeiculosClientes::all();
        $veiculos = Veiculo::all();
        $users = User::all();
        return view('veiculosclientes.cadastroveiculosclientes', compact('veiculosclientes', 'users', 'veiculos'));
    }

    public function store(Request $request)
    {
        $veiculosclientes = new VeiculosClientes();

        $veiculosclientes->placa = $request->input("placa");
        $veiculosclientes->ano = $request->input("ano");
        $veiculosclientes->cor = $request->input("cor");
        $veiculosclientes->cliente_id = auth()->user()->cliente->id;
        $veiculosclientes->veiculo_id = $request->input("id_veiculo");
        $veiculosclientes->save();
        return redirect()->route('veiculosclientes.index');
    }

    public function edit(string $id)
    {

    }

    public function update(Request $request, string $id)
    {

    }

    public function destroy(string $id)
    {
        $veiculo = VeiculosClientes::where('id', $id)->delete();
        return redirect()->route('veiculosclientes.index');
    }
}
