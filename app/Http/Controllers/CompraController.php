<?php

namespace App\Http\Controllers;

use App\Actions\Compra\AtualizarCompra;
use App\Actions\Compra\CriarCompra;
use App\Actions\Compra\RegistrarEntradaCompra;
use App\Http\Requests\Compra\StoreCompraRequest;
use App\Http\Requests\Compra\UpdateCompraRequest;
use App\Models\Compra;
use App\Models\Fornecedor;
use App\Models\Produto;
use Illuminate\Http\RedirectResponse;
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
                $query->where('numero_nf', 'like', '%' . $request->numero_nf . '%');
            })
            ->when($request->filled('fornecedor_id'), function ($query) use ($request) {
                $query->where('fornecedor_id', $request->fornecedor_id);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('data_inicio'), function ($query) use ($request) {
                $query->whereDate('data_entrada', '>=', $request->data_inicio);
            })
            ->when($request->filled('data_fim'), function ($query) use ($request) {
                $query->whereDate('data_entrada', '<=', $request->data_fim);
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
        $dados = $request->validated();
        $anexos = $dados['anexos'] ?? [];
        unset($dados['anexos']);

        $compra = $criarCompra->execute($dados, $anexos);

        return redirect()
            ->route('compras.show', $compra)
            ->with('success', 'Compra cadastrada com sucesso!');
    }

    /**
     * Exibe os detalhes da compra.
     */
    public function show(Compra $compra)
    {
        $compra->load([
            'fornecedor',
            'itens.produto',
            'itens.movimentacoesEstoque',
            'anexos',
        ]);

        return view(
            'compra.show',
            compact('compra')
        );
    }

    /**
     * Registra a entrada da compra no estoque.
     */
    public function registrarEstoque(
        Compra $compra,
        RegistrarEntradaCompra $registrarEntradaCompra
    ): RedirectResponse {
        try {
            $registrarEntradaCompra->execute($compra);

            return redirect()
                ->route('compras.show', $compra)
                ->with(
                    'success',
                    'Entrada da compra registrada no estoque com sucesso!'
                );
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('compras.show', $compra)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Verifica se a compra possui algum item lançado no estoque.
     */
    private function estoqueLancado(Compra $compra): bool
    {
        return $compra->itens()
            ->whereHas('movimentacoesEstoque', function ($query) {
                $query->where('tipo', 'entrada');
            })
            ->exists();
    }

    /**
     * Exibe o formulário de edição.
     */
    public function edit(Compra $compra)
    {
        if (in_array($compra->status, ['aprovada', 'cancelada'], true)) {
            return redirect()
                ->route('compras.show', $compra)
                ->with(
                    'error',
                    'Uma compra aprovada ou cancelada não pode ser editada.'
                );
        }

        if ($this->estoqueLancado($compra)) {
            return redirect()
                ->route('compras.show', $compra)
                ->with(
                    'error',
                    'Uma compra que já possui entrada no estoque não pode ser editada.'
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
        if (in_array($compra->status, ['aprovada', 'cancelada'], true)) {
            return redirect()
                ->route('compras.show', $compra)
                ->with(
                    'error',
                    'Uma compra aprovada ou cancelada não pode ser alterada.'
                );
        }

        if ($this->estoqueLancado($compra)) {
            return redirect()
                ->route('compras.show', $compra)
                ->with(
                    'error',
                    'Uma compra que já possui entrada no estoque não pode ser alterada.'
                );
        }

        $compraAtualizada = $atualizarCompra->execute(
            $compra,
            $request->validated()
        );

        return redirect()
            ->route('compras.show', $compraAtualizada)
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
                ->route('compras.show', $compra)
                ->with(
                    'error',
                    'Uma compra aprovada não pode ser excluída.'
                );
        }

        if ($compra->status === 'cancelada') {
            return redirect()
                ->route('compras.index')
                ->with(
                    'error',
                    'Uma compra cancelada não pode ser excluída.'
                );
        }

        if ($this->estoqueLancado($compra)) {
            return redirect()
                ->route('compras.show', $compra)
                ->with(
                    'error',
                    'Uma compra que já possui entrada no estoque não pode ser excluída.'
                );
        }

        $compra->delete();

        return redirect()
            ->route('compras.index')
            ->with(
                'success',
                'Compra excluída com sucesso!'
            );
    }
}
