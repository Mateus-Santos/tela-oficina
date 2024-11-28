<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContratoServico;
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
        return redirect()->route('contratoservico.index');
    }


    public function show(string $id)
    {
        $contrato_servico = ContratoServico::find($id);
        $manutencoes = Manutencao::where('id_contrato_servico', $id)->get();
        $valor_manutencao = Manutencao::where('id_contrato_servico', $id)->sum('valor');
        return view('contrato_servico.showcontrato_servico', ['contrato_servico' => $contrato_servico, 'manutencoes' => $manutencoes, 'valor_manutencao' => $valor_manutencao]);
    }

    public function edit(string $id)
    {
        $veiculos = Veiculo::all();
        return view('editacontrato_servico', ['veiculo' => $veiculos]);
    }


    public function update(Request $request, string $id)
    {
        //
    }


    public function destroy(string $id)
    {
        $contrato = ContratoServico::where('id', $id)->delete();
        return redirect()->route('contratoservico.index');
    }
}
