<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
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
        return view('cliente.cadastrocliente', compact('users'));
    }


    public function store(Request $request)
    {
        $cliente = new Cliente();
        $cliente->id_user = $request->input("id");
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
