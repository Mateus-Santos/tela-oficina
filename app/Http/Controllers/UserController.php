<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Endereco;
use Illuminate\Http\Request;

class PessoaController extends Controller {

    public function index()
    {
        $pessoas = Pessoa::all();
        return view('pessoa.listarpessoa', compact('pessoas'));
    }

    public function create()
    {
        return view('pessoa.cadastropessoa');
    }

    public function store(Request $request)
    {
        $pessoa = new Pessoa();
        $onlyconsonants = str_replace(['-', ''], "", $request);
        $pessoa->nome = $request->input("nome");
        $pessoa->email = $request->input("email");
        $pessoa->data_nascimento = $request->input("data_nascimento");
        $pessoa->telefone_1 = str_replace(['-', ' ', '(', ')'], "", $request->input("telefone_1"));
        $pessoa->telefone_2 = str_replace(['-', ' ', '(', ')'], "", $request->input("telefone_2"));
        $pessoa->cpf = str_replace(['.', '-'], "", $request->input("cpf"));;
        $pessoa->rg = $request->input("rg");
        $pessoa->save();
        return redirect()->route('pessoas.index');
    }

    public function show(string $id)
    {
        $pessoa = Pessoa::find($id);
        $enderecos = Endereco::where('id_pessoa', $id)->get();
        return view('pessoa.showpessoa', ['pessoa' => $pessoa, 'enderecos' => $enderecos]);
    }

    public function edit(string $id_pessoa)
    {
        $pessoa = Pessoa::where('id_pessoa', $id_pessoa)->first();
        return view('pessoa.editarpessoa', array('pessoa' => $pessoa));
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
