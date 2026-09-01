<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoriaFinanceiraRequest;
use App\Http\Requests\UpdateCategoriaFinanceiraRequest;
use App\Models\CategoriaFinanceira;

class CategoriaFinanceiraController extends Controller
{
    public function index()
    {
        $categorias = CategoriaFinanceira::query()
            ->orderBy('tipo')
            ->orderBy('nome')
            ->paginate(15);

        return view(
            'financeiro.categorias.index',
            compact('categorias')
        );
    }

    public function create()
    {
        return view('financeiro.categorias.create');
    }

    public function store(StoreCategoriaFinanceiraRequest $request)
    {
        CategoriaFinanceira::create($request->validated());

        return redirect()
            ->route('categorias-financeiras.index')
            ->with('success', 'Categoria financeira cadastrada com sucesso.');
    }

    public function edit(CategoriaFinanceira $categoriaFinanceira)
    {
        return view(
            'financeiro.categorias.edit',
            compact('categoriaFinanceira')
        );
    }

    public function update(
        UpdateCategoriaFinanceiraRequest $request,
        CategoriaFinanceira $categoriaFinanceira
    ) {
        $categoriaFinanceira->update($request->validated());

        return redirect()
            ->route('categorias-financeiras.index')
            ->with('success', 'Categoria financeira atualizada com sucesso.');
    }

    public function destroy(CategoriaFinanceira $categoriaFinanceira)
    {
        $categoriaFinanceira->delete();

        return redirect()
            ->route('categorias-financeiras.index')
            ->with('success', 'Categoria financeira excluída com sucesso.');
    }
}
