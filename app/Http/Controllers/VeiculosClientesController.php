<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VeiculosClientes;
use App\Models\Veiculo;
use App\Models\Montadora;
use App\Models\User;

class VeiculosClientesController extends Controller
{
    public function veiculosPorCliente($id)
    {
        $veiculos = VeiculosClientes::where('cliente_id', $id)
            ->with('veiculosClientes')
            ->get();

        return response()->json($veiculos);
    }

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
        $montadoras = Montadora::select('id', 'nome')->get();
        $users = User::all();
        return view('veiculosclientes.cadastroveiculosclientes', compact('users', 'montadoras'));
    }

    public function store(Request $request)
    {
        $veiculosclientes = new VeiculosClientes();
        $veiculosclientes->placa = $request->input("placa");
        $veiculosclientes->ano = $request->input("ano");
        $veiculosclientes->cor = $request->input("cor");
        $veiculosclientes->veiculo_id = $request->input("veiculo_id");
        if(auth()->user()->permitions == 1){
            $veiculosclientes->cliente_id = $request->input("id_cliente");
        } else{
            $veiculosclientes->cliente_id = auth()->user()->cliente->id;
        }
        $veiculosclientes->save();
        return redirect()->route('veiculosclientes.index');        
    }

    public function edit(string $id)
    {
        $veiculoscliente = VeiculosClientes::findOrFail($id);
        $montadoras = Montadora::all();
        if(auth()->user()->permitions == 1){
            $users = User::all();
            return view('veiculosclientes.editarveiculosclientes', compact('veiculoscliente', 'montadoras', 'users'));
        }
        return view('veiculosclientes.editarveiculosclientes', compact('veiculoscliente', 'montadoras'));
    }

    public function update(Request $request, string $id)
    {
        $veiculoscliente = VeiculosClientes::findOrFail($id);
        $veiculoscliente->placa = $request->input("placa");
        $veiculoscliente->ano = $request->input("ano");
        $veiculoscliente->cor = $request->input("cor");
        $veiculoscliente->veiculo_id = $request->input("veiculo_id");

        if (auth()->user()->permitions == 1) {
            $veiculoscliente->cliente_id = $request->input("id_cliente");
        } else {
            $veiculoscliente->cliente_id = auth()->user()->cliente->id;
        }
        $veiculoscliente->save();
        return redirect()->route('veiculosclientes.index');
    }


    public function destroy(string $id)
    {
        $veiculosclientes = VeiculosClientes::where('id', $id)->delete();
        return redirect()->route('veiculosclientes.index');
    }
}
