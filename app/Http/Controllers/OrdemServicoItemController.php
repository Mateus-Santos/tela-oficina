<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdemServicoItem;
use App\Models\OrdemServico;
use App\Models\Produto;
use App\Models\Cliente;
use App\Models\User;

class OrdemServicoItemController extends Controller
{

    public function index()
    {
        $os_itens = OrdemServicoItem::all();
        return view('os_item.listar_os_item', compact('os_itens'));
    }

    public function create()
    {
        $ordemservicos = OrdemServico::all();
        $produtos = Produto::all();
        $users = User::all();
        return view('os_item.cadastro_os_item', compact('ordemservicos', 'produtos', 'users'));
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
