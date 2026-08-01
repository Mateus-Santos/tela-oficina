<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VeiculosCliente;
use App\Models\Veiculo;
use App\Models\Montadora;
use App\Models\User;
use App\Models\Pessoa;

class VeiculosClientesController extends Controller
{
    public function veiculosPorCliente($id)
    {
        $veiculos = VeiculosCliente::where('cliente_id', $id)
            ->with('veiculosClientes')
            ->get();

        return response()->json($veiculos);
    }

    public function index()
    {
        $user = auth()->user();

        $query = VeiculosCliente::with(['veiculo.montadora', 'cliente.pessoa']);

        if ($user->permitions == 2) {
            $clienteId = $user->pessoa?->cliente?->id;
            $query->where('cliente_id', $clienteId);
        }

        $veiculosclientes = $query->latest()->paginate(10); // Exibe 10 registros por página ordenados pelo mais recente

        return view('veiculosclientes.listarveiculosclientes', compact('veiculosclientes'));
    }

    public function create()
    {
        $montadoras = Montadora::select('id', 'nome')->get();
        $users = User::all();
        return view('veiculosclientes.cadastroveiculosclientes', compact('users', 'montadoras'));
    }

    public function store(Request $request)
    {
        $veiculosclientes = new VeiculosCliente();
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
        $veiculoscliente = VeiculosCliente::findOrFail($id);
        $montadoras = Montadora::all();
        if(auth()->user()->permitions == 1){
            $users = User::all();
            return view('veiculosclientes.editarveiculosclientes', compact('veiculoscliente', 'montadoras', 'users'));
        }
        return view('veiculosclientes.editarveiculosclientes', compact('veiculoscliente', 'montadoras'));
    }

    public function update(Request $request, string $id)
    {
        $veiculoscliente = VeiculosCliente::findOrFail($id);
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
        $veiculosclientes = VeiculosCliente::where('id', $id)->delete();
        return redirect()->route('veiculosclientes.index');
    }
}
