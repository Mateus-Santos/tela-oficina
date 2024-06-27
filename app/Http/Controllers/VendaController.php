<?php

namespace App\Http\Controllers;

use App\Models\Venda;
use App\Models\Cliente;
use App\Models\Colaborador;
use App\Models\Peca;
use App\Models\PecaVenda;
use Illuminate\Http\Request;

class VendaController extends Controller
{
    
    public function index()
    {
        return view('listarvenda');
    }

    public function create()
    {
        return view('cadastrovenda');
    }

    public function store(Request $request)
    {
        
    }

    public function show(Venda $venda)
    {
        //
    }

    public function edit(string $id)
    {
        $venda = Venda::where('id', $id)->first();
        return view('editarvenda', array('venda' => $venda));
    }

    public function update(Request $request, string $id)
    {
        $venda = Venda::where('id', $id)->first();
        $qtd = $request->input("quantidade");
        $valor_unitario1 = $request->input("valor_uni");

        $venda->update([
            'valor_total' => $qtd*$valor_unitario1,
            'data_venda' => $request->data_venda,
            'quantidade' => $request->quantidade,
            'valor_uni' => $request->valor_uni,
            'desconto' => $request->desconto,
            'juros' => $request->juros,
            'valor_pagto' => $request->valor_pagto,
            'data_venc' => $request->data_venc,
            'data_pagto' => '2000',
        ]);
        
        return redirect()->route('vendas.index');
    }

    public function destroy(string $id)
    {
        $venda = Venda::where('id', $id)->delete();
        return redirect()->route('vendas.index');
    }
}
