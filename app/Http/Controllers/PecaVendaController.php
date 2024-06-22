<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PecaVenda;
use App\Models\Peca;
use App\Models\Cliente;
use App\Models\Colaborador;


class PecaVendaController extends Controller
{
    public function index()
    {

    }

    public function create()
    {

    }

    public function store(Request $request)
    {
        $peca_venda = new PecaVenda();
        $peca_venda->id_cliente = $request->input("id_cliente");
        $peca_venda->id_colaborador = $request->input("id_colaborador");
        $peca_venda->id_peca = $request->input("id_peca");
        $peca_venda->id_venda = $request->input("id_venda");
        $peca_venda->quantidade = $request->input("quantidade");
        $peca_venda->save();
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
