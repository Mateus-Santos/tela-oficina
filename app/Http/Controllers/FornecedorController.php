<?php

namespace App\Http\Controllers;

use App\Actions\Fornecedor\AtualizarFornecedor;
use App\Actions\Fornecedor\CriarFornecedor;
use App\Http\Requests\Fornecedor\StoreFornecedorRequest;
use App\Http\Requests\Fornecedor\UpdateFornecedorRequest;
use App\Models\Fornecedor;
use Illuminate\Http\Request;

class FornecedorController extends Controller
{
    public function index(Request $request)
    {
        $fornecedores = Fornecedor::query()
            ->withCount(['produtos', 'compras'])
            ->when($request->filled('nome'), function ($query) use ($request) {
                $query->where('nome', 'like', '%' . $request->nome . '%');
            })
            ->when($request->filled('cnpj'), function ($query) use ($request) {
                $cnpj = preg_replace('/\D/', '', $request->cnpj);

                $query->whereRaw(
                    "REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '/', ''), '-', '') LIKE ?",
                    ['%' . $cnpj . '%']
                );
            })
            ->orderBy('nome')
            ->paginate(10)
            ->withQueryString();

        return view('fornecedor.index', compact('fornecedores'));
    }

    public function create()
    {
        return view('fornecedor.create');
    }

    public function store(
        StoreFornecedorRequest $request,
        CriarFornecedor $criarFornecedor
    ) {
        $fornecedor = $criarFornecedor->execute($request->validated());

        return redirect()
            ->route('fornecedores.show', $fornecedor)
            ->with('success', 'Fornecedor cadastrado com sucesso!');
    }

    public function show(Fornecedor $fornecedor)
    {
        $fornecedor->loadCount(['produtos', 'compras']);

        return view('fornecedor.show', compact('fornecedor'));
    }

    public function edit(Fornecedor $fornecedor)
    {
        return view('fornecedor.edit', compact('fornecedor'));
    }

    public function update(
        UpdateFornecedorRequest $request,
        Fornecedor $fornecedor,
        AtualizarFornecedor $atualizarFornecedor
    ) {
        $fornecedorAtualizado = $atualizarFornecedor->execute(
            $fornecedor,
            $request->validated()
        );

        return redirect()
            ->route('fornecedores.show', $fornecedorAtualizado)
            ->with('success', 'Fornecedor atualizado com sucesso!');
    }

    public function destroy(Fornecedor $fornecedor)
    {
        if ($fornecedor->compras()->exists()) {
            return redirect()
                ->route('fornecedores.index')
                ->with(
                    'error',
                    'Este fornecedor não pode ser excluído porque possui compras vinculadas.'
                );
        }

        $fornecedor->delete();

        return redirect()
            ->route('fornecedores.index')
            ->with('success', 'Fornecedor excluído com sucesso!');
    }
}
