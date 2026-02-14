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
        $servicos = Servico::all();
        return view('servico.listarServico', compact('servicos'));
    }

    public function create()
    {
        $ordem_servicos = OrdemServico::all();
        return view('servico.cadastroservico', compact('ordem_servicos'));
    }

    public function store(Request $request)
    {
        $valor = str_replace('.', '', $request->valor);
        $valor = str_replace(',', '.', $valor);
        $servico = new Servico();
        $servico->setor = $request->input("setor");
        $servico->descricao = $request->input("descricao");
        $servico->nivel = $request->input("nivel");
        $servico->valor = $valor;
        $servico->id_ordem_servico = $request->input("id_ordem_servico");
        $servico->save();
        return redirect()->route('ordemservicos.index');
    }

    public function edit(string $id)
    {

    }

    public function update(Request $request, string $id)
    {

    }

    public function destroy(string $id)
    {
        $servico = Servico::where('id', $id)->delete();
        return redirect()->route('ordemservicos.index');
    }
}
