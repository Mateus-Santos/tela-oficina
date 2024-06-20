<?php

namespace App\Http\Controllers;

use App\Models\Venda;
use Illuminate\Http\Request;

class VendaController extends Controller
{
    
    public function index()
    {
        $vendas = Pessoa::all();
        return view('listarvenda', compact('vendas'));
    }

    public function create()
    {
        return view('cadastrovenda');
    }

    public function store(Request $request)
    {
        $venda = new Venda();
        $venda->valor_total = $request->input("valor_total");
        $venda->data_venda = $request->input("data_venda");
        $venda->quantidade = $request->input("quantidade");
        $venda->valor_uni = $request->input("valor_uni");
        $venda->desconto = $request->input("desconto");
        $venda->juros = $request->input("juros");
        $venda->valor_uni = $request->input("valor_uni");
        $venda->data_venc = $request->input("data_venc");
        $venda->data_pagto = $request->input("data_pagto");
        $venda->save();
        return redirect()->route('vendas.index');
    }

    public function show(Venda $venda)
    {
        //
    }

    public function edit(Venda $venda)
    {
        $venda = Venda::where('id', $id)->first();
        return view('editarvenda', array('venda' => $venda));
    }

    public function update(Request $request, Venda $venda)
    {
        $venda = Venda::where('id', $id)->first();

        $venda->update([
            'valor_total' => $valor_total,
            'data_venda' => $request->data_venda,
            'quantidade' => $request->quantidade,
            'valor_uni' => $request->valor_uni,
            'desconto' => $request->desconto,
            'juros' => $request->juros,
            'valor_pagto' => $request->valor_pagto,
            'data_venc' => $request->data_venc,
            'data_pagto' => $request->data_pagto,
        ]);
        
        return redirect()->route('vendas.index');
    }

    public function destroy(Venda $venda)
    {
        $venda = Venda::where('id', $id)->delete();
        return redirect()->route('vendas.index');
    }
}
