<?php

namespace App\Http\Controllers;

use App\Actions\ContasPagar\AtualizarContaPagar;
use App\Actions\ContasPagar\CancelarContaPagar;
use App\Actions\ContasPagar\CriarContaPagar;
use App\Actions\ContasPagar\EstornarPagamentoContaPagar;
use App\Actions\ContasPagar\RegistrarPagamentoContaPagar;
use App\Http\Requests\ContasPagar\CancelarContaPagarRequest;
use App\Http\Requests\ContasPagar\EstornarPagamentoContaPagarRequest;
use App\Http\Requests\ContasPagar\RegistrarPagamentoContaPagarRequest;
use App\Http\Requests\ContasPagar\StoreContaPagarRequest;
use App\Http\Requests\ContasPagar\UpdateContaPagarRequest;
use App\Models\ContaPagar;
use App\Models\Fornecedor;
use App\Models\Nota;
use App\Models\PagamentoContaPagar;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContaPagarController extends Controller
{
    public function index(): View
    {
        $query = ContaPagar::query()
            ->with('fornecedor')
            ->withSum([
                'pagamentos as valor_pago' => fn ($query) => $query->whereNull('estornado_em'),
            ], 'valor');

        if (request()->filled('descricao')) {
            $descricao = trim(request('descricao'));
            $query->where('descricao', 'like', '%' . $descricao . '%');
        }

        if (request()->filled('fornecedor_id')) {
            $query->where('fornecedor_id', request('fornecedor_id'));
        }

        if (request()->filled('status')) {
            $status = request('status');

            if ($status === 'vencida') {
                $query->where('status', '!=', 'cancelada')
                    ->where('status', '!=', 'paga')
                    ->whereDate('data_vencimento', '<', today());
            } else {
                $query->where('status', $status);
            }
        }

        if (request()->filled('data_emissao_inicio')) {
            $query->whereDate('data_emissao', '>=', request('data_emissao_inicio'));
        }

        if (request()->filled('data_emissao_fim')) {
            $query->whereDate('data_emissao', '<=', request('data_emissao_fim'));
        }

        if (request()->filled('data_vencimento_inicio')) {
            $query->whereDate('data_vencimento', '>=', request('data_vencimento_inicio'));
        }

        if (request()->filled('data_vencimento_fim')) {
            $query->whereDate('data_vencimento', '<=', request('data_vencimento_fim'));
        }

        $contas = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $fornecedores = Fornecedor::query()
            ->orderBy('nome')
            ->get();

        return view('contas_pagar.index', compact('contas', 'fornecedores'));
    }

    public function create(): View
    {
        $fornecedores = Fornecedor::query()
            ->orderBy('nome')
            ->get();

        $notas = Nota::query()
            ->with('cliente.pessoa')
            ->where('status', '!=', 'Cancelado')
            ->latest()
            ->get();

        return view('contas_pagar.create', compact('fornecedores', 'notas'));
    }

    public function store(
        StoreContaPagarRequest $request,
        CriarContaPagar $action
    ): RedirectResponse {
        $conta = $action->execute($request->validated());

        return redirect()
            ->route('contas-pagar.show', $conta)
            ->with('success', 'Conta a pagar criada com sucesso.');
    }

    public function show(ContaPagar $conta): View
    {
        $conta->load([
            'fornecedor',
            'nota',
            'pagamentos' => fn ($query) => $query->latest('data_pagamento'),
        ]);

        return view('contas_pagar.show', compact('conta'));
    }

    public function edit(ContaPagar $conta): View
    {
        $fornecedores = Fornecedor::query()
            ->orderBy('nome')
            ->get();

        $notas = Nota::query()
            ->with('cliente.pessoa')
            ->where(function ($query) use ($conta) {
                $query->where('status', '!=', 'Cancelado')
                    ->orWhere('id', $conta->nota_id);
            })
            ->latest()
            ->get();

        return view('contas_pagar.edit', compact('conta', 'fornecedores', 'notas'));
    }

    public function update(
        UpdateContaPagarRequest $request,
        ContaPagar $conta,
        AtualizarContaPagar $action
    ): RedirectResponse {
        $action->execute($conta, $request->validated());

        return redirect()
            ->route('contas-pagar.show', $conta)
            ->with('success', 'Conta a pagar atualizada com sucesso.');
    }

    public function registrarPagamento(
        RegistrarPagamentoContaPagarRequest $request,
        ContaPagar $conta,
        RegistrarPagamentoContaPagar $action
    ): RedirectResponse {
        $action->execute($conta, $request->validated());

        return redirect()
            ->route('contas-pagar.show', $conta)
            ->with('success', 'Pagamento registrado com sucesso.');
    }

    public function estornarPagamento(
        EstornarPagamentoContaPagarRequest $request,
        ContaPagar $conta,
        PagamentoContaPagar $pagamento,
        EstornarPagamentoContaPagar $action
    ): RedirectResponse {
        $action->execute(
            $conta,
            $pagamento,
            $request->validated('motivo')
        );

        return redirect()
            ->route('contas-pagar.show', $conta)
            ->with('success', 'Pagamento estornado com sucesso.');
    }

    public function cancelar(
        CancelarContaPagarRequest $request,
        ContaPagar $conta,
        CancelarContaPagar $action
    ): RedirectResponse {
        $action->execute(
            $conta,
            $request->validated('motivo')
        );

        return redirect()
            ->route('contas-pagar.show', $conta)
            ->with('success', 'Conta a pagar cancelada com sucesso.');
    }
}
