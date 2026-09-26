<?php

namespace App\Http\Controllers;

use App\Actions\Marca\AtualizarMarca;
use App\Actions\Marca\CriarMarca;
use App\Http\Requests\Marca\StoreMarcaRequest;
use App\Http\Requests\Marca\UpdateMarcaRequest;
use App\Models\Marca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarcaController extends Controller
{
    public function index(Request $request): View
    {
        logger()->info('FILTROS MARCAS', $request->query());

        $marcas = Marca::query()
            ->withCount('produtos')
            ->when($request->filled('nome'), function ($query) use ($request) {
                $query->where(
                    'nome',
                    'like',
                    '%' . trim($request->input('nome')) . '%'
                );
            })
            ->when(
                $request->filled('status'),
                function ($query) use ($request) {
                    $query->where(
                        'ativo',
                        (int) $request->input('status')
                    );
                }
            )
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        return view('marca.index', compact('marcas'));
    }

    public function create(): View
    {
        return view('marca.create');
    }

    public function store(
        StoreMarcaRequest $request,
        CriarMarca $criarMarca
    ): RedirectResponse {
        $marca = $criarMarca->execute($request->validated());

        return redirect()
            ->route('marcas.edit', $marca)
            ->with('success', 'Marca cadastrada com sucesso.');
    }

    public function show(Marca $marca): View
    {
        $marca->loadCount('produtos');

        return view('marca.show', compact('marca'));
    }

    public function edit(Marca $marca): View
    {
        $marca->loadCount('produtos');

        return view('marca.edit', compact('marca'));
    }

    public function update(
        UpdateMarcaRequest $request,
        Marca $marca,
        AtualizarMarca $atualizarMarca
    ): RedirectResponse {
        $atualizarMarca->execute(
            $marca,
            $request->validated()
        );

        return redirect()
            ->route('marcas.edit', $marca)
            ->with('success', 'Marca atualizada com sucesso.');
    }

    public function destroy(Marca $marca): RedirectResponse
    {
        if ($marca->produtos()->exists()) {
            return redirect()
                ->route('marcas.index')
                ->with(
                    'error',
                    'Esta marca possui produtos vinculados e não pode ser excluída.'
                );
        }

        $marca->delete();

        return redirect()
            ->route('marcas.index')
            ->with('success', 'Marca excluída com sucesso.');
    }
}
