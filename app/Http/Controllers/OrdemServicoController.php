<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdemServico;
use App\Models\User;
use App\Models\VeiculosClientes;
use App\Models\Cliente;
use App\Models\Servico;

class OrdemServicoController extends Controller
{
    public function index()
    {
        $ordem_servicos = OrdemServico::all();
        return view('ordem_servicos.listar_ordem_servicos', compact('ordem_servicos'));
    }

    public function create()
    {
        $clientes = Cliente::all();
        return view('ordem_servicos.cadastro_ordem_servicos', compact('clientes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'veiculo_cliente_id' => 'required|exists:veiculos_clientes,id',
            'status' => 'required',
            'data_abertura' => 'required|date',
            'descricao' => 'required|string',
        ]);

        $ordem_servicos = new OrdemServico();
        $ordem_servicos->data_abertura = $request->input("data_abertura");
        $ordem_servicos->status = $request->input("status");
        $ordem_servicos->veiculo_cliente_id = $request->input("veiculo_cliente_id");
        $ordem_servicos->descricao = $request->input("descricao");
        $ordem_servicos->save();
        return redirect()->route('ordemservicos.index');
    }

    public function show(string $id)
    {
        if(auth()->user()->permitions == 2){
            $id_veiculo = Veiculo::where('id_user', auth()->user()->id)->get();
            $ordem_servicos = OrdemServico::find($id);
            $servicos = Servico::where('id_ordem_servico', $id)->get();
            $valor_Servico = Servico::where('id_ordem_servico', $id)->sum('valor');
            return view('ordem_servicos.showordem_servicos', ['ordem_servicos' => $ordem_servicos, 'servicos' => $servicos, 'valor_Servico' => $valor_Servico]);
        }
        else{
            $ordem_servicos = OrdemServico::find($id);
            $servicos = Servico::where('id_ordem_servico', $id)->get();
            $valor_Servico = Servico::where('id_ordem_servico', $id)->sum('valor');
            return view('ordem_servicos.show_ordem_servicos', ['ordem_servicos' => $ordem_servicos, 'servicos' => $servicos, 'valor_Servico' => $valor_Servico]);
        }

    }

    public function edit(string $id)
    {
        $veiculos = Veiculo::all();
        return view('editaordem_servicos', ['veiculo' => $veiculos]);
    }


    public function update(Request $request, string $id)
    {
        //
    }


    public function destroy(string $id)
    {
        $contrato = OrdemServico::where('id', $id)->delete();
        return redirect()->route('OrdemServico.index');
    }
}
