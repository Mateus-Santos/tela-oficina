<?php

namespace App\Http\Controllers;
use App\Models\Pessoa;
use Illuminate\Http\Request;

class PessoaController extends Controller {


    public function index()
    {
        $pessoas = Pessoa::all();
        return view('listarpessoa', compact('pessoas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('cadastropessoa');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $pessoa = new Pessoa();
        $pessoa->nome = $request->input("nome");
        $pessoa->email = $request->input("email");
        $pessoa->data_nascimento = $request->input("data_nascimento");
        $pessoa->telefone_1 = $request->input("telefone_1");
        $pessoa->telefone_2 = $request->input("telefone_2");
        $pessoa->cpf = $request->input("cpf");
        $pessoa->rg = $request->input("rg");
        $pessoa->save();
        return redirect()->route('pessoas.index');

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $pessoa)
    {
        return view('editarpessoa', ['pessoa' -> $pessoa]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
