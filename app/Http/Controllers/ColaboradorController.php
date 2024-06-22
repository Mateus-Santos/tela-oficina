<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Colaborador;
use App\Models\Pessoa;
use App\Models\User;

class ColaboradorController extends Controller
{
    public function index()
    {
        $colaboradors = Colaborador::with('pessoa', 'user')->get();
        return view('listarcolaborador', compact('colaboradors'));
    }

    public function create()
    {
        $users = User::all();
        $pessoas = Pessoa::all();
        return view('cadastrocolaborador', compact('users', 'pessoas'));
    }

    public function store(Request $request)
    {
        $colaborador = new Colaborador();
        $colaborador->conta_banco = $request->input("conta_banco");
        $colaborador->chave_pix = $request->input("chave_pix");
        $colaborador->id_pessoa = $request->input("id_pessoa");
        $colaborador->id_user = $request->input("id_user");
        $colaborador->save();
        return redirect()->route('colaboradors.index');
    }


    public function show(string $id)
    {
        //
    }

    public function edit(string $id_colaborador)
    {
        $colaborador = Colaborador::where('id_colaborador', $id_colaborador)->first();
        $colaborador_pessoa_users = Colaborador::with('pessoa', 'user')->get();
        return view('editarcolaborador', array('colaborador' => $colaborador), array('colaborador_pessoa_users' => $colaborador_pessoa_users));
    }


    public function update(Request $request, string $id_colaborador)
    {
        $colaborador = Colaborador::where('id_colaborador', $id_colaborador)->first();

        $colaborador->update([
            'id_colaborador' => $id_colaborador,
            'id_pessoa' => $request->id_pessoa,
            'id_user' => $request->id_user,
            'chave_pix' => $request->chave_pix,
            'conta_banco' => $request->conta_banco
        ]);
        
        return redirect()->route('colaboradors.index');
    }


    public function destroy(string $id_colaborador)
    {
        $colaborador = Colaborador::where('id_colaborador', $id_colaborador)->delete();
        return redirect()->route('colaboradors.index');
    }
}
