<?php

namespace App\Http\Controllers;

use App\Actions\Estoque\RegistrarAjuste;
use App\Actions\Estoque\RegistrarSaida;
use App\Models\Produto;
use Illuminate\Http\Request;
use InvalidArgumentException;

class EstoqueController extends Controller
{
    public function index(Request $request)
    {
        $query = Produto::query()
            ->with([
                'marcaRelacionada',
                'imagens',
            ]);

        if ($request->filled('nome')) {
            $nome = trim(
                (string) $request->input('nome')
            );

            $query->where(
                'nome',
                'like',
                "%{$nome}%"
            );
        }

        if ($request->filled('codigo_fabricante')) {
            $codigoFabricante = trim(
                (string) $request->input(
                    'codigo_fabricante'
                )
            );

            $query->where(
                'codigo_fabricante',
                'like',
                "%{$codigoFabricante}%"
            );
        }

        if ($request->filled('codigo_barras')) {
            $codigoBarras = trim(
                (string) $request->input(
                    'codigo_barras'
                )
            );

            $query->where(
                'codigo_barras',
                $codigoBarras
            );
        }

        if ($request->filled('marca')) {
            $marca = trim(
                (string) $request->input('marca')
            );

            $query->whereHas(
                'marcaRelacionada',
                function ($marcaQuery) use ($marca) {
                    $marcaQuery->where(
                        'nome',
                        'like',
                        "%{$marca}%"
                    );
                }
            );
        }

        if (
            $request->has('status')
            && $request->input('status') !== ''
        ) {
            $query->where(
                'status',
                $request->input('status') === '1'
            );
        }

        if ($request->filled('situacao')) {
            match ($request->input('situacao')) {
                'zerado' => $query->where(
                    'quantidade',
                    '<=',
                    0
                ),

                'baixo' => $query
                    ->where(
                        'quantidade',
                        '>',
                        0
                    )
                    ->whereColumn(
                        'quantidade',
                        '<=',
                        'estoque_minimo'
                    ),

                'normal' => $query->whereColumn(
                    'quantidade',
                    '>',
                    'estoque_minimo'
                ),

                default => null,
            };
        }

        if ($request->filled('imagem')) {
            match ($request->input('imagem')) {
                'com_imagem' => $query->whereHas('imagens'),

                'sem_imagem' => $query->whereDoesntHave('imagens'),

                default => null,
            };
        }

        $produtos = $query
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        $totalProdutos =
            Produto::query()->count();

        $produtosZerados =
            Produto::query()
                ->where(
                    'quantidade',
                    '<=',
                    0
                )
                ->count();

        $produtosBaixoEstoque =
            Produto::query()
                ->where(
                    'quantidade',
                    '>',
                    0
                )
                ->whereColumn(
                    'quantidade',
                    '<=',
                    'estoque_minimo'
                )
                ->count();

        $produtosEstoqueNormal =
            Produto::query()
                ->whereColumn(
                    'quantidade',
                    '>',
                    'estoque_minimo'
                )
                ->count();

        return view(
            'estoque.index',
            compact(
                'produtos',
                'totalProdutos',
                'produtosZerados',
                'produtosBaixoEstoque',
                'produtosEstoqueNormal'
            )
        );
    }

    public function saida(Produto $produto)
    {
        return view(
            'estoque.saida',
            compact('produto')
        );
    }

    public function registrarSaida(
        Request $request,
        Produto $produto,
        RegistrarSaida $registrarSaida
    ) {
        $request->validate(
            [
                'quantidade' => 'required|numeric|min:0.001',
                'observacoes' => 'nullable|string|max:1000',
            ],
            [
                'quantidade.required' => 'Informe a quantidade da saída.',

                'quantidade.numeric' => 'A quantidade deve ser um número válido.',

                'quantidade.min' => 'A quantidade deve ser maior que zero.',

                'observacoes.max' => 'A observação não pode ultrapassar 1000 caracteres.',
            ]
        );

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
            ->with(
                'success',
                'Saída de estoque registrada com sucesso!'
            );
    }

    public function ajuste(Produto $produto)
    {
        return view(
            'estoque.ajuste',
            compact('produto')
        );
    }

    public function registrarAjuste(
        Request $request,
        Produto $produto,
        RegistrarAjuste $registrarAjuste
    ) {
        $request->validate(
            [
                'novo_saldo' => 'required|numeric|min:0',
                'observacoes' => 'required|string|max:1000',
            ],
            [
                'novo_saldo.required' => 'Informe o novo estoque.',

                'novo_saldo.numeric' => 'O novo estoque deve ser um número válido.',

                'novo_saldo.min' => 'O estoque não pode ser negativo.',

                'observacoes.required' => 'Informe o motivo do ajuste.',

                'observacoes.max' => 'A observação não pode ultrapassar 1000 caracteres.',
            ]
        );

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
            ->with(
                'success',
                'Ajuste de estoque registrado com sucesso!'
            );
    }
}
