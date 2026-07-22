<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdemServico;
use App\Models\SetorServico;
use App\Models\User;

class OrdemServicoController extends Controller
{

    public function index()
    {
        $ordemservicos = OrdemServico::all();
        return view('ordemservico.listar_os', compact('ordemservicos'));
    }

    public function create()
    {
        $setorservicos = SetorServico::all();
        return view('ordemservico.cadastro_os', compact('setorservicos'));
    }

    public function store(Request $request)
    {
        $valor = str_replace('.', '', $request->valor);
        $valor = str_replace(',', '.', $valor);
        $ordemservico = new ordemservico();
        $ordemservico->data_abertura = now();
        $ordemservico->setor_servico_id = $request->input("setor_servico_id");
        $ordemservico->veiculo_cliente_id = $request->input("veiculo_cliente_id");
        $ordemservico->descricao = $request->input("descricao");
        $ordemservico->valor = $valor;
        $ordemservico->save();
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
        $ordemservico = OrdemServico::where('id', $id)->delete();
        return redirect()->route('ordemservicos.index');
    }
}
