<?php

namespace App\Http\Controllers;

use App\Models\Venda;
use App\Models\Cliente;
use App\Models\Colaborador;
use App\Models\Peca;
use Illuminate\Http\Request;

class VendaController extends Controller
{
    
    public function index()
    {
        $vendas = Venda::all();
        $clientes = Cliente::all();
        $colaboradores = Colaborador::all();
        $pecas = Peca::all();
        return view('listarvenda', compact('vendas', 'clientes', 'colaboradores', 'pecas'));
    }

    public function create()
    {
        $clientes = Cliente::all();
        $colaboradores = Colaborador::all();
        $pecas = Peca::all();
        return view('cadastrovenda', compact('clientes', 'colaboradores', 'pecas'));
    }

    public function store(Request $request)
    {
        $venda = new Venda();
        $qtd = $request->input("quantidade");
        $valor_unitario1 = $request->input("valor_uni");
        $venda->valor_total = $qtd*$valor_unitario1;
        $venda->quantidade = $request->input("quantidade");
        $venda->valor_uni = $request->input("valor_uni");
        $venda->desconto = $request->input("desconto");
        $venda->juros = $request->input("juros");
        $venda->data_venda = $request->input("data_venda");
        $venda->data_venc = $request->input("data_venc");
        $venda->data_pagto = '2000';
        $venda->save();
        return redirect()->route('vendas.index');
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
