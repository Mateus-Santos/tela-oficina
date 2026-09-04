<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMontadoraRequest;
use App\Http\Requests\UpdateMontadoraRequest;
use App\Models\Montadora;
use Illuminate\Http\Request;

class MontadoraController extends Controller
{
    public function index(Request $request)
    {
        $query = Montadora::query();

        if ($request->filled('nome')) {
            $query->where(
                'nome',
                'like',
                '%' . $request->input('nome') . '%'
            );
        }

        $montadoras = $query
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view(
            'montadora.listarmontadora',
            compact('montadoras')
        );
    }

    public function create()
    {
        return view('montadora.cadastromontadora');
    }

    public function store(StoreMontadoraRequest $request)
    {
        Montadora::create($request->validated());

        return redirect()
            ->route('montadoras.index')
            ->with('success', 'Montadora cadastrada com sucesso.');
    }

    public function edit(Montadora $montadora)
    {
        return view(
            'montadora.editarmontadora',
            compact('montadora')
        );
    }

    public function update(
        UpdateMontadoraRequest $request,
        Montadora $montadora
    ) {
        $montadora->update($request->validated());

        return redirect()
            ->route('montadoras.index')
            ->with('success', 'Montadora atualizada com sucesso.');
    }

    public function destroy(Montadora $montadora)
    {
        if ($montadora->veiculos()->exists()) {
            return redirect()
                ->route('montadoras.index')
                ->with(
                    'error',
                    'Não é possível excluir esta montadora porque existem veículos vinculados a ela.'
                );
        }

        $montadora->delete();

        return redirect()
            ->route('montadoras.index')
            ->with('success', 'Montadora excluída com sucesso.');
    }
}
