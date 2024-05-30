<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Colaborador;

class ColaboradorController extends Controller
{
    public function index()
    {
        $colaboradors = Colaborador::all();
        return view('listarcolaborador', compact('colaboradors'));
    }

    public function create()
    {
        return view('cadastrocolaborador');
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
