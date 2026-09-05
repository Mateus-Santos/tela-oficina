<?php

namespace App\Http\Controllers;

use App\Models\MovimentacaoEstoque;
use App\Models\Produto;
use Illuminate\Http\Request;

class MovimentacaoEstoqueController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin:admin');
    }

    public function index(Request $request)
    {
        $query = MovimentacaoEstoque::with([
            'produto:id,nome,codigo_fabricante',
            'usuario:id,pessoa_id',
            'usuario.pessoa:id,nome',
        ])->orderByDesc('created_at');

        $query->when($request->filled('produto_id'), function ($q) use ($request) {
            $q->where('produto_id', $request->produto_id);
        });

        $query->when($request->filled('tipo'), function ($q) use ($request) {
            $q->where('tipo', $request->tipo);
        });

        $query->when($request->filled('data_inicio'), function ($q) use ($request) {
            $q->whereDate('created_at', '>=', $request->data_inicio);
        });

        $query->when($request->filled('data_fim'), function ($q) use ($request) {
            $q->whereDate('created_at', '<=', $request->data_fim);
        });

        $movimentacoes = $query
            ->paginate(20)
            ->withQueryString();

        $produtos = Produto::select('id', 'nome', 'codigo_fabricante')
            ->orderBy('nome')
            ->get();

        return view('estoque.movimentacoes', compact(
            'movimentacoes',
            'produtos'
        ));
    }
}
