<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContratoServico;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Veiculo;
use App\Models\Manutencao;

class ContratoServicoController extends Controller
{

    public function index()
    {
        $contratos = ContratoServico::all();
        return view('contrato_servico.listarcontrato_servico', compact('contratos'));
    }

    public function create()
    {
        $veiculos = Veiculo::all();
        return view('contrato_servico.cadastrocontrato_servico', compact('veiculos'));
    }


    public function store(Request $request)
    {
        $contrato_servico = new ContratoServico();
        $contrato_servico->data_abertura = $request->input("data_abertura");
        $contrato_servico->status = $request->input("status");
        $contrato_servico->id_veiculo = $request->input("id_veiculo");
        $contrato_servico->descricao = $request->input("descricao");
        $contrato_servico->save();
        return redirect()->route('manutencoes.index');
    }


    public function show(string $id)
    {
        
    }

    public function edit(string $id)
    {
        $veiculos = Veiculo::all();
        return view('editacontrato_servico', ['veiculo' -> $veiculos]);
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
