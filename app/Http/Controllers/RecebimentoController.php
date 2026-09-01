<?php

namespace App\Http\Controllers;

use App\Actions\Financeiro\RegistrarRecebimento;
use App\Http\Requests\StoreRecebimentoRequest;
use App\Models\ContaReceber;
use App\Models\FormaPagamento;

class RecebimentoController extends Controller
{
    public function create(ContaReceber $contaReceber)
    {
        if ($contaReceber->status === 'cancelada') {
            return redirect()
                ->route(
                    'contas-receber.show',
                    $contaReceber
                )
                ->with(
                    'error',
                    'Não é possível receber uma conta cancelada.'
                );
        }

        if ($contaReceber->status === 'quitada') {
            return redirect()
                ->route(
                    'contas-receber.show',
                    $contaReceber
                )
                ->with(
                    'error',
                    'Esta conta já está quitada.'
                );
        }

        $valorDevido =
            (float) $contaReceber->valor_original
            - (float) $contaReceber->desconto
            + (float) $contaReceber->juros
            + (float) $contaReceber->multa;

        $valorRecebido = (float) $contaReceber
            ->recebimentos()
            ->sum('valor');

        $saldo = $valorDevido - $valorRecebido;

        $formasPagamento = FormaPagamento::query()
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        return view(
            'financeiro.recebimentos.create',
            compact(
                'contaReceber',
                'formasPagamento',
                'saldo'
            )
        );
    }

    public function store(
        StoreRecebimentoRequest $request,
        RegistrarRecebimento $registrarRecebimento
    ) {
        $recebimento = $registrarRecebimento->execute(
            $request->validated()
        );

        return redirect()
            ->route(
                'contas-receber.show',
                $recebimento->contaReceber
            )
            ->with(
                'success',
                'Recebimento registrado com sucesso.'
            );
    }
}
