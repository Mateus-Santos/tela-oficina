<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use App\Models\PecaVenda;
use App\Models\Peca;
use App\Models\Cliente;
use App\Models\Colaborador;
use App\Models\Venda;
use App\Events\VendaPeca;


class PecaVendaController extends Controller
{
    public function index()
    {
        $pecavendas = PecaVenda::all();
        return view('peca.listarpecavenda', compact('pecavendas'));
    }

    public function create()
    {
        $clientes = Cliente::all();
        $colaboradores = Colaborador::all();
        $pecas = Peca::all();
        return view('peca.cadastropecavenda', compact('clientes', 'colaboradores', 'pecas'));
    }

    public function store(Request $request)
    {
        $venda = Venda::create([
            'desconto' => $request->input('desconto'),
            'juros' => $request->input('juros'),
            'data_venda' => $request->input('data_venda'),
            'data_venc' => $request->input('data_venc'),
        ]);

        $peca_request = $request->input('id_peca');
        $peca = json_decode($peca_request, true);
        $id_peca = $peca['id_peca'];
        $valor_uni = $peca['preco_uni'];

        $quantidade = $request->input('quantidade');
        
        $pecavenda = PecaVenda::create([
            'id_venda' => $venda->id,
            'id_cliente' => $request->input('id_cliente'),
            'id_colaborador' => $request->input('id_colaborador'),
            'id_peca' => $id_peca,
            'valor_uni' => $valor_uni,
            'quantidade' => $quantidade,
            'valor_pagto' => $request->input('valor_pagto'),
            'data_pagto' => $request->input('data_pagto'),
        ]);

        event(new VendaPeca($pecavenda));

        return redirect()->route('pecavendas.index');
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
