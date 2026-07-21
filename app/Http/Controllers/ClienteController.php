<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Pessoa;

class ClienteController extends Controller
{
    public function index()
    {
        // Carrega os clientes junto com os dados da pessoa
        $clientes = Cliente::with('pessoa')->get();
        return view('cliente.listarcliente', compact('clientes'));
    }

    public function create()
    {
        // Busca pessoas para associar ao novo cliente
        $pessoas = Pessoa::all();
        return view('cliente.cadastrocliente', compact('pessoas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'pessoa_id' => 'required|exists:pessoas,id|unique:clientes,pessoa_id',
            'pontos' => 'nullable|integer|min:0',
        ]);

        Cliente::create([
            'pessoa_id' => $request->input('pessoa_id'),
            'pontos' => $request->input('pontos', 0),
        ]);

        return redirect()->route('clientes.index');
    }

    public function show(string $id)
    {
        $cliente = Cliente::with('pessoa')->findOrFail($id);
        return view('cliente.showcliente', compact('cliente'));
    }

    public function edit(string $id)
    {
        $cliente = Cliente::with('pessoa')->findOrFail($id);
        return view('cliente.editarcliente', compact('cliente'));
    }

    public function update(Request $request, string $id)
    {
        $cliente = Cliente::findOrFail($id);

        $request->validate([
            'pontos' => 'required|integer|min:0',
        ]);

        $cliente->update([
            'pontos' => $request->input('pontos'),
        ]);

        return redirect()->route('clientes.index');
    }

    public function destroy(string $id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();

        return redirect()->route('clientes.index');
    }
}