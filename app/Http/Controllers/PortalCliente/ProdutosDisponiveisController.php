<?php

namespace App\Http\Controllers\PortalCliente;

use App\Http\Controllers\Controller;
use App\Http\Requests\PortalCliente\ListarProdutosDisponiveisRequest;
use App\Services\PortalCliente\ListarProdutosDisponiveis;
use Illuminate\Contracts\View\View;

class ProdutosDisponiveisController extends Controller
{
    public function __invoke(
        ListarProdutosDisponiveisRequest $request,
        ListarProdutosDisponiveis $listagem
    ): View {
        $busca = trim(
            (string) ($request->validated()['busca'] ?? '')
        );

        return view('portal-cliente.produtos.index', [
            'produtos' => $listagem->paginar($busca),
            'busca' => $busca,
        ]);
    }
}
