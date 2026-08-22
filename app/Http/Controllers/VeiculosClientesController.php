<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\VeiculosCliente;
use App\Models\Veiculo;
use App\Models\Montadora;
use App\Models\User;
use App\Models\Pessoa;
use App\Models\Cliente;

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

        $veiculosclientes = $query->latest()->paginate(10);

        return view('veiculosclientes.listarveiculosclientes', compact('veiculosclientes'));
    }

    public function create()
    {
        $montadoras = Montadora::select('id', 'nome')->get();
        $userLogado = Auth::user();

        if ($userLogado->permitions == 1) {
            $clientes = Cliente::with('pessoa')->get();
        } else {
            // Correção: a coluna em pessoas é user_id
            $clientes = Cliente::whereHas('pessoa', function ($query) use ($userLogado) {
                $query->where('user_id', $userLogado->id);
            })->with('pessoa')->get();
        }

        return view('veiculosclientes.cadastroveiculosclientes', compact('clientes', 'montadoras'));
    }

    public function store(Request $request)
    {
        $userLogado = auth()->user();

        $request->validate([
            'placa' => 'required|string|max:8',
            'ano' => 'required|numeric',
            'cor' => 'required|string|max:30',
            'veiculo_id' => 'required|exists:veiculos,id',
            'id_cliente' => $userLogado->permitions == 1 ? 'required|exists:clientes,id' : 'nullable',
        ]);

        $veiculosclientes = new VeiculosCliente();
        $veiculosclientes->placa = $request->input("placa");
        $veiculosclientes->ano = $request->input("ano");
        $veiculosclientes->cor = $request->input("cor");
        $veiculosclientes->veiculo_id = $request->input("veiculo_id");

        if ($userLogado->permitions == 1) {
            $veiculosclientes->cliente_id = $request->input("id_cliente");
        } else {
            $veiculosclientes->cliente_id = $userLogado->pessoa->cliente->id;
        }

        $veiculosclientes->save();
        return redirect()->route('veiculosclientes.index')->with('success', 'Veículo cadastrado com sucesso!');
    }

    public function edit(string $id)
    {
        $veiculoscliente = VeiculosCliente::with(['cliente.pessoa', 'veiculo.montadora'])->findOrFail($id);
        $userLogado = auth()->user();

        if ($userLogado->permitions != 1) {
            $clienteIdLogado = $userLogado->pessoa?->cliente?->id;
            if ($veiculoscliente->cliente_id !== $clienteIdLogado) {
                abort(403, 'Ação não autorizada.');
            }
        }

        $montadoras = Montadora::all();
        $clientes = [];

        if ($userLogado->permitions == 1) {
            $clientes = Cliente::with('pessoa')->get();
        }

        return view('veiculosclientes.editarveiculosclientes', compact('veiculoscliente', 'montadoras', 'clientes'));
    }

    public function update(Request $request, string $id)
    {
        $veiculoscliente = VeiculosCliente::findOrFail($id);
        $userLogado = auth()->user();

        if ($userLogado->permitions != 1) {
            $clienteIdLogado = $userLogado->pessoa?->cliente?->id;
            if ($veiculoscliente->cliente_id !== $clienteIdLogado) {
                abort(403, 'Ação não autorizada.');
            }
        }

        // Validação ajustada para usar 'id_cliente' (igual ao formulário Blade)
        $request->validate([
            'placa' => 'required|string|max:8',
            'ano' => 'required|numeric',
            'cor' => 'required|string|max:30',
            'veiculo_id' => 'required|exists:veiculos,id',
            'id_cliente' => $userLogado->permitions == 1 ? 'required|exists:clientes,id' : 'nullable',
        ]);

        $veiculoscliente->placa = $request->input("placa");
        $veiculoscliente->ano = $request->input("ano");
        $veiculoscliente->cor = $request->input("cor");
        $veiculoscliente->veiculo_id = $request->input("veiculo_id");

        if ($userLogado->permitions == 1) {
            $veiculoscliente->cliente_id = $request->input("id_cliente");
        } else {
            $veiculoscliente->cliente_id = $userLogado->pessoa->cliente->id;
        }

        $veiculoscliente->save();

        return redirect()
            ->route('veiculosclientes.index')
            ->with('success', 'Veículo atualizado com sucesso!');
    }

    public function destroy(string $id)
    {
        VeiculosCliente::where('id', $id)->delete();
        return redirect()->route('veiculosclientes.index');
    }
}