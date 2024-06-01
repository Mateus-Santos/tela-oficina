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

    public function create()
    {
        return view('cadastropessoa');
    }

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

    public function show(string $id)
    {
        //
    }

    public function edit(string $id_pessoa)
    {
        $pessoa = Pessoa::where('id_pessoa', $id_pessoa)->first();
        return view('editarpessoa', array('pessoa' => $pessoa));
    }

    public function update(Request $request, string $id_pessoa)
    {
        $pessoa = Pessoa::where('id_pessoa', $id_pessoa)->first();

        $pessoa->update([
            'id_pessoa' => $id_pessoa,
            'nome' => $request->nome,
            'data_nascimento' => $request->data_nascimento,
            'email' => $request->email,
            'cpf' => $request->cpf,
            'rg' => $request->rg,
            'telefone_1' => $request->telefone_1,
            'telefone_2' => $request->telefone_2,
        ]);
        
        return redirect()->route('pessoas.index');
    }

    public function destroy(string $id_pessoa)
    {
        $pessoa = Pessoa::where('id_pessoa', $id_pessoa)->delete();
        return redirect()->route('pessoas.index');
    }
}
