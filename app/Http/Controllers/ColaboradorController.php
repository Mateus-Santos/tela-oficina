<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Colaborador;
use App\Models\Cliente;

class ColaboradorController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin:admin');
    }
    
    public function index()
    {
        $colaboradors = Colaborador::with('pessoa', 'user')->get();
        return view('colaborador.listarcolaborador', compact('colaboradors'));
    }

    public function create()
    {
        $clientes = Cliente::all();
        return view('colaborador.cadastrocolaborador', compact('clientes'));
    }

    public function store(Request $request)
    {
        $colaborador = new Colaborador();
        $user = $request->input("id_user");
        $user_json = json_decode($user, true);
        $id_user = $user_json['id_user'];
        $id_pessoa = $user_json['id_pessoa'];
        $colaborador->conta_banco = $request->input("conta_banco");
        $colaborador->chave_pix = $request->input("chave_pix");
        $colaborador->id_pessoa = $id_pessoa;
        $colaborador->id_user = $id_user;
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
        return view('colaborador.editarcolaborador', array('colaborador' => $colaborador), array('colaborador_pessoa_users' => $colaborador_pessoa_users));
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
