<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContratoServico;
use App\Models\User;
use App\Models\Manutencao;

class ManutencaoController extends Controller
{
    
    public function index()
    {
        $manutencaoes = Manutencao::all();
        return view('manutencao.listarmanutencao', compact('manutencaoes'));
    }

    public function create()
    {
        $contratos = ContratoServico::all();
        return view('manutencao.cadastromanutencao', compact('contratos'));
    }

    public function store(Request $request)
    {
        $manutencao = new Manutencao();
        
        $manutencao->setor = $request->input("setor");
        $manutencao->descricao = $request->input("descricao");
        $manutencao->nivel = $request->input("nivel");
        $manutencao->valor = $request->input("valor");
        $manutencao->id_contrato_servico = $request->input("id_contrato_servico");
        $manutencao->save();
        return redirect()->route('contratoservico.index');
    }

    public function edit(string $id_peca)
    {

    }

    public function update(Request $request, string $id_peca)
    {

    }

    public function destroy(string $id_peca)
    {

    }
}
