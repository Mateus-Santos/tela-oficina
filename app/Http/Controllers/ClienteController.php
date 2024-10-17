<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Pessoa;
use App\Models\User;

class ClienteController extends Controller
{
    
    public function index()
    {
        $clientes = Cliente::all();
        return view('cliente.listarcliente', compact('clientes'));
    }

    public function create()
    {
        $users = User::all();
        $pessoas = Pessoa::all();
        return view('cliente.cadastrocliente', compact('users', 'pessoas'));
    }


    public function store(Request $request)
    {
        $cliente = new Cliente();
        $cliente->id_pessoa = $request->input("id_pessoa");
        $cliente->id_user = $request->input("id_user");
        $cliente->save();
        return redirect()->route('clientes.index');
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
