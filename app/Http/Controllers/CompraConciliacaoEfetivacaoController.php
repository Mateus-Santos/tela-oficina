<?php

namespace App\Http\Controllers;

use App\Actions\Compra\EfetivarConciliacaoCompra;
use App\Http\Requests\Compra\EfetivarConciliacaoRequest;
use App\Models\Compra;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

class CompraConciliacaoEfetivacaoController extends Controller
{
    public function store(
        EfetivarConciliacaoRequest $request,
        Compra $compra,
        EfetivarConciliacaoCompra $efetivar
    ): RedirectResponse {
        try {
            $efeito = $efetivar->execute($compra, $request->validated(), (int) $request->user()->id);
            return redirect()->route('compras.preconciliacao.show', $compra)
                ->with('success', "Conciliacao #{$efeito->id}: {$efeito->status}. Confira o registro antes de qualquer lancamento bancario.");
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
