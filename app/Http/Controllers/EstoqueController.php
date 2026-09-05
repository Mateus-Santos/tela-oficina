<?php

namespace App\Http\Controllers;

use App\Actions\Estoque\RegistrarSaida;
use App\Models\Produto;
use Illuminate\Http\Request;
use InvalidArgumentException;
use App\Actions\Estoque\RegistrarAjuste;

class EstoqueController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin:admin');
    }

    public function index(Request $request)
    {
        $query = Produto::query();

        $query->when($request->filled('nome'), function ($q) use ($request) {
            $q->where('nome', 'like', '%' . $request->nome . '%');
        });

        $query->when($request->filled('codigo_fabricante'), function ($q) use ($request) {
            $q->where('codigo_fabricante', 'like', '%' . $request->codigo_fabricante . '%');
        });

        $query->when($request->filled('codigo_barras'), function ($q) use ($request) {
            $q->where('codigo_barras', $request->codigo_barras);
        });

        $query->when($request->filled('situacao'), function ($q) use ($request) {
            match ($request->situacao) {
                'zerado' => $q->where('quantidade', '<=', 0),
                'baixo' => $q->where('quantidade', '>', 0)
                    ->whereColumn('quantidade', '<=', 'estoque_minimo'),
                'normal' => $q->whereColumn('quantidade', '>', 'estoque_minimo'),
                default => null,
            };
        });

        $produtos = $query
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        $totalProdutos = Produto::count();

        $produtosZerados = Produto::where('quantidade', '<=', 0)->count();

        $produtosBaixoEstoque = Produto::where('quantidade', '>', 0)
            ->whereColumn('quantidade', '<=', 'estoque_minimo')
            ->count();

        $produtosEstoqueNormal = Produto::whereColumn('quantidade', '>', 'estoque_minimo')
            ->count();

        return view('estoque.index', compact(
            'produtos',
            'totalProdutos',
            'produtosZerados',
            'produtosBaixoEstoque',
            'produtosEstoqueNormal'
        ));
    }

    public function saida(Produto $produto)
    {
        return view('estoque.saida', compact('produto'));
    }

    public function registrarSaida(Request $request, Produto $produto, RegistrarSaida $registrarSaida)
    {
        $request->validate([
            'quantidade' => 'required|numeric|min:0.001',
            'observacoes' => 'nullable|string|max:1000',
        ], [
            'quantidade.required' => 'Informe a quantidade da saída.',
            'quantidade.numeric' => 'A quantidade deve ser um número válido.',
            'quantidade.min' => 'A quantidade deve ser maior que zero.',
            'observacoes.max' => 'A observação não pode ultrapassar 1000 caracteres.',
        ]);

        try {
            $registrarSaida->execute(
                produto: $produto,
                quantidade: (float) $request->quantidade,
                observacoes: $request->observacoes
            );
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors([
                    'quantidade' => $e->getMessage(),
                ]);
        }

        return redirect()
            ->route('estoque.index')
            ->with('success', 'Saída de estoque registrada com sucesso!');
    }

    public function ajuste(Produto $produto)
    {
        return view('estoque.ajuste', compact('produto'));
    }

    public function registrarAjuste(
        Request $request,
        Produto $produto,
        RegistrarAjuste $registrarAjuste
    ) {
        $request->validate([
            'novo_saldo' => 'required|numeric|min:0',
            'observacoes' => 'required|string|max:1000',
        ], [
            'novo_saldo.required' => 'Informe o novo estoque.',
            'novo_saldo.numeric' => 'O novo estoque deve ser um número válido.',
            'novo_saldo.min' => 'O estoque não pode ser negativo.',
            'observacoes.required' => 'Informe o motivo do ajuste.',
            'observacoes.max' => 'A observação não pode ultrapassar 1000 caracteres.',
        ]);

        try {
            $registrarAjuste->execute(
                produto: $produto,
                novoSaldo: (float) $request->novo_saldo,
                observacoes: $request->observacoes
            );
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors([
                    'novo_saldo' => $e->getMessage(),
                ]);
        }

        return redirect()
            ->route('estoque.index')
            ->with('success', 'Ajuste de estoque registrado com sucesso!');
    }
}
