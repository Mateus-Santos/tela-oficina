<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use App\Models\produtoVenda;
use App\Models\produto;
use App\Models\Cliente;
use App\Models\Colaborador;
use App\Models\Venda;
use App\Events\Vendaproduto;


class produtoVendaController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin:admin');
    }
    
    public function index()
    {
        $produtovendas = produtoVenda::all();
        return view('produto.listarprodutovenda', compact('produtovendas'));
    }

    public function create()
    {
        $clientes = Cliente::all();
        $colaboradores = Colaborador::all();
        $produtos = produto::all();
        return view('produto.cadastroprodutovenda', compact('clientes', 'colaboradores', 'produtos'));
    }

    public function store(Request $request)
    {
        $venda = Venda::create([
            'desconto' => $request->input('desconto'),
            'juros' => $request->input('juros'),
            'data_venda' => $request->input('data_venda'),
            'data_venc' => $request->input('data_venc'),
        ]);

        $produto_request = $request->input('id_produto');
        $produto = json_decode($produto_request, true);
        $id_produto = $produto['id_produto'];
        $valor_uni = $produto['preco_uni'];

        $quantidade = $request->input('quantidade');
        
        $produtovenda = produtoVenda::create([
            'id_venda' => $venda->id,
            'id_cliente' => $request->input('id_cliente'),
            'id_colaborador' => $request->input('id_colaborador'),
            'id_produto' => $id_produto,
            'valor_uni' => $valor_uni,
            'quantidade' => $quantidade,
            'valor_pagto' => $request->input('valor_pagto'),
            'data_pagto' => $request->input('data_pagto'),
        ]);

        event(new Vendaproduto($produtovenda));

        return redirect()->route('produtovendas.index');
    }

    public function edit(string $id_produto)
    {

    }

    public function update(Request $request, string $id_produto)
    {

    }

    public function destroy(string $id_produto)
    {

    }
}
