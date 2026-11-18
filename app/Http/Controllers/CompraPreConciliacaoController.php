<?php

namespace App\Http\Controllers;

use App\Actions\Compra\SalvarPreConciliacao;
use App\Http\Requests\Compra\SalvarPreConciliacaoRequest;
use App\Models\Compra;
use App\Services\Compra\PreviaPreConciliacao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class CompraPreConciliacaoController extends Controller
{
    public function show(Request $request, Compra $compra, PreviaPreConciliacao $previsao): View
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $compra->load('fornecedor');
        return view('compra.conciliacoes.preview', [
            'compra' => $compra,
            'previsao' => $previsao->montar($compra),
            'registro' => $compra->preConciliacao,
        ]);
    }

    public function store(
        SalvarPreConciliacaoRequest $request,
        Compra $compra,
        SalvarPreConciliacao $salvar
    ): RedirectResponse {
        try {
            $salvar->execute($compra, $request->validated(), (int) $request->user()->id);
            return redirect()->route('compras.preconciliacao.show', $compra)
                ->with('success', 'Proposta em rascunho salva. Nenhuma conta, parcela ou pagamento foi alterado.');
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
