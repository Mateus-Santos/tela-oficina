<?php

namespace App\Http\Controllers;

use App\Actions\Compra\AtualizarCompra;
use App\Actions\Compra\CriarCompra;
use App\Http\Requests\Compra\StoreCompraRequest;
use App\Http\Requests\Compra\UpdateCompraRequest;
use App\Models\Compra;
use App\Models\Fornecedor;
use App\Models\Produto;
use Illuminate\Http\Request;

class CompraController extends Controller
{
    /**
     * Lista as compras cadastradas.
     */
    public function index(Request $request)
    {
        $compras = Compra::query()
            ->with('fornecedor')
            ->when($request->filled('numero_nf'), function ($query) use ($request) {
                $query->where(
                    'numero_nf',
                    'like',
                    '%' . $request->numero_nf . '%'
                );
            })
            ->when($request->filled('fornecedor_id'), function ($query) use ($request) {
                $query->where(
                    'fornecedor_id',
                    $request->fornecedor_id
                );
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where(
                    'status',
                    $request->status
                );
            })
            ->when($request->filled('data_inicio'), function ($query) use ($request) {
                $query->whereDate(
                    'data_entrada',
                    '>=',
                    $request->data_inicio
                );
            })
            ->when($request->filled('data_fim'), function ($query) use ($request) {
                $query->whereDate(
                    'data_entrada',
                    '<=',
                    $request->data_fim
                );
            })
            ->latest('data_entrada')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $fornecedores = Fornecedor::query()
            ->orderBy('nome')
            ->get();

        return view(
            'compra.index',
            compact('compras', 'fornecedores')
        );
    }

    /**
     * Exibe o formulário de cadastro.
     */
    public function create()
    {
        $fornecedores = Fornecedor::query()
            ->orderBy('nome')
            ->get();

        $produtos = Produto::query()
            ->where('status', true)
            ->orderBy('nome')
            ->get();

        return view(
            'compra.create',
            compact('fornecedores', 'produtos')
        );
    }

    /**
     * Salva uma nova compra.
     */
    public function store(
        StoreCompraRequest $request,
        CriarCompra $criarCompra
    ) {
        $compra = $criarCompra->execute(
            $request->validated()
        );

        return redirect()
            ->route('compra.show', $compra)
            ->with(
                'success',
                'Compra cadastrada com sucesso!'
            );
    }

    /**
     * Exibe os detalhes da compra.
     */
    public function show(Compra $compra)
    {
        $compra->load([
            'fornecedor',
            'itens.produto',
        ]);

        return view(
            'compra.show',
            compact('compra')
        );
    }

    /**
     * Exibe o formulário de edição.
     */
    public function edit(Compra $compra)
    {
        if (in_array($compra->status, ['aprovada', 'cancelada'], true)) {
            return redirect()
                ->route('compra.show', $compra)
                ->with(
                    'error',
                    'Uma compra aprovada ou cancelada não pode ser editada.'
                );
        }

        $compra->load([
            'fornecedor',
            'itens.produto',
        ]);

        $fornecedores = Fornecedor::query()
            ->orderBy('nome')
            ->get();

        $produtos = Produto::query()
            ->where('status', true)
            ->orderBy('nome')
            ->get();

        return view(
            'compra.edit',
            compact(
                'compra',
                'fornecedores',
                'produtos'
            )
        );
    }

    /**
     * Atualiza uma compra.
     */
    public function update(
        UpdateCompraRequest $request,
        Compra $compra,
        AtualizarCompra $atualizarCompra
    ) {
        $compraAtualizada = $atualizarCompra->execute(
            $compra,
            $request->validated()
        );

        return redirect()
            ->route('compra.show', $compraAtualizada)
            ->with(
                'success',
                'Compra atualizada com sucesso!'
            );
    }

    /**
     * Exclui uma compra.
     */
    public function destroy(Compra $compra)
    {
        if ($compra->status === 'aprovada') {
            return redirect()
                ->route('compra.show', $compra)
                ->with(
                    'error',
                    'Uma compra aprovada não pode ser excluída.'
                );
        }

        if ($compra->status === 'cancelada') {
            return redirect()
                ->route('compra.index')
                ->with(
                    'error',
                    'Uma compra cancelada não pode ser excluída.'
                );
        }

        $compra->delete();

        return redirect()
            ->route('compra.index')
            ->with(
                'success',
                'Compra excluída com sucesso!'
            );
    }
}
