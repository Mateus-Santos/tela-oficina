<?php

namespace App\Http\Controllers;

use App\Actions\Financeiro\CriarContaReceber;
use App\Http\Requests\StoreContaReceberRequest;
use App\Http\Requests\UpdateContaReceberRequest;
use App\Models\CategoriaFinanceira;
use App\Models\Cliente;
use App\Models\ContaReceber;
use App\Models\Nota;
use Illuminate\Http\Request;
use App\Actions\Financeiro\AtualizarContaReceber;


class ContaReceberController extends Controller
{
    public function index(Request $request)
    {
        $query = ContaReceber::query()
            ->with([
                'cliente',
                'nota',
                'categoriaFinanceira',
            ]);

        if ($request->filled('cliente')) {
            $cliente = $request->input('cliente');

            $query->whereHas('cliente', function ($q) use ($cliente) {
                $q->where('nome', 'like', "%{$cliente}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('data_inicio')) {
            $query->whereDate(
                'data_vencimento',
                '>=',
                $request->input('data_inicio')
            );
        }

        if ($request->filled('data_fim')) {
            $query->whereDate(
                'data_vencimento',
                '<=',
                $request->input('data_fim')
            );
        }

        if ($request->filled('nota_id')) {
            $query->where(
                'nota_id',
                $request->input('nota_id')
            );
        }

        $contasReceber = $query
            ->orderByDesc('data_vencimento')
            ->paginate(15)
            ->withQueryString();

        return view(
            'financeiro.contas_receber.index',
            compact('contasReceber')
        );
    }

    public function create()
    {
        $clientes = Cliente::query()
            ->orderBy('nome')
            ->get();

        $notas = Nota::query()
            ->with('cliente')
            ->orderByDesc('id')
            ->get();

        $categorias = CategoriaFinanceira::query()
            ->where('tipo', 'entrada')
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        return view(
            'financeiro.contas_receber.create',
            compact(
                'clientes',
                'notas',
                'categorias'
            )
        );
    }

    public function store(
        StoreContaReceberRequest $request,
        CriarContaReceber $criarContaReceber
    ) {
        $criarContaReceber->execute(
            $request->validated()
        );

        return redirect()
            ->route('contas-receber.index')
            ->with('success', 'Conta a receber criada com sucesso.');
    }

    public function show(ContaReceber $contaReceber)
    {
        $contaReceber->load([
            'cliente',
            'nota',
            'categoriaFinanceira',
            'recebimentos.formaPagamento',
            'recebimentos.usuario',
        ]);

        $valorDevido =
            (float) $contaReceber->valor_original
            - (float) $contaReceber->desconto
            + (float) $contaReceber->juros
            + (float) $contaReceber->multa;

        $valorRecebido = (float) $contaReceber
            ->recebimentos
            ->sum('valor');

        $saldo = $valorDevido - $valorRecebido;

        return view(
            'financeiro.contas_receber.show',
            compact(
                'contaReceber',
                'valorDevido',
                'valorRecebido',
                'saldo'
            )
        );
    }

    public function edit(ContaReceber $contaReceber)
    {
        if ($contaReceber->recebimentos()->exists()) {
            return redirect()
                ->route(
                    'contas-receber.show',
                    $contaReceber
                )
                ->with(
                    'error',
                    'Contas que possuem recebimentos não podem ser editadas.'
                );
        }

        $clientes = Cliente::query()
            ->orderBy('nome')
            ->get();

        $notas = Nota::query()
            ->with('cliente')
            ->orderByDesc('id')
            ->get();

        $categorias = CategoriaFinanceira::query()
            ->where('tipo', 'entrada')
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        return view(
            'financeiro.contas_receber.edit',
            compact(
                'contaReceber',
                'clientes',
                'notas',
                'categorias'
            )
        );
    }

    public function update(UpdateContaReceberRequest $request, ContaReceber $contaReceber, AtualizarContaReceber $atualizarContaReceber)
    {
        $atualizarContaReceber->execute($contaReceber, $request->validated());

        return redirect()
            ->route('contas-receber.show', $contaReceber)
            ->with('success', 'Conta a receber atualizada com sucesso.');
    }

    public function destroy(ContaReceber $contaReceber)
    {
        if ($contaReceber->recebimentos()->exists()) {
            return redirect()
                ->route(
                    'contas-receber.show',
                    $contaReceber
                )
                ->with(
                    'error',
                    'Contas que possuem recebimentos não podem ser excluídas.'
                );
        }

        $contaReceber->delete();

        return redirect()
            ->route('contas-receber.index')
            ->with(
                'success',
                'Conta a receber excluída com sucesso.'
            );
    }
}
