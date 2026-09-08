<?php

namespace App\Http\Controllers;

use App\Actions\Financeiro\EstornarRecebimento;
use App\Actions\Financeiro\RegistrarRecebimento;
use App\Http\Requests\EstornarRecebimentoRequest;
use App\Http\Requests\StoreRecebimentoRequest;
use App\Models\ContaReceber;
use App\Models\FormaPagamento;
use App\Models\Recebimento;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

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
            ->whereNull('estornado_em')
            ->sum('valor');

        $saldo = max(
            0,
            $valorDevido - $valorRecebido
        );

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
    ): RedirectResponse {
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

    public function estornar(
        EstornarRecebimentoRequest $request,
        ContaReceber $contaReceber,
        Recebimento $recebimento,
        EstornarRecebimento $estornarRecebimento
    ): RedirectResponse {
        try {
            $estornarRecebimento->execute(
                $contaReceber,
                $recebimento,
                $request->validated()['motivo']
            );
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route(
                    'contas-receber.show',
                    $contaReceber
                )
                ->with(
                    'error',
                    $e->getMessage()
                );
        }

        return redirect()
            ->route(
                'contas-receber.show',
                $contaReceber
            )
            ->with(
                'success',
                'Recebimento estornado com sucesso.'
            );
    }
}

