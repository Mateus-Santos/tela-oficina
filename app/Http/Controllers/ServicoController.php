<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ordemservico;
use App\Models\User;
use App\Models\Servico;

class ServicoController extends Controller
{

    public function index()
    {
        $servico = Servico::all();
        return view('Servico.listarServico', compact('servico'));
    }

    public function create()
    {
        $ordemservico = OrdemServico::all();
        return view('Servico.cadastroServico', compact('ordemservico'));
    }

    public function store(Request $request)
    {
        $servico = new Servico();
        $servico->setor = $request->input("setor");
        $servico->descricao = $request->input("descricao");
        $servico->nivel = $request->input("nivel");
        $servico->valor = $request->input("valor");
        $servico->id_contrato_servico = $request->input("id_contrato_servico");
        $servico->save();
        return redirect()->route('ordemservico.index');
    }

    public function edit(string $id)
    {

    }

    public function update(Request $request, string $id)
    {

    }

    public function destroy(string $id)
    {
        $servico = Servico::where('id_Servico', $id)->delete();
        return redirect()->route('ordemservico.index');
    }
}
