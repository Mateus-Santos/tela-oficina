<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFormaPagamentoRequest;
use App\Http\Requests\UpdateFormaPagamentoRequest;
use App\Models\FormaPagamento;

class FormaPagamentoController extends Controller
{
    public function index()
    {
        $formasPagamento = FormaPagamento::query()
            ->orderBy('nome')
            ->paginate(15);

        return view(
            'financeiro.formas_pagamento.index',
            compact('formasPagamento')
        );
    }

    public function create()
    {
        return view('financeiro.formas_pagamento.create');
    }

    public function store(StoreFormaPagamentoRequest $request)
    {
        FormaPagamento::create($request->validated());

        return redirect()
            ->route('formas-pagamento.index')
            ->with('success', 'Forma de pagamento cadastrada com sucesso.');
    }

    public function edit(FormaPagamento $formaPagamento)
    {
        return view(
            'financeiro.formas_pagamento.edit',
            compact('formaPagamento')
        );
    }

    public function update(
        UpdateFormaPagamentoRequest $request,
        FormaPagamento $formaPagamento
    ) {
        $formaPagamento->update($request->validated());

        return redirect()
            ->route('formas-pagamento.index')
            ->with('success', 'Forma de pagamento atualizada com sucesso.');
    }

    public function destroy(FormaPagamento $formaPagamento)
    {
        $formaPagamento->delete();

        return redirect()
            ->route('formas-pagamento.index')
            ->with('success', 'Forma de pagamento excluída com sucesso.');
    }
}
